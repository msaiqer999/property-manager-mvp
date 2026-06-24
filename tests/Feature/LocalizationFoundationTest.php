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
            ->assertSee('العربية');

        $this->from(route('login'))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'ar');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('المدير العقاري')
            ->assertSee('تسجيل الدخول')
            ->assertSee('English');

        $this->from(route('login'))
            ->post(route('locale.switch', 'en'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Login');
    }

    public function test_unsupported_locale_is_rejected_without_changing_the_session(): void
    {
        $this->withSession(['locale' => 'en'])
            ->post('/locale/fr')
            ->assertNotFound()
            ->assertSessionHas('locale', 'en');
    }

    public function test_authenticated_dashboard_supports_both_languages_and_preserves_navigation_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Dashboard')
            ->assertSee('Monthly income')
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
            ->assertSee('لوحة التحكم')
            ->assertSee('الدخل الشهري')
            ->assertSee('المستخدمون')
            ->assertSee('سجل النشاط')
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('data-language-switcher', false);

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
            ->assertDontSee('المستخدمون')
            ->assertDontSee('سجل النشاط');
    }

    public function test_existing_urls_and_service_worker_registration_remain_unchanged(): void
    {
        $this->assertSame('/', route('dashboard', absolute: false));
        $this->assertSame('/login', route('login', absolute: false));
        $this->assertSame('/contracts', route('contracts.index', absolute: false));
        $this->assertSame('/payments', route('payments.index', absolute: false));
        $this->assertSame('/locale/ar', route('locale.switch', 'ar', absolute: false));

        $frontend = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString(".register('/service-worker.js', { scope: '/' })", $frontend);
    }
}
