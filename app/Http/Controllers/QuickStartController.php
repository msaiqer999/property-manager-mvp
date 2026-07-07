<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;

class QuickStartController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $organizationId = $user->organization_id;

        $counts = [
            'building' => Building::where('organization_id', $organizationId)->count(),
            'units' => Unit::whereHas('building', fn ($query) => $query->where('organization_id', $organizationId))->count(),
            'tenant' => Tenant::where('organization_id', $organizationId)->count(),
            'contract' => Contract::where('organization_id', $organizationId)->count(),
            'payment' => Payment::where('organization_id', $organizationId)->count(),
            'expense' => Expense::where('organization_id', $organizationId)->count(),
        ];

        $progressSteps = [
            'building' => $counts['building'] > 0,
            'units' => $counts['units'] > 0,
            'tenant' => $counts['tenant'] > 0,
            'contract' => $counts['contract'] > 0,
            'payment' => $counts['payment'] > 0,
            'expense' => $counts['expense'] > 0,
        ];

        $nextStep = match (true) {
            ! $progressSteps['building'] => 'building',
            ! $progressSteps['units'] => 'units',
            ! $progressSteps['tenant'] => 'tenant',
            ! $progressSteps['contract'] => 'contract',
            ! $progressSteps['payment'] => 'payment',
            ! $progressSteps['expense'] => 'expense',
            default => 'report',
        };

        return view('quick-start.index', [
            'counts' => $counts,
            'progressSteps' => $progressSteps,
            'completedSetupSteps' => collect($progressSteps)->filter()->count(),
            'totalSetupSteps' => count($progressSteps),
            'nextStep' => $nextStep,
        ]);
    }
}
