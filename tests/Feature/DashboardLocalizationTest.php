<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_shows_guided_summary_attention_and_quick_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Collected this month')
            ->assertSee('Overdue amount')
            ->assertSee('Vacant units')
            ->assertSee('Contracts expiring soon')
            ->assertSee('AED', false)
            ->assertSee('<bdi>AED', false)
            ->assertSee('Start managing your property')
            ->assertSee('Your property management basics are set up.')
            ->assertSee('What should I do today?')
            ->assertSee('Needs your attention')
            ->assertSee('Overdue payments')
            ->assertSee('Contracts ending soon')
            ->assertSee('Partial payments need follow-up')
            ->assertSee('Quick actions')
            ->assertSee('Record payment')
            ->assertSee('Add contract')
            ->assertSee('Add tenant')
            ->assertSee('Add building')
            ->assertSee('Add multiple units')
            ->assertSee('data-dashboard-with-roadmap', false)
            ->assertSee('data-dashboard-roadmap', false)
            ->assertSee('bg-gradient-to-br', false)
            ->assertSee('Coming Soon')
            ->assertSee('These features are being developed based on early user feedback and will be released when ready.')
            ->assertSee('Unit Document Center')
            ->assertSee('Smart payment and contract alerts')
            ->assertSee('Maintenance requests')
            ->assertSee('Tenant account')
            ->assertSee('Vacant unit listing')
            ->assertSee('Suggest a feature')
            ->assertSee('aria-disabled="true"', false)
            ->assertDontSee('href="#"', false)
            ->assertDontSee('No launch dates are promised yet.')
            ->assertDontSee('Monthly expenses')
            ->assertDontSee('Net profit');
    }

    public function test_owner_dashboard_has_mobile_friendly_structure_and_large_quick_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('data-dashboard-guided-start', false)
            ->assertSee('data-guided-start-step', false)
            ->assertSee('data-mobile-owner-dashboard', false)
            ->assertSee('data-dashboard-kpi-card', false)
            ->assertSee('min-h-32', false)
            ->assertSee('justify-between', false)
            ->assertSee('data-daily-actions', false)
            ->assertSee('data-attention-section', false)
            ->assertSee('data-quick-actions', false)
            ->assertSee('data-dashboard-with-roadmap', false)
            ->assertSee('data-dashboard-roadmap', false)
            ->assertSee('data-dashboard-roadmap-item', false)
            ->assertSee('data-dashboard-secondary-lists', false)
            ->assertSee('data-latest-payments', false)
            ->assertSee('data-latest-expenses', false)
            ->assertSee('min-h-11', false)
            ->assertSee('justify-center', false)
            ->assertSee('grid gap-3 sm:flex sm:flex-wrap', false);
    }

    public function test_owner_dashboard_shows_first_building_empty_state_without_metric_noise(): void
    {
        $organization = Organization::create(['name' => 'Empty Dashboard Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Empty Dashboard Owner',
            'email' => 'empty-dashboard-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Start managing your property')
            ->assertSee('Add a building')
            ->assertSee('Next')
            ->assertSee('Start by adding your first building')
            ->assertSee('Add building')
            ->assertDontSee('Collected this month')
            ->assertDontSee('Needs your attention')
            ->assertDontSee('Quick actions');
    }

    public function test_caretaker_dashboard_remains_limited_to_payment_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->actingAs($caretaker)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Latest payments')
            ->assertDontSee('data-dashboard-guided-start', false)
            ->assertDontSee('data-dashboard-roadmap', false)
            ->assertDontSee('Start managing your property')
            ->assertDontSee('Collected this month')
            ->assertDontSee('Monthly income')
            ->assertDontSee('Monthly expenses')
            ->assertDontSee('Net profit')
            ->assertDontSee('Reports')
            ->assertDontSee('Contracts')
            ->assertDontSee('Tenants')
            ->assertDontSee('Expenses')
            ->assertDontSee('Users')
            ->assertDontSee('Activity');
    }

    public function test_dashboard_guided_sections_render_in_arabic_locale(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();

        app()->setLocale('ar');
        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('app.dashboard.guided_start_title'))
            ->assertSee(__('app.dashboard.daily_actions_title'))
            ->assertSee(__('app.dashboard.collected_this_month'))
            ->assertSee(__('app.dashboard.needs_attention'))
            ->assertSee(__('app.dashboard.quick_actions'))
            ->assertSee(__('app.dashboard.roadmap_title'))
            ->assertSee(__('payments.record_payment'))
            ->assertSee(__('contracts.add'))
            ->assertSee(__('units.bulk.add_multiple'));
    }

    public function test_incomplete_owner_sees_guided_start_progress_and_existing_next_route(): void
    {
        $organization = Organization::create(['name' => 'Guided Start Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Guided Start Owner',
            'email' => 'guided-start-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Guided Start Building',
            'location' => 'Riyadh',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-guided-start', false)
            ->assertSee('Add a building')
            ->assertSee('Completed')
            ->assertSee('Add units')
            ->assertSee('Next')
            ->assertSee('href="'.route('buildings.units.bulk.create', $building).'"', false)
            ->assertDontSee('app.dashboard.guided_start_title');
    }

    public function test_daily_actions_show_overdue_partial_expiring_and_vacant_items(): void
    {
        $organization = Organization::create(['name' => 'Daily Actions Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Daily Actions Owner',
            'email' => 'daily-actions-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Daily Actions Building',
            'location' => 'Riyadh',
        ]);
        Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'DA-VACANT-101',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => 1000,
        ]);
        $rentedUnit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'DA-RENTED-102',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Daily Actions Tenant',
            'phone' => '0500000000',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $rentedUnit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'DA-2026-001',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);
        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->subDays(5)->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 0,
            'status' => 'overdue',
        ]);
        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->toDateString(),
            'amount_due' => 1000,
            'amount_paid' => 400,
            'payment_date' => now()->toDateString(),
            'status' => 'partial',
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('What should I do today?')
            ->assertSee('Overdue payments')
            ->assertSee('Partial payments need follow-up')
            ->assertSee('Contracts ending soon')
            ->assertSee('Vacant units')
            ->assertSee('href="'.route('payments.index', ['overdue' => 1]).'"', false)
            ->assertSee('href="'.route('payments.index', ['status' => 'partial']).'"', false)
            ->assertSee('href="'.route('units.index', ['status' => 'vacant']).'"', false)
            ->assertDontSee('app.dashboard.daily_actions_title');
    }

    public function test_dashboard_displays_english_expense_category_label_without_changing_stored_value(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $expense = $this->dashboardExpense($owner, [
            'building' => 'Dashboard Localization Tower',
            'unit' => 'DASH-LOC-101',
            'category' => 'maintenance',
            'amount' => 4321.09,
            'date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Dashboard localization expense note stays unchanged.',
            'created_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Latest expenses')
            ->assertSee('Maintenance')
            ->assertSeeHtml('<span class="bidi-isolate font-medium" dir="ltr">4,321.09</span>');

        $this->assertSame('maintenance', $expense->fresh()->category);
    }

    public function test_dashboard_displays_arabic_expense_category_label_and_not_raw_internal_category(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $expense = $this->dashboardExpense($owner, [
            'building' => 'Arabic Dashboard Tower',
            'unit' => 'AR-DASH-202',
            'category' => 'security',
            'amount' => 8765.43,
            'date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Arabic dashboard expense note stays unchanged.',
            'created_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('أمن')
            ->assertDontSee('security')
            ->assertSeeHtml('<span class="bidi-isolate font-medium" dir="ltr">8,765.43</span>');

        $freshExpense = $expense->fresh();
        $this->assertSame('security', $freshExpense->category);
        $this->assertSame('Arabic Dashboard Tower', $freshExpense->building->name);
        $this->assertSame('AR-DASH-202', $freshExpense->unit->unit_number);
        $this->assertSame('8765.43', number_format((float) $freshExpense->amount, 2, '.', ''));
        $this->assertSame(now()->startOfMonth()->toDateString(), $freshExpense->expense_date->toDateString());
    }

    public function test_dashboard_latest_expenses_remain_organization_scoped(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $this->dashboardExpense($owner, [
            'building' => 'Scoped Dashboard Tower',
            'unit' => 'SCOPE-DASH-303',
            'category' => 'cleaning',
            'amount' => 1111.11,
            'date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Visible organization dashboard expense.',
            'created_at' => now()->addDay(),
        ]);
        $this->otherOrganizationExpense();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cleaning')
            ->assertSee('1,111.11')
            ->assertDontSee('999,999.00')
            ->assertDontSee('Other organization dashboard expense.');
    }

    public function test_dashboard_excludes_voided_expenses_from_totals_and_latest_expenses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $activeExpense = $this->dashboardExpense($owner, [
            'building' => 'Active Dashboard Expense Building',
            'unit' => 'ACTIVE-DASH-101',
            'category' => 'cleaning',
            'amount' => 111.11,
            'date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Active dashboard expense.',
            'created_at' => now()->addDay(),
        ]);
        $voidedExpense = $this->dashboardExpense($owner, [
            'building' => 'Voided Dashboard Expense Building',
            'unit' => 'VOID-DASH-101',
            'category' => 'maintenance',
            'amount' => 999999,
            'date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Voided dashboard expense.',
            'created_at' => now()->addDays(2),
        ]);
        $voidedExpense->forceFill([
            'voided_at' => now(),
            'voided_by' => $owner->id,
            'void_reason' => 'Excluded from dashboard.',
        ])->saveQuietly();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(number_format((float) $activeExpense->amount, 2))
            ->assertDontSee('999,999.00')
            ->assertDontSee('Voided dashboard expense.');
    }

    public function test_dashboard_route_and_authorization_behavior_remain_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        $this->assertSame('/', route('dashboard', absolute: false));

        foreach ([$owner, $manager, $accountant, $caretaker] as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }

        auth()->logout();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('landing.hero_title'));
    }

    private function dashboardExpense(User $owner, array $values): Expense
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => $values['building'],
            'location' => 'Abu Dhabi',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => $values['unit'],
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => $values['amount'],
        ]);

        $expense = Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => $values['category'],
            'amount' => $values['amount'],
            'expense_date' => $values['date'],
            'notes' => $values['notes'],
            'created_by' => $owner->id,
        ]);

        if (isset($values['created_at'])) {
            $createdAt = $values['created_at'];

            $expense->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();

            $expense->refresh();
        }

        return $expense->load('building', 'unit');
    }

    private function otherOrganizationExpense(): Expense
    {
        $organization = Organization::create(['name' => 'Dashboard Localization Other Organization']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Other Dashboard Owner',
            'email' => 'other-dashboard-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Other Dashboard Building',
            'location' => 'Dubai',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-DASH-404',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 999999,
        ]);

        $createdAt = now()->addDays(2);

        $expense = Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $building->id,
            'unit_id' => $unit->id,
            'category' => 'security',
            'amount' => 999999,
            'expense_date' => now()->startOfMonth()->toDateString(),
            'notes' => 'Other organization dashboard expense.',
            'created_by' => $owner->id,
        ]);

        $expense->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $expense->refresh();
    }
}
