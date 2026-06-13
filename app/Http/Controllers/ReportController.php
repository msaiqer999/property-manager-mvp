<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ScopesOrganization;

    public function index()
    {
        abort_if(auth()->user()->role->value === 'caretaker', 403);
        return view('reports.index', $this->summaryData());
    }

    public function pdf(string $type)
    {
        abort_if(auth()->user()->role->value === 'caretaker', 403);
        abort_unless(in_array($type, ['building-income', 'unit-statement', 'expenses', 'overdue', 'net-profit', 'monthly-summary'], true), 404);

        return Pdf::loadView('pdf.report', $this->reportData($type) + ['type' => $type])->download("{$type}.pdf");
    }

    private function summaryData(): array
    {
        $orgId = $this->organizationId();
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $income = Payment::where('organization_id', $orgId)->where('status', 'paid')->whereBetween('payment_date', [$start, $end])->sum('amount_paid');
        $expenses = Expense::where('organization_id', $orgId)->whereBetween('expense_date', [$start, $end])->sum('amount');

        return [
            'income' => $income,
            'expensesTotal' => $expenses,
            'netProfit' => $income - $expenses,
        ];
    }

    private function reportData(string $type): array
    {
        $orgId = $this->organizationId();
        $summary = $this->summaryData();

        return $summary + match ($type) {
            'building-income' => [
                'rows' => DB::table('buildings')
                    ->leftJoin('units', 'units.building_id', '=', 'buildings.id')
                    ->leftJoin('contracts', 'contracts.unit_id', '=', 'units.id')
                    ->leftJoin('payments', function ($join) {
                        $join->on('payments.contract_id', '=', 'contracts.id')
                            ->where('payments.status', '=', 'paid');
                    })
                    ->where('buildings.organization_id', $orgId)
                    ->select('buildings.name', DB::raw('COALESCE(SUM(payments.amount_paid), 0) as income'))
                    ->groupBy('buildings.id', 'buildings.name')
                    ->get(),
            ],
            'unit-statement' => [
                'rows' => Unit::with('building', 'contracts.payments')
                    ->whereHas('building', fn ($query) => $query->where('organization_id', $orgId))
                    ->get(),
            ],
            'expenses' => [
                'rows' => Expense::with('building', 'unit')->where('organization_id', $orgId)->latest('expense_date')->get(),
            ],
            'overdue' => [
                'rows' => Payment::with('contract.tenant', 'contract.unit.building')
                    ->where('organization_id', $orgId)
                    ->where('due_date', '<', now()->toDateString())
                    ->where('status', '!=', 'paid')
                    ->orderBy('due_date')
                    ->get(),
            ],
            default => [
                'rows' => Payment::with('contract.unit.building', 'contract.tenant')
                    ->where('organization_id', $orgId)
                    ->orderBy('due_date')
                    ->get(),
            ],
        };
    }
}
