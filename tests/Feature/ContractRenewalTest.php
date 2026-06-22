<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_contract_and_exact_90_day_contract_show_prepare_renewal(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $eligible = $this->contract($data, [
            'contract_number' => 'RENEW-ELIGIBLE',
            'end_date' => now()->addDays(30)->toDateString(),
        ]);
        $exact90 = $this->contract($data, [
            'contract_number' => 'RENEW-090',
            'unit_id' => $data['secondUnit']->id,
            'end_date' => now()->addDays(90)->toDateString(),
        ]);

        foreach ([$eligible, $exact90] as $contract) {
            $this->actingAs($owner)->get(route('contracts.show', $contract))
                ->assertOk()
                ->assertSee('Prepare renewal')
                ->assertSee(route('contracts.create', ['renew_from' => $contract->id]), false);
        }

        Carbon::setTestNow();
    }

    public function test_beyond_90_expired_and_terminated_contracts_are_not_eligible(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $contracts = [
            $this->contract($data, ['contract_number' => 'RENEW-091', 'end_date' => now()->addDays(91)->toDateString()]),
            $this->contract($data, ['contract_number' => 'RENEW-EXPIRED', 'unit_id' => $data['secondUnit']->id, 'status' => 'expired']),
        ];
        $terminated = $this->contract($data, ['contract_number' => 'RENEW-TERMINATED', 'unit_id' => $data['thirdUnit']->id, 'status' => 'terminated']);

        foreach ($contracts as $contract) {
            $this->actingAs($owner)->get(route('contracts.show', $contract))
                ->assertOk()
                ->assertDontSee('Prepare renewal');

            $this->actingAs($owner)->get(route('contracts.create', ['renew_from' => $contract->id]))
                ->assertNotFound();
        }

        $this->actingAs($owner)->get(route('contracts.show', $terminated))
            ->assertOk()
            ->assertDontSee('Prepare renewal');

        $this->actingAs($owner)->get(route('contracts.create', ['renew_from' => $terminated->id]))
            ->assertStatus(422)
            ->assertSee(__('contracts.lifecycle.cannot_renew_terminated'));

        Carbon::setTestNow();
    }

    public function test_renewal_form_prefills_terms_and_keeps_tenant_and_unit_read_only(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $source = $this->contract($data, [
            'contract_number' => 'RENEW-SOURCE',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'rent_amount' => 25000,
            'payment_frequency' => 'semi_annual',
            'deposit_amount' => 9000,
        ]);

        $this->actingAs($owner)->get(route('contracts.create', ['renew_from' => $source->id]))
            ->assertOk()
            ->assertSee('Prepare renewal')
            ->assertSee('This creates a new contract. The existing contract will not be changed.')
            ->assertSee('Existing deposit is not copied automatically.')
            ->assertSee($data['tenant']->full_name)
            ->assertSee($data['unit']->unit_number)
            ->assertDontSee('name="tenant_id"', false)
            ->assertDontSee('name="unit_id"', false)
            ->assertSee('value="2026-07-01"', false)
            ->assertSee('value="2027-06-30"', false)
            ->assertSee('value="25000"', false)
            ->assertSee('value="0"', false)
            ->assertSee('semi_annual');

        Carbon::setTestNow();
    }

    public function test_renewal_ignores_forged_tenant_and_unit_and_creates_separate_contract_schedule(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $source = $this->contract($data, [
            'contract_number' => 'RENEW-SOURCE',
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
            'rent_amount' => 25000,
            'payment_frequency' => 'semi_annual',
            'deposit_amount' => 9000,
        ]);
        $original = $source->only(['tenant_id', 'unit_id', 'start_date', 'end_date', 'rent_amount', 'payment_frequency', 'deposit_amount', 'status']);
        $paymentCount = Payment::count();

        $this->actingAs($owner)->post(route('contracts.store'), [
            'renew_from' => $source->id,
            'tenant_id' => $data['secondTenant']->id,
            'unit_id' => $data['secondUnit']->id,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'rent_amount' => 25000,
            'payment_frequency' => 'semi_annual',
            'deposit_amount' => 0,
            'status' => 'terminated',
            'notes' => 'Prepared renewal',
        ])->assertRedirect();

        $renewal = Contract::whereKeyNot($source->id)->latest('id')->firstOrFail();

        $this->assertSame($source->tenant_id, $renewal->tenant_id);
        $this->assertSame($source->unit_id, $renewal->unit_id);
        $this->assertSame('2026-07-01', $renewal->start_date->toDateString());
        $this->assertSame('2027-06-30', $renewal->end_date->toDateString());
        $this->assertSame('25000.00', number_format((float) $renewal->rent_amount, 2, '.', ''));
        $this->assertSame('semi_annual', $renewal->payment_frequency);
        $this->assertSame('0.00', number_format((float) $renewal->deposit_amount, 2, '.', ''));
        $this->assertSame('active', $renewal->status);
        $this->assertGreaterThan($paymentCount, Payment::count());

        $source->refresh();
        foreach ($original as $field => $value) {
            $this->assertEquals($value, $source->{$field});
        }

        Carbon::setTestNow();
    }

    public function test_duplicate_or_overlapping_renewal_is_rejected(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $source = $this->contract($data, [
            'start_date' => '2025-07-01',
            'end_date' => '2026-06-30',
        ]);
        $this->contract($data, [
            'contract_number' => 'EXISTING-FUTURE',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $contractCount = Contract::count();
        $paymentCount = Payment::count();

        $this->actingAs($owner)->post(route('contracts.store'), $this->renewalPayload($source))
            ->assertSessionHasErrors('unit_id');

        $this->assertSame($contractCount, Contract::count());
        $this->assertSame($paymentCount, Payment::count());
        $this->assertDatabaseHas('contracts', [
            'id' => $source->id,
            'status' => 'active',
        ]);

        Carbon::setTestNow();
    }

    public function test_other_organization_and_unauthorized_users_cannot_prepare_renewal(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data, $otherData] = $this->scenario();
        $otherSource = $this->contract($otherData);
        $accountant = $this->user($data['organization'], 'renew-accountant@example.com', 'accountant');

        $this->actingAs($owner)->get(route('contracts.create', ['renew_from' => $otherSource->id]))
            ->assertForbidden();

        $this->actingAs($accountant)->get(route('contracts.create', ['renew_from' => $otherSource->id]))
            ->assertForbidden();

        $this->actingAs($accountant)->post(route('contracts.store'), $this->renewalPayload($otherSource))
            ->assertForbidden();

        Carbon::setTestNow();
    }

    public function test_normal_add_contract_form_remains_unchanged(): void
    {
        [$owner] = $this->scenario();

        $this->actingAs($owner)->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('Add contract')
            ->assertSee('Select existing tenant')
            ->assertSee('Add new tenant')
            ->assertDontSee('This creates a new contract. The existing contract will not be changed.');
    }

    private function renewalPayload(Contract $source): array
    {
        $startDate = $source->end_date->copy()->addDay();
        $durationDays = $source->start_date->diffInDays($source->end_date);

        return [
            'renew_from' => $source->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays($durationDays)->toDateString(),
            'rent_amount' => $source->rent_amount,
            'payment_frequency' => $source->payment_frequency,
            'deposit_amount' => 0,
            'status' => 'active',
        ];
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Renewal Org']);
        $otherOrganization = Organization::create(['name' => 'Other Renewal Org']);
        $owner = $this->user($organization, 'renew-owner@example.com', 'owner');

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
            'name' => "Renewal Building {$prefix}",
        ]);
        $unit = $this->unit($building, "{$prefix}-101");
        $secondUnit = $this->unit($building, "{$prefix}-102");
        $thirdUnit = $this->unit($building, "{$prefix}-103");
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Renewal Tenant {$prefix}",
        ]);
        $secondTenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Second Renewal Tenant {$prefix}",
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit', 'thirdUnit', 'tenant', 'secondTenant');
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
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
    }

    private function contract(array $data, array $overrides = []): Contract
    {
        return Contract::create(array_merge([
            'organization_id' => $data['organization']->id,
            'tenant_id' => $data['tenant']->id,
            'unit_id' => $data['unit']->id,
            'contract_number' => 'RENEW-'.uniqid(),
            'start_date' => now()->subYear()->addDays(30)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ], $overrides));
    }
}
