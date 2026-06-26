<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_defaults_to_english_and_can_switch_between_supported_locales(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Property Manager')
            ->assertSee('Login')
            ->assertSee('data-language-switcher', false)
            ->assertSee($this->unicode('\u0627\u0644\u0639\u0631\u0628\u064a\u0629'))
            ->assertSee($this->unicode('\u09ac\u09be\u0982\u09b2\u09be'))
            ->assertSee($this->unicode('\u0627\u0631\u062f\u0648'))
            ->assertSee($this->unicode('\u0939\u093f\u0928\u094d\u0926\u0940'));

        $this->from(route('login'))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'ar');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.name'))
            ->assertSee(__('app.auth.login'))
            ->assertDontSee('auth.login')
            ->assertDontSee('app.auth.login')
            ->assertSee('English');

        $this->from(route('login'))
            ->post(route('locale.switch', 'en'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Login');

        $this->from(route('login'))
            ->post(route('locale.switch', 'ur'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'ur');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="ur" dir="rtl">', false)
            ->assertSee($this->unicode('\u0627\u0631\u062f\u0648'))
            ->assertSee(__('app.auth.login'))
            ->assertDontSee('app.auth.login');

        foreach (['bn' => 'ltr', 'hi' => 'ltr'] as $locale => $direction) {
            $this->withSession(['locale' => $locale])
                ->get(route('login'))
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'" dir="'.$direction.'">', false)
                ->assertSee(__('app.auth.login'))
                ->assertDontSee('app.auth.login');
        }
    }

    public function test_unsupported_locale_is_rejected_without_changing_the_session(): void
    {
        $this->withSession(['locale' => 'en'])
            ->post('/locale/fr')
            ->assertNotFound()
            ->assertSessionHas('locale', 'en');
    }

    public function test_authenticated_dashboard_supports_languages_and_preserves_navigation_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Dashboard')
            ->assertSee('Collected this month')
            ->assertSee('Needs your attention')
            ->assertSee('Quick actions')
            ->assertSee('Users')
            ->assertSee('Activity')
            ->assertSee('data-language-switcher', false)
            ->assertSee('rel="manifest"', false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('rel="apple-touch-icon"', false);

        $this->actingAs($owner)
            ->from(route('dashboard'))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('locale', 'ar');

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.dashboard.title'))
            ->assertSee(__('app.dashboard.collected_this_month'))
            ->assertSee(__('app.dashboard.needs_attention'))
            ->assertSee(__('app.dashboard.quick_actions'))
            ->assertSee(__('app.navigation.users'))
            ->assertSee(__('app.navigation.activity'))
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('data-language-switcher', false);

        foreach ([
            'bn' => 'ltr',
            'ur' => 'rtl',
            'hi' => 'ltr',
        ] as $locale => $direction) {
            $this->actingAs($owner)
                ->withSession(['locale' => $locale])
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'" dir="'.$direction.'">', false)
                ->assertSee(__('app.dashboard.title'))
                ->assertSee(__('app.navigation.payments'))
                ->assertDontSee('app.dashboard.title')
                ->assertDontSee('app.navigation.payments');
        }

        $this->actingAs($manager)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(url('users'), false)
            ->assertDontSee(url('activity-logs'), false);

        $this->actingAs($manager)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertDontSee(url('users'), false)
            ->assertDontSee(url('activity-logs'), false);
    }

    public function test_existing_urls_and_service_worker_registration_remain_unchanged(): void
    {
        $this->assertSame('/', route('dashboard', absolute: false));
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/contracts', route('contracts.index', absolute: false));
        $this->assertSame('/payments', route('payments.index', absolute: false));
        $this->assertSame('/locale/ar', route('locale.switch', 'ar', absolute: false));
        $this->assertSame('/locale/bn', route('locale.switch', 'bn', absolute: false));
        $this->assertSame('/locale/ur', route('locale.switch', 'ur', absolute: false));
        $this->assertSame('/locale/hi', route('locale.switch', 'hi', absolute: false));

        $frontend = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString(".register('/service-worker.js', { scope: '/' })", $frontend);
    }

    private function unicode(string $escaped): string
    {
        return json_decode('"'.$escaped.'"', true, flags: JSON_THROW_ON_ERROR);
    }
}
