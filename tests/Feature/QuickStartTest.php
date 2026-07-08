<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_quick_start(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Quick Start')
            ->assertSee('Setup progress')
            ->assertSee('Setup: 0 of 6 steps completed')
            ->assertSee('Next step')
            ->assertSee('Not started')
            ->assertSee('Recommended setup order')
            ->assertSee('Testing the system? Open the closed beta guide.')
            ->assertSee('If anything is unclear, broken, or slow during testing, use the Feedback button.')
            ->assertSee('Add building')
            ->assertSee('Add units')
            ->assertSee('Add multiple units')
            ->assertSee('Add tenant')
            ->assertSee('Create contract')
            ->assertSee('Record payment')
            ->assertSee('Add expense')
            ->assertSee('View report')
            ->assertSee(route('buildings.create', absolute: false))
            ->assertSee(route('units.create', absolute: false))
            ->assertSee(route('units.bulk-create', absolute: false))
            ->assertSee(route('tenants.create', absolute: false))
            ->assertSee(route('contracts.create', absolute: false))
            ->assertSee(route('payments.index', absolute: false))
            ->assertSee(route('expenses.create', absolute: false))
            ->assertSee(route('reports.index', absolute: false))
            ->assertDontSee('app.quick_start');
    }

    public function test_guest_cannot_access_quick_start(): void
    {
        $this->get(route('quick-start.index'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_quick_start_entry_point_for_property_managers(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-quick-start', false)
            ->assertSee('Start setup')
            ->assertSee('Setup: 0 of 6 steps completed')
            ->assertSee(route('quick-start.index', absolute: false));
    }

    public function test_units_empty_state_without_buildings_guides_to_add_building_first(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('data-empty-state-units', false)
            ->assertSee('Add a building first')
            ->assertSee('Add building')
            ->assertSee(route('buildings.create', absolute: false))
            ->assertDontSee(route('units.bulk-create', absolute: false));
    }

    public function test_units_empty_state_with_buildings_prefers_add_multiple_units(): void
    {
        $owner = $this->user('owner');
        Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Units Empty State Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('data-empty-state-units', false)
            ->assertSee('Add multiple units')
            ->assertSee('Add unit')
            ->assertSee(route('units.bulk-create', absolute: false))
            ->assertSee(route('units.create', absolute: false));
    }

    public function test_quick_start_renders_arabic_labels(): void
    {
        $owner = $this->user('owner');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.quick_start.title'))
            ->assertSee(__('app.quick_start.progress_title'))
            ->assertSee(__('app.quick_start.next_step'))
            ->assertSee(__('app.quick_start.steps.building.title'))
            ->assertSee(__('app.quick_start.steps.contract.title'))
            ->assertSee(__('app.quick_start.steps.report.title'))
            ->assertDontSee('app.quick_start');
    }

    public function test_quick_start_recommends_add_building_when_no_buildings_exist(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 0 of 6 steps completed')
            ->assertSee('href="'.route('buildings.create').'"', false)
            ->assertSee('Add building');
    }

    public function test_quick_start_recommends_add_multiple_units_after_building_exists(): void
    {
        $owner = $this->user('owner');
        Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Quick Start Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 1 of 6 steps completed')
            ->assertSee('href="'.route('units.bulk-create').'"', false)
            ->assertSee('Add multiple units');
    }

    public function test_quick_start_progress_updates_through_units_tenants_contracts_payments_and_expenses(): void
    {
        $owner = $this->user('owner');
        [$building, $unit, $tenant, $contract] = $this->setupThroughContract($owner);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 4 of 6 steps completed')
            ->assertSee('Open payments');

        Payment::create([
            'organization_id' => $owner->organization_id,
            'contract_id' => $contract->id,
            'due_date' => now()->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 1000,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 5 of 6 steps completed')
            ->assertSee('Add expense');

        Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'maintenance',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 6 of 6 steps completed')
            ->assertSee('View reports');
    }

    public function test_quick_start_recommends_add_tenant_when_units_exist_but_no_tenants(): void
    {
        $owner = $this->user('owner');
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Tenant Recommendation Building',
            'location' => 'Riyadh',
        ]);
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'TR-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 2 of 6 steps completed')
            ->assertSee('href="'.route('tenants.create').'"', false)
            ->assertSee('Add tenant');
    }

    public function test_quick_start_recommends_create_contract_when_units_and_tenants_exist(): void
    {
        $owner = $this->user('owner');
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Contract Recommendation Building',
            'location' => 'Riyadh',
        ]);
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'CR-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);
        Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => 'Contract Recommendation Tenant',
            'phone' => '0500000000',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Setup: 3 of 6 steps completed')
            ->assertSee('href="'.route('contracts.create').'"', false)
            ->assertSee('Create contract');
    }

    public function test_quick_start_shows_pilot_readiness_for_owner_and_updates_checks(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('data-pilot-readiness', false)
            ->assertSee('Pilot readiness')
            ->assertSee('Needs setup')
            ->assertSee('Feedback channel ready')
            ->assertSee('During the pilot, use the Feedback button whenever something is unclear, broken, or slow.');

        [$building, $unit, $tenant, $contract] = $this->setupThroughContract($owner);
        Payment::create([
            'organization_id' => $owner->organization_id,
            'contract_id' => $contract->id,
            'due_date' => now()->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 0,
            'status' => 'pending',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Ready for pilot')
            ->assertSee('At least one building exists')
            ->assertSee('Payments are generated');
    }

    public function test_building_show_includes_add_multiple_units_link_and_stays_scoped(): void
    {
        $owner = $this->user('owner');
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Scoped Building Show',
            'location' => 'Riyadh',
        ]);
        $otherOwner = $this->user('owner');
        $otherBuilding = Building::create([
            'organization_id' => $otherOwner->organization_id,
            'name' => 'Other Building Show',
            'location' => 'Dubai',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.show', $building))
            ->assertOk()
            ->assertSee('data-building-empty-units', false)
            ->assertSee(route('units.bulk-create', ['building_id' => $building->id], absolute: false))
            ->assertSee('Add multiple units');

        $this->actingAs($owner)
            ->get(route('buildings.show', $otherBuilding))
            ->assertForbidden();
    }

    public function test_buildings_empty_state_appears_only_when_there_are_no_buildings(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('data-empty-state-buildings', false)
            ->assertSee('No buildings yet')
            ->assertSee('Add building');

        Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'First Real Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('First Real Building')
            ->assertDontSee('data-empty-state-buildings', false);
    }

    public function test_restricted_user_does_not_get_forbidden_setup_links(): void
    {
        $caretaker = $this->user('caretaker');

        $this->actingAs($caretaker)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('Not available for your role')
            ->assertDontSee(route('buildings.create', absolute: false))
            ->assertDontSee(route('contracts.create', absolute: false))
            ->assertSee(route('payments.index', absolute: false));
    }

    private function user(string $role): User
    {
        static $sequence = 0;
        $sequence++;

        $organization = Organization::create(['name' => 'Quick Start Organization '.$role]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Quick Start '.ucfirst($role),
            'email' => 'quick-start-'.$role.'-'.$sequence.'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function setupThroughContract(User $owner): array
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Quick Start Progress Building',
            'location' => 'Riyadh',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'QS-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $owner->organization_id,
            'full_name' => 'Quick Start Tenant',
            'phone' => '0500000000',
        ]);
        $contract = Contract::create([
            'organization_id' => $owner->organization_id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'QS-2026-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        return [$building, $unit, $tenant, $contract];
    }
}
