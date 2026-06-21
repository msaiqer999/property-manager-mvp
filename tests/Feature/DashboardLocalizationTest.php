<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardLocalizationTest extends TestCase
{
    use RefreshDatabase;

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

        $this->get(route('dashboard'))->assertRedirect(route('login'));
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
