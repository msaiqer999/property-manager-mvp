<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_groups_active_contracts_at_30_60_and_90_day_boundaries(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $within30 = $this->contract($data, ['contract_number' => 'EXP-030', 'end_date' => now()->addDays(30)->toDateString()]);
        $within60 = $this->contract($data, ['contract_number' => 'EXP-060', 'end_date' => now()->addDays(60)->toDateString(), 'unit_id' => $data['secondUnit']->id]);
        $within90 = $this->contract($data, ['contract_number' => 'EXP-090', 'end_date' => now()->addDays(90)->toDateString(), 'unit_id' => $data['thirdUnit']->id]);
        $beyond90 = $this->contract($data, ['contract_number' => 'EXP-091', 'end_date' => now()->addDays(91)->toDateString(), 'unit_id' => $data['fourthUnit']->id]);

        $this->assertSame('30', $within30->expiryWarningGroup());
        $this->assertSame('60', $within60->expiryWarningGroup());
        $this->assertSame('90', $within90->expiryWarningGroup());
        $this->assertNull($beyond90->expiryWarningGroup());

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Contracts expiring soon')
            ->assertSee('EXP-030')
            ->assertSee('EXP-060')
            ->assertSee('EXP-090')
            ->assertDontSee('EXP-091')
            ->assertSee('Expires in 30 days')
            ->assertSee('Expires in 60 days')
            ->assertSee('Expires in 90 days');

        Carbon::setTestNow();
    }

    public function test_expired_terminated_beyond_90_and_other_organization_contracts_are_excluded(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data, $otherData] = $this->scenario();
        $this->contract($data, ['contract_number' => 'EXP-ACTIVE', 'end_date' => now()->addDays(12)->toDateString()]);
        $this->contract($data, ['contract_number' => 'EXP-EXPIRED', 'status' => 'expired', 'end_date' => now()->addDays(12)->toDateString(), 'unit_id' => $data['secondUnit']->id]);
        $this->contract($data, ['contract_number' => 'EXP-TERMINATED', 'status' => 'terminated', 'end_date' => now()->addDays(12)->toDateString(), 'unit_id' => $data['thirdUnit']->id]);
        $this->contract($data, ['contract_number' => 'EXP-FAR', 'end_date' => now()->addDays(120)->toDateString(), 'unit_id' => $data['fourthUnit']->id]);
        $this->contract($otherData, ['contract_number' => 'OTHER-EXP-012', 'end_date' => now()->addDays(12)->toDateString()]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('EXP-ACTIVE')
            ->assertDontSee('EXP-EXPIRED')
            ->assertDontSee('EXP-TERMINATED')
            ->assertDontSee('EXP-FAR')
            ->assertDontSee('OTHER-EXP-012');

        Carbon::setTestNow();
    }

    public function test_dashboard_empty_state_when_no_contracts_are_expiring_soon(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $this->contract($data, ['contract_number' => 'EXP-FAR', 'end_date' => now()->addDays(120)->toDateString()]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No active contracts are expiring within 90 days.')
            ->assertDontSee('EXP-FAR');

        Carbon::setTestNow();
    }

    public function test_contracts_list_and_detail_show_expiry_warning_text(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data, ['contract_number' => 'EXP-DETAIL', 'end_date' => now()->addDays(1)->toDateString()]);

        $this->actingAs($owner)->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('EXP-DETAIL')
            ->assertSee('Expires in 1 day');

        $this->actingAs($owner)->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Contract expiry warning')
            ->assertSee('Expires in 1 day');

        Carbon::setTestNow();
    }

    public function test_today_boundary_shows_expires_today(): void
    {
        Carbon::setTestNow('2026-06-18');
        [$owner, $data] = $this->scenario();
        $contract = $this->contract($data, ['contract_number' => 'EXP-TODAY', 'end_date' => now()->toDateString()]);

        $this->assertSame('Expires today', $contract->expiryWarningText());

        $this->actingAs($owner)->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('Expires today');

        Carbon::setTestNow();
    }

    public function test_unauthorized_contract_access_remains_blocked(): void
    {
        [$owner, , $otherData] = $this->scenario();
        $otherContract = $this->contract($otherData, ['contract_number' => 'OTHER-DETAIL']);

        $this->actingAs($owner)->get(route('contracts.show', $otherContract))->assertForbidden();
    }

    private function scenario(): array
    {
        $organization = Organization::create(['name' => 'Expiry Org']);
        $otherOrganization = Organization::create(['name' => 'Other Expiry Org']);
        $owner = $this->user($organization, 'expiry-owner@example.com');

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
            'name' => "Expiry Building {$prefix}",
        ]);
        $unit = $this->unit($building, "{$prefix}-101");
        $secondUnit = $this->unit($building, "{$prefix}-102");
        $thirdUnit = $this->unit($building, "{$prefix}-103");
        $fourthUnit = $this->unit($building, "{$prefix}-104");
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Expiry Tenant {$prefix}",
        ]);

        return compact('organization', 'building', 'unit', 'secondUnit', 'thirdUnit', 'fourthUnit', 'tenant');
    }

    private function user(Organization $organization, string $email): User
    {
        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password',
            'role' => 'owner',
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
            'contract_number' => 'EXP-'.uniqid(),
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ], $overrides));
    }
}
