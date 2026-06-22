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

class ReportLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_renders_english_and_preserves_routes_and_report_types(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        [$income, $expenses, $netProfit] = $this->currentMonthTotals($owner);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('Reports')
            ->assertSee('Income')
            ->assertSee('Expenses')
            ->assertSee('Net profit')
            ->assertSee('Export building income PDF')
            ->assertSee('Download unit statement PDF')
            ->assertSee('Export expenses PDF')
            ->assertSee('Export overdue payments PDF')
            ->assertSee('Export net profit PDF')
            ->assertSee('Export monthly summary PDF')
            ->assertSee('dir="ltr">'.number_format($income, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($expenses, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($netProfit, 2).'</p>', false);

        foreach ($this->reportTypes() as $type) {
            $response->assertSee('href="'.route('reports.pdf', $type).'"', false);
            $this->assertSame("/reports/{$type}/pdf", route('reports.pdf', $type, absolute: false));
        }
    }

    public function test_reports_index_renders_arabic_with_rtl_and_ltr_monetary_totals(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        [$income, $expenses, $netProfit] = $this->currentMonthTotals($owner);

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('التقارير')
            ->assertSee('الدخل')
            ->assertSee('المصروفات')
            ->assertSee('صافي الربح')
            ->assertSee('تصدير تقرير دخل المباني PDF')
            ->assertSee('تنزيل كشف الوحدة PDF')
            ->assertSee('تصدير تقرير المصروفات PDF')
            ->assertSee('تصدير تقرير الدفعات المتأخرة PDF')
            ->assertSee('تصدير تقرير صافي الربح PDF')
            ->assertSee('تصدير الملخص الشهري PDF')
            ->assertSee('dir="ltr">'.number_format($income, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($expenses, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($netProfit, 2).'</p>', false);

        foreach ($this->reportTypes() as $type) {
            $response->assertSee('href="'.route('reports.pdf', $type).'"', false);
        }
    }

    public function test_report_authorization_remains_unchanged(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $accountant = User::where('email', 'accountant@example.com')->firstOrFail();
        $caretaker = User::where('email', 'caretaker@example.com')->firstOrFail();

        foreach ([$owner, $manager, $accountant] as $user) {
            $this->actingAs($user)->get(route('reports.index'))->assertOk();
        }

        $this->actingAs($caretaker)->get(route('reports.index'))->assertForbidden();
    }

    public function test_report_totals_remain_organization_scoped(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        [$income, $expenses, $netProfit] = $this->currentMonthTotals($owner);

        $this->createOtherOrganizationFinancialData();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('dir="ltr">'.number_format($income, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($expenses, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($netProfit, 2).'</p>', false)
            ->assertDontSee('999,999.00');
    }

    public function test_report_totals_exclude_voided_expenses(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        [$income, $expenses, $netProfit] = $this->currentMonthTotals($owner);

        $this->createVoidedCurrentMonthExpense($owner, 999999);

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('dir="ltr">'.number_format($income, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($expenses, 2).'</p>', false)
            ->assertSee('dir="ltr">'.number_format($netProfit, 2).'</p>', false)
            ->assertDontSee('999,999.00');
    }

    private function currentMonthTotals(User $user): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $income = Payment::where('organization_id', $user->organization_id)
            ->where('amount_paid', '>', 0)
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount_paid');
        $expenses = Expense::where('organization_id', $user->organization_id)
            ->notVoided()
            ->whereBetween('expense_date', [$start, $end])
            ->sum('amount');

        return [(float) $income, (float) $expenses, (float) $income - (float) $expenses];
    }

    private function createOtherOrganizationFinancialData(): void
    {
        $organization = Organization::create([
            'name' => 'Reports Localization Other Organization',
        ]);

        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Reports Localization Other Building',
            'location' => 'Other City',
        ]);

        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'OTHER-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 999999,
        ]);

        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Reports Localization Other Tenant',
            'phone' => '0509999999',
        ]);

        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'REPORT-OTHER-001',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addYear()->toDateString(),
            'rent_amount' => 999999,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);

        Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => now()->startOfMonth()->toDateString(),
            'amount_due' => 999999,
            'amount_paid' => 999999,
            'payment_date' => now()->startOfMonth()->toDateString(),
            'status' => 'paid',
        ]);
    }

    private function createVoidedCurrentMonthExpense(User $owner, int $amount): void
    {
        $building = Building::create([
            'organization_id' => $owner->organization_id,
            'name' => 'Voided Report Expense Building',
            'location' => 'Abu Dhabi',
        ]);

        $expense = Expense::create([
            'organization_id' => $owner->organization_id,
            'building_id' => $building->id,
            'category' => 'maintenance',
            'amount' => $amount,
            'expense_date' => now()->startOfMonth()->toDateString(),
            'created_by' => $owner->id,
        ]);

        $expense->forceFill([
            'voided_at' => now(),
            'voided_by' => $owner->id,
            'void_reason' => 'Excluded from report totals.',
        ])->saveQuietly();
    }

    private function reportTypes(): array
    {
        return [
            'building-income',
            'unit-statement',
            'expenses',
            'overdue',
            'net-profit',
            'monthly-summary',
        ];
    }
}
