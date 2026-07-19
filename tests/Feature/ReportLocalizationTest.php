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
            ->assertSee('name="building_id"', false)
            ->assertSee('name="unit_id"', false)
            ->assertSee('name="from"', false)
            ->assertSee('name="to"', false)
            ->assertSee('<bdi>AED '.number_format($income, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($expenses, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($netProfit, 2).'</bdi>', false);

        foreach ($this->reportTypes() as $type) {
            $response->assertSee('href="'.e($this->filteredReportUrl($type)).'"', false);
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
            ->assertSee('<bdi>AED '.number_format($income, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($expenses, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($netProfit, 2).'</bdi>', false);

        foreach ($this->reportTypes() as $type) {
            $response->assertSee('href="'.e($this->filteredReportUrl($type)).'"', false);
        }
    }

    public function test_report_filters_scope_expenses_pdf_and_profit_totals(): void
    {
        $organization = Organization::create(['name' => 'Report Filter Org']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Report Filter Owner',
            'email' => 'report-filter-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $buildingA = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Report Filter Building A',
            'location' => 'Riyadh',
        ]);
        $buildingB = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Report Filter Building B',
            'location' => 'Riyadh',
        ]);
        $unitA = Unit::create([
            'building_id' => $buildingA->id,
            'unit_number' => 'RPT-A-101',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 1000,
        ]);
        $unitB = Unit::create([
            'building_id' => $buildingB->id,
            'unit_number' => 'RPT-B-202',
            'type' => 'apartment',
            'status' => 'maintenance',
            'rent_amount' => 1000,
        ]);

        Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $buildingA->id,
            'unit_id' => $unitA->id,
            'category' => 'maintenance',
            'amount' => 321,
            'expense_date' => '2026-06-12',
            'notes' => 'Filtered expense should appear.',
            'created_by' => $owner->id,
        ]);

        Expense::create([
            'organization_id' => $organization->id,
            'building_id' => $buildingB->id,
            'unit_id' => $unitB->id,
            'category' => 'maintenance',
            'amount' => 654,
            'expense_date' => '2026-06-12',
            'notes' => 'Filtered expense should be hidden.',
            'created_by' => $owner->id,
        ]);

        $filter = [
            'unit_id' => $unitA->id,
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ];
        $buildingFilter = ['building_id' => $buildingA->id] + $filter;

        $this->actingAs($owner)->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Report Filter Building A')
            ->assertSee('RPT-A-101')
            ->assertSee('321.00');

        $this->actingAs($owner)->get(route('expenses.index', ['building_id' => $buildingA->id]))
            ->assertOk()
            ->assertSee('321.00')
            ->assertDontSee('654.00');

        $this->actingAs($owner)->get(route('expenses.index', ['unit_id' => $unitA->id]))
            ->assertOk()
            ->assertSee('321.00')
            ->assertDontSee('654.00');

        $this->actingAs($owner)->get(route('expenses.index', ['category' => 'maintenance']))
            ->assertOk()
            ->assertSee('321.00');

        $reports = $this->actingAs($owner)->get(route('reports.index', $filter))
            ->assertOk()
            ->assertSee(__('reports.filters.all_buildings'))
            ->assertSee('RPT-A-101')
            ->assertSee('321.00')
            ->assertSee('-321.00')
            ->assertDontSee('654.00');

        foreach ($this->reportTypes() as $type) {
            $reports->assertSee('href="'.e(route('reports.pdf', ['type' => $type] + $filter)).'"', false);
        }

        $this->actingAs($owner)->get(route('reports.index', $buildingFilter))
            ->assertOk()
            ->assertSee('321.00')
            ->assertDontSee('654.00');

        $html = $this->actingAs($owner)->get(route('reports.pdf', ['type' => 'expenses'] + $filter))
            ->assertOk();

        $this->assertStringStartsWith('%PDF-', $html->getContent());

        app()->setLocale('en');
        $this->actingAs($owner);
        $controller = app(\App\Http\Controllers\ReportController::class);
        $filtersMethod = new \ReflectionMethod($controller, 'reportFilters');
        $filtersMethod->setAccessible(true);
        $reportFilters = $filtersMethod->invoke($controller, \Illuminate\Http\Request::create('/reports/expenses/pdf', 'GET', $filter));

        $method = new \ReflectionMethod($controller, 'reportData');
        $method->setAccessible(true);
        $reportHtml = view('pdf.report', $method->invoke($controller, 'expenses', $reportFilters) + ['type' => 'expenses'])->render();

        $this->assertStringContainsString('Report Filter Building A', $reportHtml);
        $this->assertStringContainsString('RPT-A-101', $reportHtml);
        $this->assertStringContainsString('All buildings', $reportHtml);
        $this->assertStringContainsString('321.00', $reportHtml);
        $this->assertStringNotContainsString('654.00', $reportHtml);
        $this->assertStringContainsString('Report filters', $reportHtml);
        $this->assertStringContainsString('Totals', $reportHtml);
        $this->assertStringNotContainsString('reports.', $reportHtml);
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

    public function test_tenant_unit_statement_filters_totals_receipts_and_security(): void
    {
        $organization = Organization::create(['name' => 'Statement Org']);
        $otherOrganization = Organization::create(['name' => 'Other Statement Org']);
        $owner = User::create([
            'organization_id' => $organization->id,
            'name' => 'Statement Owner',
            'email' => 'statement-owner@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);
        $manager = User::create([
            'organization_id' => $organization->id,
            'name' => 'Statement Manager',
            'email' => 'statement-manager@example.com',
            'password' => 'password',
            'role' => 'manager',
        ]);
        $accountant = User::create([
            'organization_id' => $organization->id,
            'name' => 'Statement Accountant',
            'email' => 'statement-accountant@example.com',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        $caretaker = User::create([
            'organization_id' => $organization->id,
            'name' => 'Statement Caretaker',
            'email' => 'statement-caretaker@example.com',
            'password' => 'password',
            'role' => 'caretaker',
        ]);
        $building = Building::create([
            'organization_id' => $organization->id,
            'name' => 'Statement Building',
            'location' => 'Dubai',
        ]);
        $unit = Unit::create([
            'building_id' => $building->id,
            'unit_number' => 'ST-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 1000,
        ]);
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'full_name' => 'Statement Tenant',
            'phone' => '+971500111222',
        ]);
        $contract = Contract::create([
            'organization_id' => $organization->id,
            'unit_id' => $unit->id,
            'tenant_id' => $tenant->id,
            'contract_number' => 'STMT-001',
            'start_date' => '2026-06-01',
            'end_date' => '2027-05-31',
            'rent_amount' => 1000,
            'payment_frequency' => 'monthly',
            'deposit_amount' => 0,
            'status' => 'active',
        ]);
        $paidPayment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-01',
            'amount_due' => 1000,
            'amount_paid' => 1000,
            'payment_date' => '2026-06-02',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);
        $partialPayment = Payment::create([
            'organization_id' => $organization->id,
            'contract_id' => $contract->id,
            'due_date' => '2026-06-15',
            'amount_due' => 1000,
            'amount_paid' => 250,
            'payment_method' => 'cash',
            'status' => 'partial',
        ]);

        $otherBuilding = Building::create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other Statement Building',
            'location' => 'Abu Dhabi',
        ]);
        $otherUnit = Unit::create([
            'building_id' => $otherBuilding->id,
            'unit_number' => 'OTHER-ST-101',
            'type' => 'apartment',
            'status' => 'rented',
            'rent_amount' => 9999,
        ]);
        $otherTenant = Tenant::create([
            'organization_id' => $otherOrganization->id,
            'full_name' => 'Other Statement Tenant',
        ]);

        $filter = [
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ];

        foreach ([$owner, $manager, $accountant] as $user) {
            $this->actingAs($user)->get(route('reports.index', $filter))->assertOk();
        }

        $response = $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('reports.index', $filter));

        $response->assertOk()
            ->assertSee('Tenant / unit statement')
            ->assertSee('Statement Tenant')
            ->assertSee('+971500111222')
            ->assertSee('Statement Building')
            ->assertSee('ST-101')
            ->assertSee('STMT-001')
            ->assertSee('2,000.00')
            ->assertSee('1,250.00')
            ->assertSee('750.00')
            ->assertSee('Partially paid, balance overdue')
            ->assertSee(route('payments.show', $paidPayment), false)
            ->assertSee(route('payments.show', $partialPayment), false)
            ->assertSee('tenant_id='.$tenant->id, false)
            ->assertDontSee('min-w-[62rem]', false)
            ->assertDontSee('reports.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'ar'])
            ->get(route('reports.index', $filter))
            ->assertOk()
            ->assertSee('كشف حساب')
            ->assertSee('Statement Tenant')
            ->assertDontSee('reports.');

        $this->actingAs($caretaker)->get(route('reports.index', $filter))->assertForbidden();
        $this->actingAs($owner)->get(route('reports.index', ['tenant_id' => $otherTenant->id]))->assertForbidden();
        $this->actingAs($owner)->get(route('reports.index', ['unit_id' => $otherUnit->id]))->assertForbidden();

        app()->setLocale('en');
        $this->actingAs($owner);
        $controller = app(\App\Http\Controllers\ReportController::class);
        $filtersMethod = new \ReflectionMethod($controller, 'reportFilters');
        $filtersMethod->setAccessible(true);
        $reportFilters = $filtersMethod->invoke($controller, \Illuminate\Http\Request::create('/reports/unit-statement/pdf', 'GET', $filter));
        $method = new \ReflectionMethod($controller, 'reportData');
        $method->setAccessible(true);
        $reportHtml = view('pdf.report', $method->invoke($controller, 'unit-statement', $reportFilters) + ['type' => 'unit-statement'])->render();

        $this->assertStringContainsString('Statement Tenant', $reportHtml);
        $this->assertStringContainsString('+971500111222', $reportHtml);
        $this->assertStringContainsString('ST-101', $reportHtml);
        $this->assertStringContainsString('STMT-001', $reportHtml);
        $this->assertStringContainsString('2,000.00', $reportHtml);
        $this->assertStringContainsString('1,250.00', $reportHtml);
        $this->assertStringContainsString('750.00', $reportHtml);
        $this->assertStringContainsString('Partially paid, balance overdue', $reportHtml);
        $this->assertStringNotContainsString('Other Statement Tenant', $reportHtml);
        $this->assertStringNotContainsString('reports.', $reportHtml);
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
            ->assertSee('<bdi>AED '.number_format($income, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($expenses, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($netProfit, 2).'</bdi>', false)
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
            ->assertSee('<bdi>AED '.number_format($income, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($expenses, 2).'</bdi>', false)
            ->assertSee('<bdi>AED '.number_format($netProfit, 2).'</bdi>', false)
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

    private function filteredReportUrl(string $type): string
    {
        return route('reports.pdf', [
            'type' => $type,
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ]);
    }
}
