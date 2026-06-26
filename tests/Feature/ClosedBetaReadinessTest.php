<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosedBetaReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_core_journey_pages_and_receipt_pdf_are_reachable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = Building::where('organization_id', $owner->organization_id)->firstOrFail();
        $unit = Unit::whereHas('building', fn ($query) => $query->where('organization_id', $owner->organization_id))->firstOrFail();
        $tenant = Tenant::where('organization_id', $owner->organization_id)->firstOrFail();
        $contract = Contract::where('organization_id', $owner->organization_id)->firstOrFail();
        $payment = Payment::where('organization_id', $owner->organization_id)
            ->where('amount_paid', '>', 0)
            ->firstOrFail();

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee('data-mobile-owner-dashboard', false);
        $this->actingAs($owner)->get(route('buildings.index'))->assertOk()->assertSee('data-mobile-buildings-list', false);
        $this->actingAs($owner)->get(route('buildings.show', $building))->assertOk()->assertSee('data-building-actions', false);
        $this->actingAs($owner)->get(route('units.index'))->assertOk()->assertSee('data-mobile-units-list', false);
        $this->actingAs($owner)->get(route('units.show', $unit))->assertOk()->assertSee('data-unit-show-card', false);
        $this->actingAs($owner)->get(route('tenants.index'))->assertOk()->assertSee('data-mobile-tenants-list', false);
        $this->actingAs($owner)->get(route('tenants.show', $tenant))->assertOk()->assertSee('data-tenant-show-card', false);
        $this->actingAs($owner)->get(route('contracts.index'))->assertOk()->assertSee('data-mobile-contracts-list', false);
        $this->actingAs($owner)->get(route('contracts.show', $contract))->assertOk()->assertSee('data-contract-show-card', false);
        $this->actingAs($owner)->get(route('payments.index'))->assertOk()->assertSee('data-mobile-payments-list', false);
        $this->actingAs($owner)->get(route('payments.show', $payment))->assertOk()->assertSee('data-payment-action', false);

        $receiptResponse = $this->actingAs($owner)->get(route('payments.receipt', $payment));
        $receiptResponse->assertOk();
        $this->assertSame('application/pdf', $receiptResponse->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $receiptResponse->getContent());
    }

    public function test_owner_onboarding_states_have_clear_first_steps(): void
    {
        $ownerWithoutBuildings = $this->ownerForNewOrganization('closed-beta-no-buildings@example.com');

        $this->actingAs($ownerWithoutBuildings)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-buildings', false)
            ->assertSee('Start by adding your first building')
            ->assertDontSee('data-dashboard-kpi-card', false);

        $ownerWithoutUnits = $this->ownerForNewOrganization('closed-beta-no-units@example.com');
        $building = Building::create([
            'organization_id' => $ownerWithoutUnits->organization_id,
            'name' => 'Closed Beta Empty Units Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($ownerWithoutUnits)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-units', false)
            ->assertSee('href="'.route('units.create', ['building_id' => $building->id]).'"', false)
            ->assertSee('href="'.route('buildings.units.bulk.create', $building).'"', false);

        $ownerWithoutContracts = $this->ownerForNewOrganization('closed-beta-no-contracts@example.com');
        $contractBuilding = Building::create([
            'organization_id' => $ownerWithoutContracts->organization_id,
            'name' => 'Closed Beta Empty Contracts Building',
            'location' => 'Riyadh',
        ]);
        Unit::create([
            'building_id' => $contractBuilding->id,
            'unit_number' => 'CB-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 2500,
        ]);

        $this->actingAs($ownerWithoutContracts)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-owner-onboarding-empty-contracts', false)
            ->assertSee('Create your first contract to start tracking payments')
            ->assertSee('href="'.route('contracts.create').'"', false);
    }

    public function test_caretaker_remains_limited_for_closed_beta(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Latest payments')
            ->assertDontSee('Monthly income')
            ->assertDontSee('Monthly expenses')
            ->assertDontSee('Net profit')
            ->assertDontSee('Reports')
            ->assertDontSee('Contracts')
            ->assertDontSee('Tenants')
            ->assertDontSee('Expenses')
            ->assertDontSee('Users')
            ->assertDontSee('Activity')
            ->assertDontSee('data-owner-onboarding-empty-buildings', false);

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
            $this->actingAs($caretaker)->get($url)->assertForbidden();
        }

        $this->actingAs($caretaker)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-payments-list', false);
    }

    public function test_arabic_rtl_core_pages_keep_brand_and_mobile_structure(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        app()->setLocale('ar');

        $this->assertSame($this->unicode('\u0627\u0644\u0645\u062f\u064a\u0631 \u0627\u0644\u0639\u0642\u0627\u0631\u064a'), __('app.name'));

        foreach ([
            route('dashboard') => 'data-mobile-owner-dashboard',
            route('buildings.index') => 'data-mobile-buildings-list',
            route('units.index') => 'data-mobile-units-list',
            route('tenants.index') => 'data-mobile-tenants-list',
            route('contracts.index') => 'data-mobile-contracts-list',
            route('payments.index') => 'data-mobile-payments-list',
        ] as $url => $marker) {
            $this->actingAs($owner)
                ->withSession(['locale' => 'ar'])
                ->get($url)
                ->assertOk()
                ->assertSee('<html lang="ar" dir="rtl">', false)
                ->assertSee(__('app.name'))
                ->assertSee($marker, false);
        }
    }

    private function ownerForNewOrganization(string $email): User
    {
        $organization = Organization::create(['name' => 'Closed Beta Organization '.$email]);

        return User::create([
            'organization_id' => $organization->id,
            'name' => 'Closed Beta Owner',
            'email' => $email,
            'password' => 'password',
            'role' => 'owner',
        ]);
    }

    private function unicode(string $escaped): string
    {
        return json_decode('"'.$escaped.'"', true, flags: JSON_THROW_ON_ERROR);
    }
}
