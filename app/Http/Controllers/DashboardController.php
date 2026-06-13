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

        return view('dashboard', [
            'monthlyIncome' => Payment::where('organization_id', $orgId)->where('status', 'paid')->whereBetween('payment_date', [$start, $end])->sum('amount_paid'),
            'monthlyExpenses' => Expense::where('organization_id', $orgId)->whereBetween('expense_date', [$start, $end])->sum('amount'),
            'overdueAmount' => Payment::where('organization_id', $orgId)
                ->where('due_date', '<', now()->toDateString())
                ->where('status', '!=', 'paid')
                ->sum(DB::raw('amount_due - amount_paid')),
            'vacantUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'vacant')->count(),
            'rentedUnits' => Unit::whereHas('building', fn ($q) => $q->where('organization_id', $orgId))->where('status', 'rented')->count(),
            'endingSoon' => Contract::where('organization_id', $orgId)->whereBetween('end_date', [now(), now()->addDays(45)])->get(),
            'latestPayments' => Payment::where('organization_id', $orgId)->latest()->take(5)->get(),
            'latestExpenses' => Expense::where('organization_id', $orgId)->latest()->take(5)->get(),
        ]);
    }
}
