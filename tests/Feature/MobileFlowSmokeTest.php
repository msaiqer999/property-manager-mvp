<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_mobile_flow_pages_expose_mobile_friendly_sections_and_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $building = Building::where('organization_id', $owner->organization_id)->firstOrFail();
        $unit = Unit::whereHas('building', fn ($query) => $query->where('organization_id', $owner->organization_id))->firstOrFail();
        $tenant = Tenant::where('organization_id', $owner->organization_id)->firstOrFail();
        $contract = Contract::where('organization_id', $owner->organization_id)->firstOrFail();
        $payment = Payment::where('organization_id', $owner->organization_id)->firstOrFail();
        $recordablePayment = Payment::where('organization_id', $owner->organization_id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-mobile-owner-dashboard', false)
            ->assertSee('data-attention-section', false)
            ->assertSee('data-quick-actions', false)
            ->assertSee('tap-target', false)
            ->assertSee('min-h-11', false);

        $this->actingAs($owner)
            ->get(route('buildings.index'))
            ->assertOk()
            ->assertSee('data-mobile-buildings-list', false)
            ->assertSee('data-building-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('buildings.show', $building))
            ->assertOk()
            ->assertSee('data-building-actions', false)
            ->assertSee('data-building-units-mobile-list', false)
            ->assertSee('data-building-unit-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('units.index'))
            ->assertOk()
            ->assertSee('data-mobile-units-list', false)
            ->assertSee('data-unit-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('units.show', $unit))
            ->assertOk()
            ->assertSee('data-unit-show-card', false);

        $this->actingAs($owner)
            ->get(route('tenants.index'))
            ->assertOk()
            ->assertSee('data-mobile-tenants-list', false)
            ->assertSee('data-tenant-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('tenants.create'))
            ->assertOk()
            ->assertSee('data-tenant-form', false)
            ->assertSee('min-h-11', false);

        $this->actingAs($owner)
            ->get(route('tenants.show', $tenant))
            ->assertOk()
            ->assertSee('data-tenant-show-card', false);

        $this->actingAs($owner)
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertSee('data-mobile-contracts-list', false)
            ->assertSee('data-contract-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('contracts.show', $contract))
            ->assertOk()
            ->assertSee('data-contract-show-card', false)
            ->assertSee('data-contract-payments-mobile-list', false)
            ->assertSee('data-contract-payment-mobile-card', false);

        $this->actingAs($owner)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-payments-list', false)
            ->assertSee('data-payment-mobile-card', false)
            ->assertSee('data-payment-action', false);

        $this->actingAs($owner)
            ->get(route('payments.edit', $recordablePayment))
            ->assertOk()
            ->assertSee('data-payment-summary', false)
            ->assertSee('data-payment-record-form', false)
            ->assertSee('data-payment-action', false);

        $this->actingAs($owner)
            ->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('data-payment-action', false);
    }

    public function test_caretaker_mobile_flow_stays_limited_to_payment_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();
        $recordablePayment = Payment::where('organization_id', $caretaker->organization_id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->firstOrFail();

        $this->actingAs($caretaker)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Monthly income')
            ->assertDontSee('Monthly expenses')
            ->assertDontSee('Net profit')
            ->assertDontSee('Reports')
            ->assertDontSee('Contracts')
            ->assertDontSee('Tenants')
            ->assertDontSee('Expenses')
            ->assertDontSee('Users')
            ->assertDontSee('Activity');

        foreach ([
            route('buildings.index'),
            route('units.index'),
            route('tenants.index'),
            route('contracts.index'),
            route('reports.index'),
            route('users.index'),
            route('activity-logs.index'),
        ] as $url) {
            $this->actingAs($caretaker)->get($url)->assertForbidden();
        }

        $this->actingAs($caretaker)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('data-mobile-payments-list', false)
            ->assertSee('data-payment-mobile-card', false);

        $this->actingAs($caretaker)
            ->get(route('payments.edit', $recordablePayment))
            ->assertOk()
            ->assertSee('data-payment-summary', false)
            ->assertSee('data-payment-record-form', false);
    }

    public function test_arabic_mobile_flow_keeps_rtl_and_core_sections_visible(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        app()->setLocale('ar');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('app.dashboard.needs_attention'))
            ->assertSee(__('app.dashboard.quick_actions'))
            ->assertSee('data-mobile-owner-dashboard', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('contracts.index'))
            ->assertOk()
            ->assertSee(__('contracts.title'))
            ->assertSee('data-mobile-contracts-list', false);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee(__('payments.title'))
            ->assertSee('data-mobile-payments-list', false);
    }
}
