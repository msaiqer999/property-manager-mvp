<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_uses_brand_identity_and_accessible_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('app-identity', false)
            ->assertSee('brand-mark', false)
            ->assertSee('auth-card', false)
            ->assertSee('form-control', false)
            ->assertSee('btn-primary', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee(__('app.auth.login'))
            ->assertDontSee('app.auth.login');
    }

    public function test_authenticated_layout_uses_visual_identity_in_english_and_arabic(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('bg-brand-background', false)
            ->assertSee('app-header', false)
            ->assertSee('brand-mark', false)
            ->assertSee('app-nav-link-active', false)
            ->assertSee('data-mobile-navigation', false)
            ->assertSee(__('app.dashboard.title'))
            ->assertDontSee('app.dashboard.title');

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('app-header', false)
            ->assertSee('app-nav-link-active', false)
            ->assertSee('data-mobile-navigation', false)
            ->assertSee(__('app.dashboard.title'))
            ->assertDontSee('app.dashboard.title');
    }

    public function test_closed_beta_pages_render_with_shared_identity_tokens(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $closedBetaPages = [
            route('quick-start.index') => [
                __('app.quick_start.title'),
                __('app.quick_start.pilot.checks.feedback'),
            ],
            route('pilot-guide.index') => [
                __('app.pilot_guide.title'),
                __('app.pilot_guide.feedback_button'),
                __('app.pilot_guide.steps.feedback.title'),
            ],
            route('feedback.index') => [
                __('feedback.index_title'),
                __('feedback.empty_title'),
            ],
        ];

        foreach ($closedBetaPages as $url => $expectedLabels) {
            $response = $this->actingAs($owner)
                ->withSession(['locale' => 'en'])
                ->get($url)
                ->assertOk()
                ->assertSee('bg-brand-surface', false)
                ->assertSee('data-mobile-navigation', false)
                ->assertSee(__('feedback.button'))
                ->assertSee(__('feedback.title'))
                ->assertSee(__('feedback.message'))
                ->assertSee(__('feedback.page_url'))
                ->assertSee(__('feedback.screenshot_note'))
                ->assertSee(__('feedback.submit'));

            foreach ($expectedLabels as $expectedLabel) {
                $response->assertSee($expectedLabel);
            }

            foreach ([
                'app.quick_start.title',
                'app.quick_start.steps.building.title',
                'app.quick_start.pilot.checks.feedback',
                'app.pilot_guide.title',
                'app.pilot_guide.steps.feedback.title',
                'feedback.title',
                'feedback.index_title',
                'feedback.types.bug',
                'feedback.statuses.new',
            ] as $rawTranslationKey) {
                $response->assertDontSee($rawTranslationKey);
            }
        }
    }

    public function test_restricted_role_keeps_mobile_navigation_without_owner_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-navigation', false)
            ->assertSee('app-nav-link-active', false)
            ->assertSee(__('app.navigation.dashboard'))
            ->assertSee(__('app.navigation.payments'))
            ->assertDontSee(__('app.navigation.users'))
            ->assertDontSee(__('app.navigation.contracts'))
            ->assertDontSee(url('activity-logs'), false);
    }
}
