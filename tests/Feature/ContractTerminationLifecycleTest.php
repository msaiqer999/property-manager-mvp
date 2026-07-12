<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractTerminationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_terminates_active_contract_with_server_effective_date_and_metadata(): void
    {
        Carbon::setTestNow('2026-06-22 09:15:00');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data, [
            'start_date' => '2026-06-22',
            'end_date' => '2027-06-21',
            'rent_amount' => 1000,
            'notes' => 'Original terms',
        ]);
        $dueToday = $this->payment($contract, ['due_date' => '2026-06-22', 'status' => 'pending']);
        $futurePending = $this->payment($contract, ['due_date' => '2026-07-22', 'status' => 'pending', 'amount_paid' => 0]);
        $futurePartial = $this->payment($contract, [
            'due_date' => '2026-08-22',
            'status' => 'partial',
            'amount_paid' => 250,
            'payment_date' => '2026-06-20',
            'payment_method' => 'cash',
            'proof_image' => 'payment-proofs/proof.jpg',
            'notes' => 'Keep partial history',
        ]);
        $futurePaid = $this->payment($contract, [
            'due_date' => '2026-09-22',
            'status' => 'paid',
            'amount_paid' => 1000,
            'payment_date' => '2026-06-21',
        ]);
        $data['unit']->update(['status' => 'rented']);

        $this->actingAs($owner)->patch(route('contracts.terminate', $contract), [
            'termination_reason' => "  Owner\u{00A0}asked\tto\nstop  ",
            'termination_effective_date' => '2030-01-01',
        ])->assertRedirect(route('contracts.show', $contract));

        $contract->refresh();
        $this->assertSame('terminated', $contract->status);
        $this->assertSame('2026-06-22', $contract->termination_effective_date->toDateString());
        $this->assertSame('2026-06-22 09:15:00', $contract->terminated_at->toDateTimeString());
        $this->assertSame($owner->id, $contract->terminated_by);
        $this->assertSame('Owner asked to stop', $contract->termination_reason);
        $this->assertSame('Original terms', $contract->notes);
        $this->assertSame('vacant', $data['unit']->fresh()->status);

        $this->assertSame('pending', $dueToday->fresh()->status);
        $this->assertSame('cancelled', $futurePending->fresh()->status);
        $this->assertSame('cancelled', $futurePartial->fresh()->status);
        $this->assertSame('250.00', number_format((float) $futurePartial->fresh()->amount_paid, 2, '.', ''));
        $this->assertSame('cash', $futurePartial->fresh()->payment_method);
        $this->assertSame('payment-proofs/proof.jpg', $futurePartial->fresh()->proof_image);
        $this->assertSame('Keep partial history', $futurePartial->fresh()->notes);
        $this->assertSame('paid', $futurePaid->fresh()->status);

        $log = ActivityLog::where('action', 'contract.terminated')->sole();
        $this->assertStringContainsString('contract_number='.$contract->contract_number, $log->description);
        $this->assertStringContainsString('reason=Owner asked to stop', $log->description);
        $this->assertStringContainsString('effective_date=2026-06-22', $log->description);
        $this->assertStringContainsString('affected_payment_count=2', $log->description);
        $this->assertStringContainsString('affected_remaining_scheduled_amount=1750.00', $log->description);
        $this->assertSame(0, ActivityLog::where('action', 'payment.cancelled')->count());

        Carbon::setTestNow();
    }

    public function test_termination_state_validation_and_role_organization_isolation(): void
    {
        Carbon::setTestNow('2026-06-22');
        [$owner, $data, $otherData] = $this->scenario();
        $manager = $this->user($data['organization'], 'manager-term@example.com', 'manager');
        $accountant = $this->user($data['organization'], 'accountant-term@example.com', 'accountant');
        $caretaker = $this->user($data['organization'], 'caretaker-term@example.com', 'caretaker');
        $contract = $this->contract($data);
        $otherContract = $this->contract($otherData);
        $stale = $this->contract($data, ['unit_id' => $data['secondUnit']->id, 'end_date' => '2026-06-21']);
        $expired = $this->contract($data, ['unit_id' => $data['thirdUnit']->id, 'status' => 'expired']);

        foreach ([$manager, $accountant, $caretaker] as $user) {
            $this->actingAs($user)->patch(route('contracts.terminate', $contract), [
                'termination_reason' => 'No permission',
            ])->assertForbidden();
        }

        $this->actingAs($owner)->patch(route('contracts.terminate', $otherContract), [
            'termination_reason' => 'Cross org',
        ])->assertForbidden();

        $this->actingAs($owner)->patch(route('contracts.terminate', $stale), [
            'termination_reason' => 'Too late',
        ])->assertStatus(422)->assertSee(__('contracts.lifecycle.cannot_terminate_expired'));

        $this->actingAs($owner)->patch(route('contracts.terminate', $expired), [
            'termination_reason' => 'Expired already',
        ])->assertStatus(422)->assertSee(__('contracts.lifecycle.cannot_terminate_expired'));

        $this->assertSame('active', $contract->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_missing_blank_and_overlong_reason_are_rejected_without_mutation(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);

        $this->actingAs($owner)->patch(route('contracts.terminate', $contract), [])
            ->assertSessionHasErrors('termination_reason');

        $this->actingAs($owner)->patch(route('contracts.terminate', $contract), ['termination_reason' => " \u{00A0}\t\n "])
            ->assertSessionHasErrors('termination_reason');

        $this->actingAs($owner)->patch(route('contracts.terminate', $contract), ['termination_reason' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('termination_reason');

        $this->assertSame('active', $contract->fresh()->status);
        $this->assertNull($contract->fresh()->terminated_at);
    }

    public function test_generic_update_cannot_forge_terminated_and_legacy_terminated_is_read_only_but_viewable(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);
        $legacy = $this->contract($data, ['unit_id' => $data['secondUnit']->id, 'status' => 'terminated']);

        $this->actingAs($owner)->put(route('contracts.update', $contract), $this->payload($contract, [
            'status' => 'terminated',
        ]))->assertSessionHasErrors('status');

        $this->assertSame('active', $contract->fresh()->status);
        $this->assertNull($contract->fresh()->terminated_at);

        $this->actingAs($owner)->get(route('contracts.show', $legacy))
            ->assertOk()
            ->assertSee(__('payments.not_available'))
            ->assertDontSee(route('contracts.edit', $legacy), false);

        $this->actingAs($owner)->get(route('contracts.edit', $legacy))
            ->assertStatus(422)
            ->assertSee(__('contracts.lifecycle.cannot_edit_terminated'));

        $this->actingAs($owner)->put(route('contracts.update', $legacy), $this->payload($legacy, [
            'notes' => 'Should not update',
        ]))->assertStatus(422);

        $this->assertNotSame('Should not update', $legacy->fresh()->notes);
        $this->assertSame(0, ActivityLog::where('action', 'contract.updated')->count());
    }

    public function test_cancelled_payment_guards_and_receipts_use_actual_paid_amount(): void
    {
        Storage::fake('local');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);
        $partial = $this->payment($contract, [
            'status' => 'cancelled',
            'amount_paid' => 125,
            'payment_date' => '2026-06-22',
        ]);
        $zero = $this->payment($contract, [
            'due_date' => '2026-07-22',
            'status' => 'cancelled',
            'amount_paid' => 0,
        ]);

        $this->actingAs($owner)->get(route('payments.edit', $partial))
            ->assertStatus(422)
            ->assertSee(__('payments.validation.cannot_record_cancelled'));

        $this->actingAs($owner)->put(route('payments.update', $partial), [
            'amount_paid' => 200,
            'payment_date' => '2026-06-22',
            'payment_method' => 'cash',
            'proof_image' => UploadedFile::fake()->image('new-proof.jpg'),
        ])->assertStatus(422);

        Storage::disk('local')->assertMissing('payment-proofs/new-proof.jpg');
        $this->assertSame('125.00', number_format((float) $partial->fresh()->amount_paid, 2, '.', ''));

        $this->actingAs($owner)->get(route('payments.receipt', $partial))->assertOk();
        $this->actingAs($owner)->get(route('payments.receipt', $zero))
            ->assertStatus(422)
            ->assertSee(__('payments.validation.receipt_unavailable_without_recorded_money'));
    }

    public function test_reports_dashboard_and_overdue_command_exclude_cancelled_remaining_balance_but_count_received_money(): void
    {
        Carbon::setTestNow('2026-06-22');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);
        $this->payment($contract, [
            'status' => 'cancelled',
            'due_date' => '2026-06-15',
            'amount_due' => 1000,
            'amount_paid' => 400,
            'payment_date' => '2026-06-20',
        ]);
        $overdue = $this->payment($contract, [
            'status' => 'overdue',
            'due_date' => '2026-06-01',
            'amount_due' => 1000,
            'amount_paid' => 100,
        ]);
        $cancelledPending = $this->payment($contract, [
            'status' => 'cancelled',
            'due_date' => '2026-06-01',
            'amount_due' => 1000,
            'amount_paid' => 0,
        ]);
        Expense::create([
            'organization_id' => $data['organization']->id,
            'building_id' => $data['building']->id,
            'amount' => 50,
            'category' => 'maintenance',
            'expense_date' => '2026-06-10',
        ]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('400.00')
            ->assertSee('900.00');

        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('400.00')
            ->assertSee('350.00');

        $this->artisan('payments:mark-overdue')
            ->expectsOutput('Payments overdue check complete. Affected: 0; status: complete.')
            ->assertExitCode(0);
        $this->assertSame('cancelled', $cancelledPending->fresh()->status);
        $this->assertSame('overdue', $overdue->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_terminated_contract_cannot_be_renewed_and_history_remains_visible(): void
    {
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data, ['status' => 'terminated']);
        $cancelled = $this->payment($contract, ['status' => 'cancelled']);
        $contractCount = Contract::count();
        $paymentCount = Payment::count();

        $this->actingAs($owner)->get(route('contracts.create', ['renew_from' => $contract->id]))
            ->assertStatus(422)
            ->assertSee(__('contracts.lifecycle.cannot_renew_terminated'));

        $this->actingAs($owner)->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee($contract->contract_number)
            ->assertSee(__('payments.statuses.cancelled'));

        $this->actingAs($owner)->get(route('payments.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee($contract->contract_number)
            ->assertSee(__('payments.lifecycle.cancelled_due_to_contract_termination'));

        $this->assertSame($contractCount, Contract::count());
        $this->assertSame($paymentCount, Payment::count());
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    public function test_unit_occupancy_results_and_logger_rollback(): void
    {
        Carbon::setTestNow('2026-06-22');
        [$owner, $data] = $this->scenario();
        $vacating = $this->contract($data, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $futureOnly = $this->contract($data, [
            'unit_id' => $data['secondUnit']->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);
        $maintenance = $this->contract($data, [
            'unit_id' => $data['thirdUnit']->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $data['unit']->update(['status' => 'rented']);
        $data['secondUnit']->update(['status' => 'rented']);
        $data['thirdUnit']->update(['status' => 'maintenance']);

        $this->actingAs($owner)->patch(route('contracts.terminate', $vacating), ['termination_reason' => 'Vacate'])
            ->assertRedirect(route('contracts.show', $vacating));
        $this->actingAs($owner)->patch(route('contracts.terminate', $futureOnly), ['termination_reason' => 'Future'])
            ->assertRedirect(route('contracts.show', $futureOnly));
        $this->actingAs($owner)->patch(route('contracts.terminate', $maintenance), ['termination_reason' => 'Maintenance'])
            ->assertRedirect(route('contracts.show', $maintenance));

        $this->assertSame('vacant', $data['unit']->fresh()->status);
        $this->assertSame('vacant', $data['secondUnit']->fresh()->status);
        $this->assertSame('maintenance', $data['thirdUnit']->fresh()->status);

        $rollbackContract = $this->contract($data, ['unit_id' => $data['fourthUnit']->id]);
        $rollbackPayment = $this->payment($rollbackContract, ['due_date' => '2026-07-22']);
        $data['fourthUnit']->update(['status' => 'rented']);
        $this->mock(ActivityLogger::class, function ($mock) {
            $mock->shouldReceive('log')->andThrow(new \RuntimeException('log failed'));
        });

        $this->actingAs($owner)->patch(route('contracts.terminate', $rollbackContract), [
            'termination_reason' => 'Rollback',
        ])->assertServerError();

        $this->assertSame('active', $rollbackContract->fresh()->status);
        $this->assertNull($rollbackContract->fresh()->terminated_at);
        $this->assertSame('pending', $rollbackPayment->fresh()->status);
        $this->assertSame('rented', $data['fourthUnit']->fresh()->status);
        $this->assertSame(0, ActivityLog::where('description', 'like', '%Rollback%')->count());
        Carbon::setTestNow();
    }

    public function test_duplicate_termination_does_not_overwrite_metadata_or_logs(): void
    {
        Carbon::setTestNow('2026-06-22 10:00:00');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data);
        $payment = $this->payment($contract, ['due_date' => '2026-07-22']);

        $this->actingAs($owner)->patch(route('contracts.terminate', $contract), ['termination_reason' => 'First'])
            ->assertRedirect(route('contracts.show', $contract));

        Carbon::setTestNow('2026-06-22 11:00:00');
        $this->actingAs($owner)->patch(route('contracts.terminate', $contract->fresh()), ['termination_reason' => 'Second'])
            ->assertStatus(422)
            ->assertSee(__('contracts.lifecycle.already_terminated'));

        $contract->refresh();
        $this->assertSame('First', $contract->termination_reason);
        $this->assertSame('2026-06-22 10:00:00', $contract->terminated_at->toDateTimeString());
        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame(1, ActivityLog::where('action', 'contract.terminated')->count());
        Carbon::setTestNow();
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Termination Org']);
        $otherOrganization = Organization::create(['name' => 'Other Termination Org']);
        $owner = $this->user($organization, 'termination-owner@example.com', 'owner');

        return [
            $owner,
            $this->organizationData($organization, 'A'),
            $this->organizationData($otherOrganization, 'B'),
        ];
    }

    private function organizationData(Organization $organization, string $prefix): array
    {
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => "Termination Building {$prefix}",
        ]);
        $unit = $this->unit($building, "{$prefix}-101");
        $secondUnit = $this->unit($building, "{$prefix}-102");
        $thirdUnit = $this->unit($building, "{$prefix}-103");
        $fourthUnit = $this->unit($building, "{$prefix}-104");
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Termination Tenant {$prefix}",
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit', 'thirdUnit', 'fourthUnit', 'tenant');
    }

    private function user(Organization $organization, string $email, string $role): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'password',
            'role' => $role,
        ]);
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
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'contract_number' => 'TERM-'.uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
            'notes' => 'Existing contract',
        ], $overrides));
    }

    private function payment(Contract $contract, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-22',
            'amount_due' => 1000,
            'amount_paid' => 0,
            'payment_date' => null,
            'status' => 'pending',
            'payment_method' => null,
            'proof_image' => null,
            'notes' => null,
        ], $overrides));
    }

    private function payload(Contract $contract, array $overrides = []): array
    {
        return array_replace([
            'start_date' => $contract->start_date->toDateString(),
            'end_date' => $contract->end_date->toDateString(),
            'rent_amount' => $contract->rent_amount,
            'payment_frequency' => $contract->payment_frequency,
            'deposit_amount' => $contract->deposit_amount,
            'status' => $contract->status,
            'notes' => $contract->notes,
        ], $overrides);
    }
}
