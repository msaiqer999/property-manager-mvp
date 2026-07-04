<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\PdfRenderer;
use App\Support\ReportAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ScopesOrganization;

    public function index(Request $request, ReportAuthorization $authorization)
    {
        abort_if(auth()->user()->role->value === 'caretaker', 403);
        abort_unless($authorization->viewReports(auth()->user()), 403);
        abort_unless($authorization->viewProfitData(auth()->user()), 403);

        $filters = $this->reportFilters($request);

        return view('reports.index', $this->summaryData($filters) + [
            'filters' => $filters,
            'buildings' => $this->buildings(),
            'units' => $this->units(),
            'tenants' => $this->tenants(),
            'filterQuery' => $this->filterQuery($filters),
            'statementRows' => $this->statementRows($filters),
        ]);
    }

    public function pdf(Request $request, string $type, ReportAuthorization $authorization, PdfRenderer $pdf)
    {
        abort_if(auth()->user()->role->value === 'caretaker', 403);
        abort_unless(in_array($type, ['building-income', 'unit-statement', 'expenses', 'overdue', 'net-profit', 'monthly-summary'], true), 404);
        abort_unless($authorization->exportPdf(auth()->user(), $type), 403);

        if (in_array($type, ['net-profit', 'monthly-summary'], true)) {
            abort_unless($authorization->viewProfitData(auth()->user()), 403);
        }

        $filters = $this->reportFilters($request);

        return $pdf->download('pdf.report', $this->reportData($type, $filters) + ['type' => $type], $this->pdfFilename($type));
    }

    private function summaryData(array $filters): array
    {
        $orgId = $this->organizationId();
        $income = $this->paymentQuery($filters, 'payment_date')
            ->where('amount_paid', '>', 0)
            ->sum('amount_paid');
        $expenses = $this->expenseQuery($filters)->sum('amount');

        return [
            'income' => $income,
            'expensesTotal' => $expenses,
            'netProfit' => $income - $expenses,
        ];
    }

    private function reportData(string $type, ?array $filters = null): array
    {
        $filters ??= $this->reportFilters(request());
        $summary = $this->summaryData($filters);

        $data = $summary + match ($type) {
            'building-income' => [
                'rows' => DB::table('buildings')
                    ->leftJoin('units', 'units.building_id', '=', 'buildings.id')
                    ->leftJoin('contracts', 'contracts.unit_id', '=', 'units.id')
                    ->leftJoin('payments', function ($join) use ($filters) {
                        $join->on('payments.contract_id', '=', 'contracts.id')
                            ->where('payments.amount_paid', '>', 0)
                            ->whereBetween('payments.payment_date', [$filters['from'], $filters['to']]);
                    })
                    ->where('buildings.organization_id', $this->organizationId())
                    ->when($filters['building_id'], fn ($query, $id) => $query->where('buildings.id', $id))
                    ->when($filters['unit_id'], fn ($query, $id) => $query->where('units.id', $id))
                    ->when($filters['tenant_id'], fn ($query, $id) => $query->where('contracts.tenant_id', $id))
                    ->select('buildings.name', DB::raw('COALESCE(SUM(payments.amount_paid), 0) as income'))
                    ->groupBy('buildings.id', 'buildings.name')
                    ->get(),
            ],
            'unit-statement' => [
                'rows' => $this->unitStatementUnits($filters),
                'statementRows' => $this->statementRows($filters),
            ],
            'expenses' => [
                'rows' => $this->expenseQuery($filters)->with('building', 'unit')->latest('expense_date')->get(),
            ],
            'overdue' => [
                'rows' => $this->paymentQuery($filters, 'due_date')
                    ->with('contract.tenant', 'contract.unit.building')
                    ->where('status', 'overdue')
                    ->whereColumn('amount_paid', '<', 'amount_due')
                    ->select('payments.*', DB::raw('amount_due - amount_paid as remaining_amount'))
                    ->orderBy('due_date')
                    ->get(),
            ],
            default => [
                'rows' => $this->paymentQuery($filters, 'due_date')
                    ->with('contract.unit.building', 'contract.tenant')
                    ->orderBy('due_date')
                    ->get(),
            ],
        };

        return $data + [
            'filters' => $filters,
            'totals' => $this->reportTotals($type, $data),
        ];
    }

    private function reportFilters(Request $request): array
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfMonth();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $buildingId = $request->integer('building_id') ?: null;
        $unitId = $request->integer('unit_id') ?: null;
        $building = null;
        $unit = null;
        $tenantId = $request->integer('tenant_id') ?: null;
        $tenant = null;

        if ($buildingId !== null) {
            $building = Building::findOrFail($buildingId);
            abort_unless((int) $building->organization_id === $this->organizationId(), 403);
        }

        if ($unitId !== null) {
            $unit = Unit::with('building')->findOrFail($unitId);
            abort_unless((int) $unit->building?->organization_id === $this->organizationId(), 403);

            if ($buildingId !== null && (int) $unit->building_id !== $buildingId) {
                abort(403);
            }
        }

        if ($tenantId !== null) {
            $tenant = Tenant::findOrFail($tenantId);
            abort_unless((int) $tenant->organization_id === $this->organizationId(), 403);
        }

        return [
            'building_id' => $buildingId,
            'unit_id' => $unitId,
            'tenant_id' => $tenantId,
            'from' => $from,
            'to' => $to,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'building_label' => $building?->name ?? __('reports.filters.all_buildings'),
            'unit_label' => $unit?->unit_number ?? __('reports.filters.all_units'),
            'tenant_label' => $tenant?->full_name ?? __('reports.filters.all_tenants'),
            'tenant_phone' => $tenant?->phone,
        ];
    }

    private function filterQuery(array $filters): array
    {
        return array_filter([
            'building_id' => $filters['building_id'],
            'unit_id' => $filters['unit_id'],
            'tenant_id' => $filters['tenant_id'],
            'from' => $filters['from_date'],
            'to' => $filters['to_date'],
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function paymentQuery(array $filters, string $dateColumn)
    {
        return Payment::query()
            ->where('payments.organization_id', $this->organizationId())
            ->whereBetween("payments.{$dateColumn}", [$filters['from'], $filters['to']])
            ->when($filters['building_id'], fn ($query, $id) => $query->whereHas('contract.unit', fn ($unit) => $unit->where('building_id', $id)))
            ->when($filters['unit_id'], fn ($query, $id) => $query->whereHas('contract', fn ($contract) => $contract->where('unit_id', $id)))
            ->when($filters['tenant_id'], fn ($query, $id) => $query->whereHas('contract', fn ($contract) => $contract->where('tenant_id', $id)));
    }

    private function expenseQuery(array $filters)
    {
        return Expense::query()
            ->where('organization_id', $this->organizationId())
            ->notVoided()
            ->whereBetween('expense_date', [$filters['from'], $filters['to']])
            ->when($filters['building_id'], fn ($query, $id) => $query->where('building_id', $id))
            ->when($filters['unit_id'], fn ($query, $id) => $query->where('unit_id', $id));
    }

    private function reportTotals(string $type, array $data): array
    {
        $rows = $data['rows'];

        return match ($type) {
            'building-income' => ['income' => (float) $rows->sum('income')],
            'expenses' => ['expenses' => (float) $rows->sum('amount')],
            'overdue' => [
                'amount_due' => (float) $rows->sum('amount_due'),
                'amount_paid' => (float) $rows->sum('amount_paid'),
                'remaining_amount' => (float) $rows->sum(fn ($row) => $row->remaining_amount ?? ($row->amount_due - $row->amount_paid)),
            ],
            'unit-statement' => [
                'amount_due' => (float) $data['statementRows']->sum('amount_due'),
                'amount_paid' => (float) $data['statementRows']->sum('amount_paid'),
                'remaining_balance' => (float) $data['statementRows']->sum(fn ($payment) => $payment->remaining_amount),
                'overdue_remaining' => (float) $data['statementRows']->sum(fn ($payment) => in_array($payment->display_status_key, ['overdue', 'partial_overdue'], true) ? $payment->remaining_amount : 0),
            ],
            default => [
                'income' => (float) $data['income'],
                'expenses' => (float) $data['expensesTotal'],
                'net_profit' => (float) $data['netProfit'],
            ],
        };
    }

    private function buildings()
    {
        return Building::where('organization_id', $this->organizationId())->orderBy('name')->get();
    }

    private function units()
    {
        return Unit::whereHas('building', fn ($query) => $query->where('organization_id', $this->organizationId()))
            ->with('building')
            ->orderBy('unit_number')
            ->get();
    }

    private function tenants()
    {
        return Tenant::where('organization_id', $this->organizationId())
            ->orderBy('full_name')
            ->get();
    }

    private function statementRows(array $filters)
    {
        return $this->paymentQuery($filters, 'due_date')
            ->with('contract.tenant', 'contract.unit.building')
            ->orderBy('due_date')
            ->get();
    }

    private function unitStatementUnits(array $filters)
    {
        return Unit::with('building')
            ->whereHas('building', fn ($query) => $query->where('organization_id', $this->organizationId()))
            ->when($filters['building_id'], fn ($query, $id) => $query->where('building_id', $id))
            ->when($filters['unit_id'], fn ($query, $id) => $query->whereKey($id))
            ->when($filters['tenant_id'], fn ($query, $id) => $query->whereHas('contracts', fn ($contract) => $contract->where('tenant_id', $id)))
            ->orderBy('unit_number')
            ->get();
    }

    private function pdfFilename(string $type): string
    {
        $period = now()->format('Y-m');

        return match ($type) {
            'building-income' => "building-income-{$period}.pdf",
            'unit-statement' => "unit-statement-{$period}.pdf",
            'expenses' => "expenses-{$period}.pdf",
            'overdue' => "overdue-payments-{$period}.pdf",
            'net-profit' => "net-profit-{$period}.pdf",
            'monthly-summary' => "monthly-summary-{$period}.pdf",
            default => "{$type}-{$period}.pdf",
        };
    }
}
