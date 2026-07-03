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

class ContractCreationJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_create_page_renders_guided_sections_in_english_and_arabic(): void
    {
        [$owner, $tenant, $unit] = $this->contractSetup('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('data-contract-step="unit"', false)
            ->assertSee('data-contract-step="tenant"', false)
            ->assertSee('data-contract-step="details"', false)
            ->assertSee('data-contract-step="review"', false)
            ->assertSee('name="contract_mode"', false)
            ->assertSee('data-contract-building-select', false)
            ->assertSee('data-contract-unit-select', false)
            ->assertSee('Step 1')
            ->assertSee('Select unit')
            ->assertSee('New contract for a vacant unit')
            ->assertSee('Future contract after an existing contract ends')
            ->assertSee('Select tenant')
            ->assertSee('Review payment schedule summary')
            ->assertSee($tenant->full_name)
            ->assertSee('Select a building first to see available units.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.create'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('contracts.form.step_unit_title'))
            ->assertSee(__('contracts.form.step_tenant_title'))
            ->assertSee(__('contracts.form.step_review_title'))
            ->assertSee(__('contracts.form.mode_vacant'))
            ->assertSee(__('contracts.form.mode_future'));
    }

    public function test_building_selector_appears_before_unit_selector(): void
    {
        [$owner] = $this->contractSetup('owner');

        $response = $this->actingAs($owner)
            ->get(route('contracts.create'));

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, 'data-contract-unit-select'),
            strpos($content, 'data-contract-building-select')
        );
    }

    public function test_default_mode_lists_only_vacant_units_from_selected_building_as_unit_numbers(): void
    {
        [$owner, , $vacantUnit] = $this->contractSetup('owner');
        $building = $vacantUnit->building;
        $occupiedUnit = $this->occupiedUnit($owner->organization, $building, 'CJ-201');
        $otherBuilding = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Other Contract Journey Building',
            'location' => 'Sharjah',
        ]);
        $otherVacantUnit = Unit::create([
            'building_id' => $otherBuilding->id,
            'unit_number' => 'CJ-999',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2600,
        ]);

        $response = $this->actingAs($owner)
            ->get(route('contracts.create', ['building_id' => $building->id]));

        $response->assertOk()
            ->assertDontSee($vacantUnit->building->name.' / '.$vacantUnit->unit_number, false)
            ->assertDontSee(__('contracts.availability.occupied_until', ['date' => '2026-12-31']));

        $unitOptions = $this->unitSelectOptions($response->getContent());

        $this->assertSame($vacantUnit->unit_number, $unitOptions[(string) $vacantUnit->id] ?? null);
        $this->assertArrayNotHasKey((string) $occupiedUnit->id, $unitOptions);
        $this->assertArrayNotHasKey((string) $otherVacantUnit->id, $unitOptions);
    }

    public function test_future_mode_lists_occupied_units_with_available_after_label(): void
    {
        [$owner, , $vacantUnit] = $this->contractSetup('owner');
        $building = $vacantUnit->building;
        $occupiedUnit = $this->occupiedUnit($owner->organization, $building, 'CJ-301');

        $response = $this->actingAs($owner)
            ->get(route('contracts.create', [
                'building_id' => $building->id,
                'contract_mode' => 'future',
            ]));

        $response->assertOk();

        $unitOptions = $this->unitSelectOptions($response->getContent());

        $this->assertSame($occupiedUnit->unit_number.' - Available after 2027-07-31', $unitOptions[(string) $occupiedUnit->id] ?? null);
        $this->assertArrayNotHasKey((string) $vacantUnit->id, $unitOptions);
    }

    public function test_owner_can_create_contract_and_payment_schedule_is_generated(): void
    {
        [$owner, $tenant, $unit] = $this->contractSetup('owner');

        $response = $this->actingAs($owner)
            ->post(route('contracts.store'), $this->contractPayload($tenant, $unit));

        $contract = Contract::where('tenant_id', $tenant->id)->where('unit_id', $unit->id)->firstOrFail();

        $response
            ->assertRedirect(route('contracts.show', $contract))
            ->assertSessionHas('status', __('contracts.form.created_success'));

        $this->assertSame(12, $contract->payments()->count());
    }

    public function test_manager_can_create_contract_and_restricted_roles_are_blocked(): void
    {
        [$manager, $tenant, $unit] = $this->contractSetup('manager');

        $this->actingAs($manager)
            ->post(route('contracts.store'), $this->contractPayload($tenant, $unit))
            ->assertRedirect();

        foreach (['accountant', 'caretaker'] as $role) {
            [$user] = $this->contractSetup($role, "Contract Journey {$role}");

            $this->actingAs($user)->get(route('contracts.create'))->assertForbidden();
        }
    }

    public function test_rented_or_unavailable_unit_validation_message_is_clear(): void
    {
        [$owner, $tenant, $unit] = $this->contractSetup('owner');

        Contract::create($this->existingContractAttributes($owner->organization, $tenant, $unit));

        $this->actingAs($owner)
            ->from(route('contracts.create'))
            ->post(route('contracts.store'), $this->contractPayload($tenant, $unit))
            ->assertRedirect(route('contracts.create'))
            ->assertSessionHasErrors([
                'unit_id' => __('contracts.validation.overlap'),
            ]);
    }

    public function test_future_contract_after_existing_contract_end_is_allowed(): void
    {
        [$owner, $tenant, $unit] = $this->contractSetup('owner');
        Contract::create($this->existingContractAttributes($owner->organization, $tenant, $unit));

        $futureTenant = Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => 'Future Contract Tenant',
            'phone' => '0500000002',
        ]);

        $response = $this->actingAs($owner)
            ->post(route('contracts.store'), array_merge($this->contractPayload($futureTenant, $unit), [
                'start_date' => '2027-08-01',
                'end_date' => '2028-07-31',
            ]));

        $futureContract = Contract::where('tenant_id', $futureTenant->id)->firstOrFail();

        $response->assertRedirect(route('contracts.show', $futureContract));
        $this->assertSame(12, $futureContract->payments()->count());
    }

    public function test_renewal_flow_keeps_fixed_unit_without_mode_selector(): void
    {
        Carbon::setTestNow('2027-06-15');
        [$owner, $tenant, $unit] = $this->contractSetup('owner');
        $contract = Contract::create($this->existingContractAttributes($owner->organization, $tenant, $unit));

        try {
            $this->actingAs($owner)
                ->get(route('contracts.create', ['renew_from' => $contract->id]))
                ->assertOk()
                ->assertSee(__('contracts.form.unit_fixed_renewal'))
                ->assertDontSee('name="contract_mode"', false)
                ->assertDontSee('data-contract-building-select', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function contractSetup(string $role, string $organizationName = 'Contract Journey Organization'): array
    {
        $organization = Organization::create(['name' => $organizationName]);
        $user = User::create([
            'organization_id' => $organization->id,
            'name' => "Contract Journey {$role}",
            'email' => 'contract-journey-'.$role.'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Contract Journey Building',
            'location' => 'Dubai',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'CJ-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2500,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Contract Journey Tenant',
            'phone' => '0500000001',
        ]);

        return [$user, $tenant, $unit];
    }

    private function contractPayload(Tenant $tenant, Unit $unit): array
    {
        return [
            'tenant_mode' => 'existing',
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'rent_amount' => 2500,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
            'notes' => 'Created from guided contract journey test.',
        ];
    }

    private function existingContractAttributes(Organization $organization, Tenant $tenant, Unit $unit): array
    {
        return [
            'organization_id' => $organization->id,
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'contract_number' => 'CJ-EXISTING-001',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'rent_amount' => 2500,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 500,
            'status' => 'active',
        ];
    }

    private function occupiedUnit(Organization $organization, Building $building, string $unitNumber): Unit
    {
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $unitNumber,
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 2800,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => "Tenant for {$unitNumber}",
            'phone' => '0500000999',
        ]);

        Contract::create($this->existingContractAttributes($organization, $tenant, $unit));

        return $unit;
    }

    private function unitSelectOptions(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $options = [];
        $xpath = new \DOMXPath($dom);

        foreach ($xpath->query('//select[@name="unit_id"]/option') as $option) {
            $value = $option->getAttribute('value');

            if ($value === '') {
                continue;
            }

            $options[$value] = trim((string) preg_replace('/\s+/', ' ', $option->textContent));
        }

        return $options;
    }
}
