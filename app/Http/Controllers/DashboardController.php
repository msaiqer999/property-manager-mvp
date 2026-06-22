<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesOrganization;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Payment;
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
        $expiringSoon = Contract::with(['tenant', 'unit.building'])
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', now()->addDays(90)->toDateString())
            ->orderBy('end_date')
            ->get();
        $expiryCounts = [
            '30' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '30')->count(),
            '60' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '60')->count(),
            '90' => $expiringSoon->filter(fn (Contract $contract) => $contract->expiryWarningGroup() === '90')->count(),
        ];

        return view('dashboard', [
            'monthlyIncome' => Payment::where('organization_id', $orgId)
                ->where('amount_paid', '>', 0)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount_paid'),
            'monthlyExpenses' => Expense::where('organization_id', $orgId)->notVoided()->whereBetween('expense_date', [$start, $end])->sum('amount'),
            'overdueAmount' => Payment::where('organization_id', $orgId)
                ->where('status', 'overdue')
                ->sum(DB::raw('amount_due - amount_paid')),
            'vacantUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'vacant')->count(),
            'rentedUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'rented')->count(),
            'expiringSoon' => $expiringSoon->take(5),
            'expiryCounts' => $expiryCounts,
            'latestPayments' => Payment::where('organization_id', $orgId)->latest()->take(5)->get(),
            'latestExpenses' => Expense::where('organization_id', $orgId)->notVoided()->latest()->take(5)->get(),
        ]);
    }
}
