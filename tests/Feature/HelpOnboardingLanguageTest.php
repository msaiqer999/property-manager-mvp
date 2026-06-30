<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpOnboardingLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_button_and_first_visit_guidance_markup_exist_on_main_authenticated_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        foreach ([
            route('dashboard') => 'dashboard',
            route('buildings.index') => 'buildings',
            route('units.index') => 'units',
            route('tenants.index') => 'tenants',
            route('contracts.index') => 'contracts',
            route('payments.index') => 'payments',
            route('expenses.index') => 'expenses',
            route('reports.index') => 'reports',
        ] as $url => $pageKey) {
            $this->actingAs($owner)
                ->get($url)
                ->assertOk()
                ->assertSee('data-help-open', false)
                ->assertSee('data-help-panel', false)
                ->assertSee('data-page-help-key="'.$pageKey.'"', false)
                ->assertSee('data-first-visit-label', false)
                ->assertSee('data-help-got-it', false)
                ->assertSee('data-help-dont-show', false)
                ->assertSee(__('app.help.button'))
                ->assertDontSee('app.help.pages.'.$pageKey);
        }
    }

    public function test_language_dropdown_lists_clean_language_names_without_flags(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-language-switcher', false)
            ->assertSee('data-language-dropdown', false)
            ->assertSee('data-language-option', false)
            ->assertSee('العربية')
            ->assertSee('English')
            ->assertSee('Filipino (Tagalog)')
            ->assertSee('اردو')
            ->assertSee('हिन्दी')
            ->assertSee('বাংলা')
            ->assertDontSee('🇦🇪')
            ->assertDontSee('🇸🇦')
            ->assertDontSee('🇺🇸')
            ->assertDontSee('🇵🇭')
            ->assertDontSee('flag');
    }

    public function test_existing_locale_switching_and_filipino_option_work(): void
    {
        $this->from(route('login'))
            ->post(route('locale.switch', 'ar'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'ar');

        $this->from(route('login'))
            ->post(route('locale.switch', 'en'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'en');

        $this->from(route('login'))
            ->post(route('locale.switch', 'fil'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('locale', 'fil');

        $this->withSession(['locale' => 'fil'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="fil" dir="ltr">', false)
            ->assertSee('Filipino (Tagalog)')
            ->assertDontSee('app.help.button');
    }
}
