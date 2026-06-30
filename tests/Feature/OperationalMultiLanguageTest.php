<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalMultiLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_switcher_lists_operational_languages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-language-switcher', false)
            ->assertSee('data-language-dropdown', false)
            ->assertSee('data-language-option', false)
            ->assertSee('English')
            ->assertSee('Filipino (Tagalog)')
            ->assertSee($this->unicode('\u0627\u0644\u0639\u0631\u0628\u064a\u0629'))
            ->assertSee($this->unicode('\u09ac\u09be\u0982\u09b2\u09be'))
            ->assertSee($this->unicode('\u0627\u0631\u062f\u0648'))
            ->assertSee($this->unicode('\u0939\u093f\u0928\u094d\u0926\u0940'))
            ->assertDontSee('🇸🇦')
            ->assertDontSee('🇺🇸')
            ->assertDontSee('flag');
    }

    public function test_bengali_urdu_and_hindi_render_operational_dashboard_and_payments(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $cases = [
            'bn' => [
                'dir' => 'ltr',
                'dashboard' => $this->unicode('\u09a1\u09cd\u09af\u09be\u09b6\u09ac\u09cb\u09b0\u09cd\u09a1'),
                'payments' => $this->unicode('\u09aa\u09c7\u09ae\u09c7\u09a8\u09cd\u099f'),
                'attention' => $this->unicode('\u0986\u09aa\u09a8\u09be\u09b0 \u09ae\u09a8\u09cb\u09af\u09cb\u0997 \u09a6\u09b0\u0995\u09be\u09b0'),
                'record' => $this->unicode('\u09aa\u09c7\u09ae\u09c7\u09a8\u09cd\u099f \u09b0\u09c7\u0995\u09b0\u09cd\u09a1 \u0995\u09b0\u09c1\u09a8'),
            ],
            'ur' => [
                'dir' => 'rtl',
                'dashboard' => $this->unicode('\u0688\u06cc\u0634 \u0628\u0648\u0631\u0688'),
                'payments' => $this->unicode('\u0627\u062f\u0627\u0626\u06cc\u06af\u06cc\u0627\u06ba'),
                'attention' => $this->unicode('\u0622\u067e \u06a9\u06cc \u062a\u0648\u062c\u06c1 \u062f\u0631\u06a9\u0627\u0631 \u06c1\u06d2'),
                'record' => $this->unicode('\u0627\u062f\u0627\u0626\u06cc\u06af\u06cc \u0631\u06cc\u06a9\u0627\u0631\u0688 \u06a9\u0631\u06cc\u06ba'),
            ],
            'hi' => [
                'dir' => 'ltr',
                'dashboard' => $this->unicode('\u0921\u0948\u0936\u092c\u094b\u0930\u094d\u0921'),
                'payments' => $this->unicode('\u092d\u0941\u0917\u0924\u093e\u0928'),
                'attention' => $this->unicode('\u0906\u092a\u0915\u093e \u0927\u094d\u092f\u093e\u0928 \u091a\u093e\u0939\u093f\u090f'),
                'record' => $this->unicode('\u092d\u0941\u0917\u0924\u093e\u0928 \u0930\u093f\u0915\u0949\u0930\u094d\u0921 \u0915\u0930\u0947\u0902'),
            ],
        ];

        foreach ($cases as $locale => $text) {
            $this->actingAs($owner)
                ->withSession(['locale' => $locale])
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('<html lang="'.$locale.'" dir="'.$text['dir'].'">', false)
                ->assertSee($text['dashboard'])
                ->assertSee($text['payments'])
                ->assertSee($text['attention'])
                ->assertDontSee('app.navigation.payments')
                ->assertDontSee('app.dashboard.needs_attention');

            $this->actingAs($owner)
                ->withSession(['locale' => $locale])
                ->get(route('payments.index'))
                ->assertOk()
                ->assertSee($text['payments'])
                ->assertSee($text['record'])
                ->assertSee('data-mobile-payments-list', false)
                ->assertDontSee('payments.record_payment')
                ->assertDontSee('payments.columns.tenant');
        }
    }

    public function test_caretaker_permissions_do_not_change_in_operational_languages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        foreach (['bn', 'ur', 'hi'] as $locale) {
            $this->actingAs($caretaker)
                ->withSession(['locale' => $locale])
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('data-mobile-navigation', false)
                ->assertSee(url('payments'), false)
                ->assertDontSee(url('buildings'), false)
                ->assertDontSee(url('units'), false)
                ->assertDontSee(url('tenants'), false)
                ->assertDontSee(url('contracts'), false)
                ->assertDontSee(url('expenses'), false)
                ->assertDontSee(url('reports'), false)
                ->assertDontSee(url('users'), false)
                ->assertDontSee(url('activity-logs'), false)
                ->assertDontSee('Monthly income')
                ->assertDontSee('Monthly expenses')
                ->assertDontSee('Net profit');

            foreach ([
                route('buildings.index'),
                route('units.index'),
                route('tenants.index'),
                route('contracts.index'),
                route('expenses.index'),
                route('reports.index'),
                route('users.index'),
                route('activity-logs.index'),
            ] as $url) {
                $this->actingAs($caretaker)
                    ->withSession(['locale' => $locale])
                    ->get($url)
                    ->assertForbidden();
            }
        }
    }

    public function test_existing_english_and_arabic_locales_still_render(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Dashboard')
            ->assertSee('Payments');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.navigation.payments'));
    }

    private function unicode(string $escaped): string
    {
        return json_decode('"'.$escaped.'"', true, flags: JSON_THROW_ON_ERROR);
    }
}
