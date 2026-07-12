<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\TestCase;

class ContractLifecycleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_past_contract_becomes_expired_and_command_is_idempotent(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $contract = $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
            'status' => 'active',
        ]);

        $this->artisan('contracts:expire')
            ->expectsOutput('Contracts expiry complete. Inspected: 1; expired: 1; skipped: 0; failed: 0.')
            ->assertSuccessful();

        $this->assertSame('expired', $contract->fresh()->status);
        $this->assertSame('vacant', $data['unit']->fresh()->status);

        $this->artisan('contracts:expire')
            ->expectsOutput('Contracts expiry complete. Inspected: 0; expired: 0; skipped: 0; failed: 0.')
            ->assertSuccessful();

        $this->assertSame('expired', $contract->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_current_active_terminated_and_existing_expired_contracts_are_not_changed(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $current = $this->contract($data, [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'status' => 'active',
        ]);
        $terminated = $this->contract($data, [
            'unit_id' => $data['secondUnit']->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
            'status' => 'terminated',
        ]);
        $expired = $this->contract($data, [
            'unit_id' => $data['thirdUnit']->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
            'status' => 'expired',
        ]);

        $this->artisan('contracts:expire')
            ->expectsOutput('Contracts expiry complete. Inspected: 0; expired: 0; skipped: 0; failed: 0.')
            ->assertSuccessful();

        $this->assertSame('active', $current->fresh()->status);
        $this->assertSame('terminated', $terminated->fresh()->status);
        $this->assertSame('expired', $expired->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_contract_expiry_rolls_back_failed_contract_and_continues_processing(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $data['unit']->update(['status' => 'rented']);
        $data['secondUnit']->update(['status' => 'rented']);
        $failedContract = $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
        ]);
        $successfulContract = $this->contract($data, [
            'unit_id' => $data['secondUnit']->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
        ]);

        Unit::updating(function (Unit $unit): void {
            if ($unit->unit_number === '101') {
                throw new RuntimeException('Simulated occupancy sync failure.');
            }
        });

        try {
            $this->artisan('contracts:expire')
                ->expectsOutput('Contracts expiry complete. Inspected: 2; expired: 1; skipped: 0; failed: 1.')
                ->assertExitCode(1);

            $this->assertSame('active', $failedContract->fresh()->status);
            $this->assertSame('rented', $data['unit']->fresh()->status);
            $this->assertSame('expired', $successfulContract->fresh()->status);
            $this->assertSame('vacant', $data['secondUnit']->fresh()->status);
        } finally {
            Unit::flushEventListeners();
            Carbon::setTestNow();
        }
    }

    public function test_unit_remains_rented_if_another_current_active_contract_exists(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $data['unit']->update(['status' => 'rented']);
        $past = $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
        ]);
        $this->contract($data, [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $this->artisan('contracts:expire')->assertSuccessful();

        $this->assertSame('expired', $past->fresh()->status);
        $this->assertSame('rented', $data['unit']->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_future_active_contract_marks_unit_rented(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $data['unit']->update(['status' => 'rented']);
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
        ]);
        $this->contract($data, [
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $this->artisan('contracts:expire')->assertSuccessful();

        $this->assertSame('rented', $data['unit']->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_maintenance_status_is_preserved(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $data['unit']->update(['status' => 'maintenance']);
        $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-16',
        ]);

        $this->artisan('contracts:expire')->assertSuccessful();

        $this->assertSame('maintenance', $data['unit']->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_overdue_schedules_are_updated_without_changing_paid_or_recorded_payments(): void
    {
        Carbon::setTestNow('2026-06-17');
        $data = $this->scenario();
        $contract = $this->contract($data);
        $pending = $this->payment($contract, ['status' => 'pending', 'amount_paid' => 0, 'payment_date' => null]);
        $paid = $this->payment($contract, ['status' => 'paid', 'amount_paid' => 1000, 'payment_date' => '2026-06-10']);
        $recordedAmount = $this->payment($contract, ['status' => 'pending', 'amount_paid' => 100, 'payment_date' => null]);
        $recordedDate = $this->payment($contract, ['status' => 'pending', 'amount_paid' => 0, 'payment_date' => '2026-06-10']);

        $this->artisan('payments:mark-overdue')
            ->expectsOutput('Payments overdue check complete. Affected: 1; status: complete.')
            ->assertSuccessful();

        $this->assertSame('overdue', $pending->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('pending', $recordedAmount->fresh()->status);
        $this->assertSame('pending', $recordedDate->fresh()->status);

        $this->artisan('payments:mark-overdue')
            ->expectsOutput('Payments overdue check complete. Affected: 0; status: complete.')
            ->assertSuccessful();

        Carbon::setTestNow();
    }

    public function test_daily_schedule_contains_required_commands_once(): void
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertSame(1, substr_count($output, 'contracts:expire'));
        $this->assertSame(1, substr_count($output, 'payments:mark-overdue'));
    }

    public function test_daily_schedule_uses_overlap_protection_without_single_server_mutex(): void
    {
        $events = collect(app(Schedule::class)->events());

        $contracts = $events->first(fn ($event) => str_contains($event->command, 'contracts:expire'));
        $payments = $events->first(fn ($event) => str_contains($event->command, 'payments:mark-overdue'));

        $this->assertNotNull($contracts);
        $this->assertNotNull($payments);

        $this->assertSame('30 0 * * *', $contracts->expression);
        $this->assertSame('contracts:expire:daily', $contracts->description);
        $this->assertTrue($contracts->withoutOverlapping);
        $this->assertSame(60, $contracts->expiresAt);
        $this->assertFalse($contracts->onOneServer);

        $this->assertSame('0 1 * * *', $payments->expression);
        $this->assertSame('payments:mark-overdue:daily', $payments->description);
        $this->assertTrue($payments->withoutOverlapping);
        $this->assertSame(60, $payments->expiresAt);
        $this->assertFalse($payments->onOneServer);
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Lifecycle Org']);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Lifecycle Building',
        ]);
        $unit = $this->unit($building, '101');
        $secondUnit = $this->unit($building, '102');
        $thirdUnit = $this->unit($building, '103');
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Lifecycle Tenant',
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit', 'thirdUnit', 'tenant');
    }

    private function unit(Building $building, string $number): Unit
    {
        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => $number,
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);
    }

    private function contract(array $data, array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'organization_id' => $data['organization']->id,
            'unit_id' => $data['unit']->id,
            'tenant_id' => $data['tenant']->id,
            'contract_number' => 'LC-'.uniqid(),
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ], $overrides));
    }

    private function payment(Contract $contract, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-16',
            'amount_due' => 1000,
            'amount_paid' => 0,
            'payment_date' => null,
            'status' => 'pending',
        ], $overrides));
    }
}
