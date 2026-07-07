<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FasterTenantContractSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_show_links_to_contract_create_for_available_unit_and_hides_restricted_roles(): void
    {
        [$owner, $tenant, $unit] = $this->makeSetupRecords('owner');
        [$manager] = $this->makeSetupRecords('manager', 'Manager Contract Setup Organization');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('Create contract for this unit')
            ->assertSee(route('contracts.create', ['unit_id' => $unit->id], absolute: false));

        $this->actingAs($manager)
            ->withSession(['locale' => 'en'])
            ->get(route('units.show', $unit))
            ->assertForbidden();

        $managerUnit = $this->unitFor($manager->organization_id, 'MGR-101');
        $this->actingAs($manager)
            ->withSession(['locale' => 'en'])
            ->get(route('units.show', $managerUnit))
            ->assertOk()
            ->assertSee('Create contract for this unit')
            ->assertSee(route('contracts.create', ['unit_id' => $managerUnit->id], absolute: false));

        foreach (['accountant', 'caretaker'] as $role) {
            [$restricted] = $this->makeSetupRecords($role, "Restricted {$role} Contract Setup Organization");

            $this->actingAs($restricted)
                ->get(route('units.show', $unit))
                ->assertForbidden();
        }
    }

    public function test_unavailable_unit_show_uses_neutral_hint_instead_of_contract_action(): void
    {
        [$owner, , $unit] = $this->makeSetupRecords('owner');
        $unit->update(['status' => 'rented']);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('This unit is not currently available for a new contract.')
            ->assertDontSee(route('contracts.create', ['unit_id' => $unit->id], absolute: false));
    }

    public function test_tenant_show_links_to_contract_create_for_authorized_users_only(): void
    {
        [$owner, $tenant] = $this->makeSetupRecords('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Create contract for this tenant')
            ->assertSee(route('contracts.create', ['tenant_id' => $tenant->id], absolute: false));

        foreach (['accountant', 'caretaker'] as $role) {
            [$restricted] = $this->makeSetupRecords($role, "Restricted Tenant {$role} Contract Setup Organization");

            $this->actingAs($restricted)
                ->get(route('tenants.show', $tenant))
                ->assertForbidden();
        }
    }

    public function test_contract_create_preselects_owned_unit_and_tenant_but_not_cross_organization_records(): void
    {
        [$owner, $tenant, $unit] = $this->makeSetupRecords('owner');
        [$otherOwner, $otherTenant, $otherUnit] = $this->makeSetupRecords('owner', 'Other Contract Setup Organization');

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create', [
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
            ]));

        $response->assertOk()
            ->assertSee('data-contract-unit-select', false)
            ->assertSee('data-selected-unit="'.$unit->id.'"', false);

        $this->assertSelectOptionSelected($response->getContent(), 'building_id', (string) $unit->building_id);
        $this->assertSelectOptionSelected($response->getContent(), 'unit_id', (string) $unit->id);
        $this->assertSelectOptionSelected($response->getContent(), 'tenant_id', (string) $tenant->id);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create', [
                'unit_id' => $otherUnit->id,
                'tenant_id' => $otherTenant->id,
            ]))
            ->assertOk()
            ->assertDontSee($otherUnit->unit_number)
            ->assertDontSee($otherTenant->full_name)
            ->assertDontSee('data-selected-unit="'.$otherUnit->id.'"', false);

        $this->actingAs($otherOwner)
            ->withSession(['locale' => 'en'])
            ->get(route('contracts.create', [
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
            ]))
            ->assertOk()
            ->assertDontSee($unit->unit_number)
            ->assertDontSee($tenant->full_name);
    }

    public function test_tenant_create_can_save_normally_or_redirect_to_contract_create(): void
    {
        [$owner] = $this->makeSetupRecords('owner');

        $normalResponse = $this->actingAs($owner)
            ->post(route('tenants.store'), $this->tenantPayload('Normal Tenant Setup'))
            ->assertRedirect();

        $normalTenant = Tenant::where('full_name', 'Normal Tenant Setup')->firstOrFail();
        $normalResponse->assertRedirect(route('tenants.show', $normalTenant));

        $response = $this->actingAs($owner)
            ->post(route('tenants.store'), $this->tenantPayload('Contract Tenant Setup') + [
                'after_save' => 'create_contract',
            ]);

        $tenant = Tenant::where('full_name', 'Contract Tenant Setup')->firstOrFail();
        $response->assertRedirect(route('contracts.create', ['tenant_id' => $tenant->id]));
    }

    public function test_contract_creation_guidance_and_payment_schedule_still_render(): void
    {
        [$owner, $tenant, $unit] = $this->makeSetupRecords('owner');

        $this->actingAs($owner)
            ->followingRedirects()
            ->post(route('contracts.store'), $this->contractPayload($tenant, $unit))
            ->assertOk()
            ->assertSee('Contract created successfully. You can now review the payment schedule.')
            ->assertSee('View payments');

        $contract = Contract::where('tenant_id', $tenant->id)->where('unit_id', $unit->id)->firstOrFail();
        $this->assertSame(12, $contract->payments()->count());
    }

    public function test_arabic_labels_render_for_new_contract_setup_actions(): void
    {
        [$owner, $tenant, $unit] = $this->makeSetupRecords('owner');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee(__('contracts.actions.create_for_unit'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee(__('contracts.actions.create_for_tenant'));

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('tenants.create'))
            ->assertOk()
            ->assertSee(__('tenants.actions.save_and_create_contract'));
    }

    private function makeSetupRecords(string $role, string $organizationName = 'Faster Contract Setup Organization'): array
    {
        $organization = Organization::create(['name' => $organizationName.' '.uniqid()]);
        $user = User::create([
            'organization_id' => $organization->id,
            'name' => "Faster Setup {$role}",
            'email' => 'faster-setup-'.$role.'-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Faster Setup Building',
            'location' => 'Riyadh',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'FS-'.strtoupper(substr(uniqid(), -5)),
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2500,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Faster Setup Tenant '.uniqid(),
            'phone' => '0500000000',
        ]);

        return [$user, $tenant, $unit];
    }

    private function unitFor(int $organizationId, string $unitNumber): Unit
    {
        $building = Building::create([
            'organization_id' => $organizationId,
            'name' => 'Manager Faster Setup Building',
            'location' => 'Riyadh',
        ]);

        return Unit::create([
            'building_id' => $building->id,
            'unit_number' => $unitNumber,
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2600,
        ]);
    }

    private function tenantPayload(string $name): array
    {
        return [
            'full_name' => $name,
            'phone' => '0501112222',
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'id_number' => 'ID-'.uniqid(),
            'nationality' => 'Saudi',
            'notes' => 'Tenant created from faster setup test.',
        ];
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
            'notes' => 'Created from faster setup test.',
        ];
    }

    private function assertSelectOptionSelected(string $html, string $selectName, string $value): void
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $option = $xpath->query('//select[@name="'.$selectName.'"]/option[@value="'.$value.'"]')->item(0);

        $this->assertNotNull($option, "Expected {$selectName} option {$value} to exist.");
        $this->assertTrue($option->hasAttribute('selected'), "Expected {$selectName} option {$value} to be selected.");
    }
}
