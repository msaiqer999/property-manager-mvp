<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\DashboardAuthorization;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ScopesOrganization;

    public function __invoke(DashboardAuthorization $authorization)
    {
        abort_unless($authorization->viewDashboard(auth()->user()), 403);

        $orgId = $this->organizationId();
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $today = now()->toDateString();
        $dueSoonEnd = now()->addDays(7)->toDateString();
        $expiringSoon = Contract::with(['tenant', 'unit.building'])
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', now()->addDays(90)->toDateString())
            ->orderBy('end_date')
            ->get();
        $expiryCounts = [
            '30' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '30')->count(),
            '60' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '60')->count(),
            '90' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '90')->count(),
        ];
        $buildings = Building::where('organization_id', $orgId)->orderBy('name')->get();
        $unitCount = Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->count();
        $tenantCount = Tenant::where('organization_id', $orgId)->count();
        $contractCount = Contract::where('organization_id', $orgId)->count();
        $pendingPaymentCount = Payment::where('organization_id', $orgId)
            ->whereIn('status', ['pending', 'partial'])
            ->count();
        $partialPaymentCount = Payment::where('organization_id', $orgId)
            ->where('status', 'partial')
            ->count();
        $recordedPaymentCount = Payment::where('organization_id', $orgId)
            ->where(function ($query) {
                $query->where('amount_paid', '>', 0)
                    ->orWhere('status', 'paid');
            })
            ->count();
        $overduePaymentsQuery = Payment::where('organization_id', $orgId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('due_date', '<=', $today)
            ->whereColumn('amount_paid', '<', 'amount_due');
        $paymentsDueSoonQuery = Payment::where('organization_id', $orgId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('due_date', '>', $today)
            ->whereDate('due_date', '<=', $dueSoonEnd)
            ->whereColumn('amount_paid', '<', 'amount_due');

        return view('dashboard', [
            'role' => auth()->user()->role,
            'monthlyIncome' => Payment::where('organization_id', $orgId)
                ->where('amount_paid', '>', 0)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount_paid'),
            'monthlyExpenses' => Expense::where('organization_id', $orgId)->notVoided()->whereBetween('expense_date', [$start, $end])->sum('amount'),
            'overdueAmount' => (clone $overduePaymentsQuery)->sum(DB::raw('amount_due - amount_paid')),
            'overduePaymentCount' => (clone $overduePaymentsQuery)->count(),
            'nearestOverduePaymentDate' => optional((clone $overduePaymentsQuery)->orderBy('due_date')->first())->due_date,
            'paymentsDueSoonCount' => (clone $paymentsDueSoonQuery)->count(),
            'nearestPaymentDueDate' => optional((clone $paymentsDueSoonQuery)->orderBy('due_date')->first())->due_date,
            'vacantUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'vacant')->count(),
            'rentedUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'rented')->count(),
            'buildingCount' => $buildings->count(),
            'unitCount' => $unitCount,
            'tenantCount' => $tenantCount,
            'contractCount' => $contractCount,
            'recordedPaymentCount' => $recordedPaymentCount,
            'firstBuilding' => $buildings->first(),
            'expiringSoon' => $expiringSoon->take(5),
            'expiryCounts' => $expiryCounts,
            'expiringSoonCount' => $expiringSoon->count(),
            'nearestContractExpiryDate' => optional($expiringSoon->first())->end_date,
            'pendingPaymentCount' => $pendingPaymentCount,
            'partialPaymentCount' => $partialPaymentCount,
            'nextPaymentToRecord' => Payment::where('organization_id', $orgId)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->orderBy('due_date')
                ->first(),
            'latestPayments' => Payment::where('organization_id', $orgId)->latest()->take(5)->get(),
            'latestExpenses' => Expense::where('organization_id', $orgId)->notVoided()->latest()->take(5)->get(),
        ]);
    }
}
