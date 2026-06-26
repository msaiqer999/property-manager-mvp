<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_without_buildings_sees_first_building_onboarding(): void
    {
        $owner = $this->ownerForNewOrganization('owner-onboarding-empty-buildings@example.com');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-buildings', false)
            ->assertSee('Start by adding your first building')
            ->assertSee('Add the building first, then add units, tenants, and contracts.')
            ->assertSee('href="'.route('buildings.create').'"', false)
            ->assertSee('Add building')
            ->assertDontSee('data-mobile-owner-dashboard', false)
            ->assertDontSee('data-dashboard-kpi-card', false)
            ->assertDontSee('data-attention-section', false)
            ->assertDontSee('data-quick-actions', false)
            ->assertDontSee('data-dashboard-secondary-lists', false);
    }

    public function test_owner_with_building_without_units_sees_unit_onboarding_actions(): void
    {
        $owner = $this->ownerForNewOrganization('owner-onboarding-empty-units@example.com');
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Onboarding Empty Units Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-units', false)
            ->assertSee('Add units for this building')
            ->assertSee('href="'.route('units.create', ['building_id' => $building->id]).'"', false)
            ->assertSee('href="'.route('buildings.units.bulk.create', $building).'"', false)
            ->assertSee('Add unit')
            ->assertSee('Add multiple units')
            ->assertDontSee('data-mobile-owner-dashboard', false)
            ->assertDontSee('data-dashboard-secondary-lists', false);
    }

    public function test_owner_with_units_without_contracts_sees_contract_onboarding_action(): void
    {
        $owner = $this->ownerForNewOrganization('owner-onboarding-empty-contracts@example.com');
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Onboarding Contract Building',
            'location' => 'Riyadh',
        ]);
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'ONB-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2500,
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-contracts', false)
            ->assertSee('Create your first contract to start tracking payments')
            ->assertSee('A contract connects the tenant to the unit and creates the payment schedule.')
            ->assertSee('href="'.route('contracts.create').'"', false)
            ->assertSee('Add contract')
            ->assertDontSee('data-mobile-owner-dashboard', false)
            ->assertDontSee('data-dashboard-secondary-lists', false);
    }

    public function test_owner_with_complete_demo_data_sees_current_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-mobile-owner-dashboard', false)
            ->assertSee('data-dashboard-kpi-card', false)
            ->assertSee('data-attention-section', false)
            ->assertSee('data-quick-actions', false)
            ->assertSee('data-dashboard-secondary-lists', false)
            ->assertDontSee('data-owner-onboarding-empty-buildings', false)
            ->assertDontSee('data-owner-onboarding-empty-units', false)
            ->assertDontSee('data-owner-onboarding-empty-contracts', false);
    }

    public function test_caretaker_does_not_see_owner_onboarding_or_forbidden_owner_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Latest payments')
            ->assertDontSee('data-owner-onboarding-empty-buildings', false)
            ->assertDontSee('data-owner-onboarding-empty-units', false)
            ->assertDontSee('data-owner-onboarding-empty-contracts', false)
            ->assertDontSee('Add building')
            ->assertDontSee('Add unit')
            ->assertDontSee('Add contract')
            ->assertDontSee('Reports')
            ->assertDontSee('Contracts')
            ->assertDontSee('Tenants')
            ->assertDontSee('Users')
            ->assertDontSee('Activity');
    }

    public function test_arabic_owner_onboarding_messages_render_for_empty_buildings(): void
    {
        $owner = $this->ownerForNewOrganization('owner-onboarding-arabic@example.com');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('data-owner-onboarding-empty-buildings', false)
            ->assertSee(__('app.dashboard.empty_no_buildings_title'))
            ->assertSee(__('app.dashboard.empty_no_buildings_body'));
    }

    private function ownerForNewOrganization(string $email): User
    {
        $organization = Organization::create(['name' => 'Owner Onboarding Organization '.$email]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner Onboarding User',
            'email' => $email,
            'password' => 'password',
            'role' => 'owner',
        ]);
    }
}
