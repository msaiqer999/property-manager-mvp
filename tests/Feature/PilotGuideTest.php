<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_pilot_guide(): void
    {
        $this->get(route('pilot-guide.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_pilot_guide(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('pilot-guide.index'))
            ->assertOk()
            ->assertSee('Closed beta guide')
            ->assertSee('Recommended 30-minute test path')
            ->assertSee('Start from Quick Start')
            ->assertSee('Open Quick Start')
            ->assertSee('Add a building')
            ->assertSee('Add multiple units')
            ->assertSee('Add a tenant')
            ->assertSee('Create a contract')
            ->assertSee('Check generated payments')
            ->assertSee('Add an expense')
            ->assertSee('Open reports')
            ->assertSee('Send feedback if unclear, broken, or slow')
            ->assertSee(route('quick-start.index', absolute: false))
            ->assertSee('data-feedback-open', false)
            ->assertSee('Send feedback')
            ->assertDontSee('app.pilot_guide')
            ->assertDontSee('password')
            ->assertDontSee('secret')
            ->assertDontSee('.env');
    }

    public function test_arabic_pilot_guide_labels_render(): void
    {
        $owner = $this->user('owner');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('pilot-guide.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.pilot_guide.title'))
            ->assertSee(__('app.pilot_guide.path_title'))
            ->assertSee(__('app.pilot_guide.feedback_button'))
            ->assertSee(__('app.pilot_guide.steps.quick_start.title'))
            ->assertDontSee('app.pilot_guide');
    }

    public function test_dashboard_shows_pilot_guide_entry_point(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-pilot-guide', false)
            ->assertSee('Closed beta guide')
            ->assertSee(route('pilot-guide.index', absolute: false));
    }

    public function test_quick_start_shows_pilot_guide_entry_point(): void
    {
        $owner = $this->user('owner');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('quick-start.index'))
            ->assertOk()
            ->assertSee('data-quick-start-pilot-guide', false)
            ->assertSee('Testing the system? Open the closed beta guide.')
            ->assertSee(route('pilot-guide.index', absolute: false));
    }

    public function test_owner_and_manager_handover_checklist_is_visible(): void
    {
        foreach (['owner', 'manager'] as $role) {
            $this->actingAs($this->user($role))
                ->withSession(['locale' => 'en'])
                ->get(route('pilot-guide.index'))
                ->assertOk()
                ->assertSee('data-pilot-guide-handover', false)
                ->assertSee('Confirm pilot user account is created')
                ->assertSee('Review feedback inbox daily during pilot');
        }
    }

    public function test_accountant_and_caretaker_do_not_see_handover_or_forbidden_setup_links(): void
    {
        foreach (['accountant', 'caretaker'] as $role) {
            $response = $this->actingAs($this->user($role))
                ->withSession(['locale' => 'en'])
                ->get(route('pilot-guide.index'));

            $response->assertOk()
                ->assertDontSee('data-pilot-guide-handover', false)
                ->assertDontSee('Confirm pilot user account is created')
                ->assertDontSee(route('buildings.create', absolute: false))
                ->assertDontSee(route('units.bulk-create', absolute: false))
                ->assertDontSee(route('tenants.create', absolute: false))
                ->assertDontSee(route('contracts.create', absolute: false))
                ->assertDontSee(route('expenses.create', absolute: false))
                ->assertSee('Not available for your role');
        }
    }

    private function user(string $role): User
    {
        $organization = Organization::create([
            'name' => 'Pilot Guide Organization '.$role.' '.uniqid(),
        ]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => ucfirst($role).' Pilot User',
            'email' => $role.'-pilot-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }
}
