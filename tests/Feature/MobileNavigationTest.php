<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_accessible_mobile_and_desktop_navigation_with_active_state(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($owner)->get('/contracts');

        $response
            ->assertOk()
            ->assertSee('data-mobile-menu-control', false)
            ->assertSee('aria-label="Toggle navigation menu"', false)
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('class="group relative shrink-0 sm:hidden"', false)
            ->assertSee('data-desktop-navigation', false)
            ->assertSee('sm:flex', false)
            ->assertSee('aria-current="page" data-active-navigation', false);

        foreach (['Dashboard', 'Units', 'Payments', 'Contracts', 'Tenants', 'Expenses', 'Reports', 'Buildings', 'Users', 'Activity'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_restricted_role_does_not_gain_owner_navigation_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->get('/payments')
            ->assertOk()
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('data-desktop-navigation', false)
            ->assertSee('aria-current="page" data-active-navigation', false)
            ->assertDontSee(url('users'), false)
            ->assertDontSee(url('activity-logs'), false);
    }
}
