<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_closed_beta_information_pages_render_for_guests_in_english(): void
    {
        $pages = [
            'legal.privacy' => 'Privacy notice',
            'legal.terms' => 'Terms of use',
            'legal.beta' => 'Closed-beta notice',
        ];

        foreach ($pages as $route => $title) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($title)
                ->assertSee('controlled closed beta')
                ->assertSee('You retain ownership')
                ->assertSee('authorized application access')
                ->assertSee('does not collect, receive, hold, split, distribute, or transfer rent money')
                ->assertSee('does not provide legal, tax, accounting, or financial advice')
                ->assertSee('Support during the beta is limited')
                ->assertDontSee('legal.');
        }
    }

    public function test_public_closed_beta_information_pages_render_for_authenticated_users(): void
    {
        $user = $this->user();

        foreach (['legal.privacy', 'legal.terms', 'legal.beta'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee(__('legal.links.privacy'))
                ->assertSee(__('legal.links.terms'))
                ->assertSee(__('legal.links.beta'))
                ->assertDontSee('legal.');
        }
    }

    public function test_arabic_closed_beta_information_pages_render_rtl_without_raw_keys(): void
    {
        app()->setLocale('ar');

        foreach (['legal.privacy', 'legal.terms', 'legal.beta'] as $route) {
            $this->withSession(['locale' => 'ar'])
                ->get(route($route))
                ->assertOk()
                ->assertSee('<html lang="ar" dir="rtl">', false)
                ->assertSee(__('legal.eyebrow'))
                ->assertSee(__('legal.links.beta'))
                ->assertSee(__('legal.links.privacy'))
                ->assertSee(__('legal.links.terms'))
                ->assertDontSee('legal.');
        }
    }

    public function test_landing_login_and_authenticated_layout_link_to_closed_beta_information_pages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-landing-legal-links', false)
            ->assertSee(route('legal.beta', absolute: false), false)
            ->assertSee(route('legal.privacy', absolute: false), false)
            ->assertSee(route('legal.terms', absolute: false), false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-login-legal-links', false)
            ->assertSee(route('legal.beta', absolute: false), false)
            ->assertSee(route('legal.privacy', absolute: false), false)
            ->assertSee(route('legal.terms', absolute: false), false);

        $this->actingAs($this->user())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-app-legal-links', false)
            ->assertSee(route('legal.beta', absolute: false), false)
            ->assertSee(route('legal.privacy', absolute: false), false)
            ->assertSee(route('legal.terms', absolute: false), false);
    }

    public function test_pilot_guide_links_to_beta_information_and_password_change(): void
    {
        $this->actingAs($this->user())
            ->get(route('pilot-guide.index'))
            ->assertOk()
            ->assertSee('data-pilot-guide-beta-notice', false)
            ->assertSee(route('legal.beta', absolute: false), false)
            ->assertSee(route('legal.privacy', absolute: false), false)
            ->assertSee(route('legal.terms', absolute: false), false)
            ->assertSee(route('password.change', absolute: false), false)
            ->assertDontSee('legal.');
    }

    private function user(): User
    {
        $organization = Organization::create(['name' => 'Legal Test Organization']);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Legal Test Owner',
            'email' => 'legal-owner-'.uniqid().'@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
    }
}
