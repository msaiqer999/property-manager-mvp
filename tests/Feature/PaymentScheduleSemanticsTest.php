<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\PaymentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentScheduleSemanticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_amount_repeats_unchanged_monthly(): void
    {
        $contract = $this->contract([
            'rent_amount' => 3000,
            'payment_frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);

        PaymentSchedule::createFor($contract);

        $this->assertSame(['3000.00', '3000.00', '3000.00'], $contract->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
    }

    public function test_quarterly_amount_repeats_unchanged_every_three_months(): void
    {
        $contract = $this->contract([
            'rent_amount' => 10000,
            'payment_frequency' => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        PaymentSchedule::createFor($contract);

        $this->assertSame(['10000.00', '10000.00'], $contract->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
    }

    public function test_semi_annual_amount_repeats_unchanged_every_six_months(): void
    {
        $contract = $this->contract([
            'rent_amount' => 25000,
            'payment_frequency' => 'semi_annual',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        PaymentSchedule::createFor($contract);

        $this->assertSame(['25000.00', '25000.00'], $contract->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
    }

    public function test_annual_amount_for_exact_twelve_month_period_creates_one_unmultiplied_installment(): void
    {
        $contract = $this->contract([
            'rent_amount' => 50000,
            'payment_frequency' => 'annual',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        PaymentSchedule::createFor($contract);

        $this->assertSame(['50000.00'], $contract->payments()->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
        $this->assertDatabaseMissing('payments', [
            'contract_id' => $contract->id,
            'amount_due' => 600000,
        ]);
    }

    public function test_contract_longer_than_one_year_gets_full_annual_installment_plus_prorated_final_installment(): void
    {
        $contract = $this->contract([
            'rent_amount' => 50000,
            'payment_frequency' => 'annual',
            'start_date' => '2026-06-24',
            'end_date' => '2027-10-05',
        ]);

        PaymentSchedule::createFor($contract);

        $amounts = $contract->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all();

        $this->assertSame(['50000.00', '14207.65'], $amounts);
    }

    public function test_short_final_monthly_and_quarterly_periods_are_prorated_and_rounded(): void
    {
        $monthly = $this->contract([
            'rent_amount' => 3000,
            'payment_frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-10',
        ]);
        PaymentSchedule::createFor($monthly);

        $quarterly = $this->contract([
            'rent_amount' => 10000,
            'payment_frequency' => 'quarterly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-02-15',
        ]);
        PaymentSchedule::createFor($quarterly);

        $this->assertSame(['3000.00', '1071.43'], $monthly->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
        $this->assertSame(['5111.11'], $quarterly->payments()->orderBy('due_date')->pluck('amount_due')->map(fn ($amount) => number_format((float) $amount, 2, '.', ''))->all());
    }

    public function test_existing_recorded_schedules_are_not_silently_replaced(): void
    {
        $contract = $this->contract([
            'rent_amount' => 3000,
            'payment_frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        PaymentSchedule::createFor($contract);

        $firstPayment = $contract->payments()->orderBy('due_date')->firstOrFail();
        $firstPayment->update([
            'amount_paid' => 100,
            'payment_date' => '2026-01-05',
            'status' => 'partial',
        ]);

        $contract->update(['rent_amount' => 5000]);
        PaymentSchedule::replaceFor($contract);

        $this->assertSame(3, $contract->payments()->count());
        $this->assertDatabaseHas('payments', [
            'id' => $firstPayment->id,
            'amount_due' => 3000,
            'amount_paid' => 100,
        ]);
    }

    public function test_contract_display_payments_and_reports_use_corrected_scheduled_amounts(): void
    {
        [$owner, $data] = $this->scenario();

        $this->actingAs($owner)->post(route('contracts.store'), [
            'tenant_mode' => 'existing',
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->subDay()->toDateString(),
            'rent_amount' => 50000,
            'payment_frequency' => 'annual',
            'deposit_amount' => 5000,
            'status' => 'active',
            'notes' => 'Annual period amount',
        ])->assertRedirect();

        $contract = Contract::latest('id')->firstOrFail();
        $payment = $contract->payments()->firstOrFail();
        $payment->update([
            'amount_paid' => 50000,
            'payment_date' => now()->startOfMonth()->toDateString(),
            'status' => 'paid',
        ]);

        $this->actingAs($owner)->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Rent per payment period')
            ->assertSee('50,000.00')
            ->assertDontSee('600,000.00');

        $this->actingAs($owner)->get(route('payments.index'))
            ->assertOk()
            ->assertSee('50,000.00')
            ->assertDontSee('600,000.00');

        $this->actingAs($owner)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('50,000.00')
            ->assertDontSee('600,000.00');
    }

    private function contract(array $overrides = []): Contract
    {
        [, $data] = $this->scenario();

        return Contract::create(array_merge([
            'organization_id' => $data['organization']->id,
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'contract_number' => 'TEST-'.uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ], $overrides));
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Schedule Org']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'owner-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Schedule Building',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'S-'.uniqid(),
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Schedule Tenant',
        ]);

        return [$owner, compact('organization', 'building', 'unit', 'tenant')];
    }
}
