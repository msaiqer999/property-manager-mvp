<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Organization;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GlobalReadinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GlobalReadinessFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_can_assign_organization_country_defaults(): void
    {
        Config::set('app.registration_enabled', true);
        $this->seed(GlobalReadinessSeeder::class);

        $indonesia = Country::where('code', 'ID')->firstOrFail();

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Where are the properties you want to manage located?')
            ->assertSee('Indonesia')
            ->assertSee('name="country_id"', false);

        $this->post(route('register'), [
            'organization_name' => 'Jakarta Portfolio',
            'country_id' => $indonesia->id,
            'name' => 'Jakarta Owner',
            'email' => 'jakarta-owner@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('dashboard'));

        $organization = Organization::where('name', 'Jakarta Portfolio')->firstOrFail();

        $this->assertSame($indonesia->id, $organization->country_id);
        $this->assertSame('IDR', $organization->currency_code);
        $this->assertSame('id', $organization->locale);
        $this->assertSame('Asia/Jakarta', $organization->timezone);
        $this->assertSame('IDR', $organization->effectiveCurrencyCode());
    }

    public function test_registration_requires_active_country_reference_data_before_creating_accounts(): void
    {
        Config::set('app.registration_enabled', true);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Country setup is not ready yet.')
            ->assertDontSee('name="country_id"', false)
            ->assertDontSee('name="email"', false);

        $this->from(route('register'))->post(route('register'), [
            'organization_name' => 'Missing Country Setup Org',
            'name' => 'Missing Country Setup Owner',
            'email' => 'missing-country-setup@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors(['country_id']);

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_organization_can_use_country_default_currency_without_override(): void
    {
        Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KES',
            'decimal_places' => 2,
        ]);

        $kenya = Country::create([
            'name' => 'Kenya',
            'code' => 'KE',
            'default_currency_code' => 'KES',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Nairobi',
        ]);

        $organization = Organization::create([
            'name' => 'Nairobi Rentals',
            'country_id' => $kenya->id,
        ]);

        $this->assertNull($organization->currency_code);
        $this->assertSame('KES', $organization->effectiveCurrencyCode());
    }

    public function test_model_currency_fallback_uses_country_or_config_without_hardcoded_aed(): void
    {
        Config::set('app.fallback_currency_code', null);

        $organization = Organization::create(['name' => 'Unconfigured Organization']);

        $this->assertNull($organization->effectiveCurrencyCode());

        Config::set('app.fallback_currency_code', 'ZZZ');

        $this->assertSame('ZZZ', $organization->fresh()->effectiveCurrencyCode());

        Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KES',
            'decimal_places' => 2,
        ]);

        $kenya = Country::create([
            'name' => 'Kenya',
            'code' => 'KE',
            'default_currency_code' => 'KES',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Nairobi',
        ]);

        $countryOrganization = Organization::create([
            'name' => 'Country Default Organization',
            'country_id' => $kenya->id,
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'country_id' => $kenya->id,
            'name' => 'Country Default Building',
        ]);

        $modelSource = file_get_contents(app_path('Models/Organization.php'))
            .file_get_contents(app_path('Models/Building.php'));

        $this->assertSame('KES', $countryOrganization->effectiveCurrencyCode());
        $this->assertSame('KES', $building->effectiveCurrencyCode());
        $this->assertStringNotContainsString("'AED'", $modelSource);
        $this->assertStringNotContainsString('"AED"', $modelSource);
    }

    public function test_global_readiness_seeder_creates_required_countries_and_currencies_idempotently(): void
    {
        $this->seed(GlobalReadinessSeeder::class);

        foreach ([
            'AE' => 'AED',
            'ID' => 'IDR',
            'SA' => 'SAR',
            'KE' => 'KES',
            'TZ' => 'TZS',
            'MA' => 'MAD',
        ] as $countryCode => $currencyCode) {
            $this->assertDatabaseHas('countries', [
                'code' => $countryCode,
                'default_currency_code' => $currencyCode,
                'is_active' => true,
            ]);

            $this->assertDatabaseHas('currencies', [
                'code' => $currencyCode,
                'is_active' => true,
            ]);
        }

        $tables = ['countries', 'currencies', 'property_types', 'payment_methods', 'contract_templates', 'tax_settings'];
        $counts = collect($tables)
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])
            ->all();

        $this->seed(GlobalReadinessSeeder::class);

        $this->assertSame(
            $counts,
            collect($tables)
                ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])
                ->all()
        );
    }

    public function test_property_types_can_be_global_or_country_specific(): void
    {
        $this->seed(GlobalReadinessSeeder::class);

        $uae = Country::where('code', 'AE')->firstOrFail();
        $indonesia = Country::where('code', 'ID')->firstOrFail();

        $uaeTypes = PropertyType::availableForCountry($uae)
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $indonesiaTypes = PropertyType::availableForCountry($indonesia)
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $this->assertContains('house', $uaeTypes);
        $this->assertContains('villa', $uaeTypes);
        $this->assertContains('house', $indonesiaTypes);
        $this->assertContains('kos', $indonesiaTypes);
        $this->assertNotContains('kos', $uaeTypes);
        $this->assertDatabaseHas('currencies', ['code' => 'IDR', 'decimal_places' => 0]);
        $this->assertDatabaseHas('payment_methods', ['country_id' => $indonesia->id, 'code' => 'qris']);
        $this->assertDatabaseHas('payment_methods', ['code' => 'mpesa']);
        $this->assertDatabaseHas('contract_templates', ['country_id' => $indonesia->id, 'language' => 'id']);
        $this->assertDatabaseHas('tax_settings', ['country_id' => $uae->id, 'type' => 'manual_review', 'is_active' => false]);
        $this->assertDatabaseHas('property_types', ['country_id' => null, 'code' => 'house']);
        $this->assertDatabaseHas('property_types', ['country_id' => $indonesia->id, 'code' => 'ruko']);
    }

    public function test_user_preferred_locale_can_be_stored(): void
    {
        $organization = Organization::create(['name' => 'Locale Preference Organization']);

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => 'Locale Preference Owner',
            'email' => 'locale-preference-owner@example.com',
            'password' => 'password-123',
            'role' => 'owner',
            'preferred_locale' => 'sw',
        ]);

        $this->assertSame('sw', $user->fresh()->preferred_locale);
    }

    public function test_database_seeder_keeps_uae_demo_working_with_country_configuration(): void
    {
        Config::set('app.fallback_currency_code', 'ZZZ');

        $this->seed(DatabaseSeeder::class);

        $country = Country::where('code', 'AE')->firstOrFail();
        $organization = Organization::where('name', 'Abu Dhabi Small Properties')->firstOrFail();
        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame($country->id, $organization->country_id);
        $this->assertSame('AED', $organization->currency_code);
        $this->assertSame('en', $organization->locale);
        $this->assertSame('Asia/Dubai', $organization->timezone);
        $this->assertSame($organization->id, $owner->organization_id);
        $this->assertDatabaseHas('currencies', ['code' => 'AED', 'is_active' => true]);
        $this->assertDatabaseHas('contract_templates', ['country_id' => $country->id, 'language' => 'en', 'is_default' => true]);

        foreach ($organization->buildings as $building) {
            $this->assertSame($country->id, $building->country_id);
            $this->assertSame('AED', $building->currency_code);
            $this->assertSame('Asia/Dubai', $building->timezone);
        }

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-mobile-owner-dashboard', false)
            ->assertSee('<bdi>AED', false);
    }
}
