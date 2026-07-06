<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Organization;
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
            ->assertSee('Recommended setup order')
            ->assertSee('Add building')
            ->assertSee('Add units')
            ->assertSee('Add tenant')
            ->assertSee('Create contract')
            ->assertSee('Record payment')
            ->assertSee('Add expense')
            ->assertSee('View report')
            ->assertSee(route('buildings.create', absolute: false))
            ->assertSee(route('units.create', absolute: false))
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
            ->assertSee(route('quick-start.index', absolute: false));
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
            ->assertSee(__('app.quick_start.steps.building.title'))
            ->assertSee(__('app.quick_start.steps.contract.title'))
            ->assertSee(__('app.quick_start.steps.report.title'))
            ->assertDontSee('app.quick_start');
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
        $organization = Organization::create(['name' => 'Quick Start Organization '.$role]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Quick Start '.ucfirst($role),
            'email' => 'quick-start-'.$role.'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
