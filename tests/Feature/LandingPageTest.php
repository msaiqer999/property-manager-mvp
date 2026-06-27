<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_public_landing_page_at_root(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Save time and organize your properties from one place')
            ->assertSee(route('login'), false)
            ->assertSee('Create account')
            ->assertDontSee('landing.hero_title');
    }

    public function test_landing_page_uses_arabic_locale(): void
    {
        app()->setLocale('ar');

        $this->withSession(['locale' => 'ar'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('landing.hero_title'))
            ->assertSee(__('landing.login'))
            ->assertDontSee('landing.hero_title');
    }

    public function test_landing_page_uses_safe_fallback_translations_for_operational_locales(): void
    {
        foreach (['bn' => 'ltr', 'ur' => 'rtl', 'hi' => 'ltr'] as $locale => $direction) {
            app()->setLocale($locale);

            $this->withSession(['locale' => $locale])
                ->get('/')
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'" dir="'.$direction.'">', false)
                ->assertSee(__('landing.hero_title'))
                ->assertSee(__('landing.hero_subtitle'))
                ->assertSee(__('landing.benefits_title'))
                ->assertSee(__('landing.benefits')[0])
                ->assertSee(__('landing.cta_body'))
                ->assertSee(__('landing.plans_soon'))
                ->assertDontSee('Save time and organize your properties from one place')
                ->assertDontSee('Keep rent, contracts, receipts, expenses, and follow-up in one calm place.')
                ->assertDontSee('Packages will be available soon.')
                ->assertDontSee('landing.hero_title');
        }
    }

    public function test_landing_page_shows_demo_link_when_registration_is_disabled(): void
    {
        Config::set('app.registration_enabled', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('Request a demo')
            ->assertDontSee(route('register'), false);
    }

    public function test_authenticated_root_dashboard_login_and_dashboard_alias_are_not_broken(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/')
            ->assertOk()
            ->assertSee('data-mobile-owner-dashboard', false);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-mobile-owner-dashboard', false);

        auth()->logout();

        $this->get('/login')
            ->assertOk()
            ->assertSee(__('app.auth.login'));
    }
}
