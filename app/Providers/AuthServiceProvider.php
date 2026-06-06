<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\BuildingPolicy;
use App\Policies\ContractPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReportPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Building::class => BuildingPolicy::class,
        Unit::class => UnitPolicy::class,
        Tenant::class => TenantPolicy::class,
        Contract::class => ContractPolicy::class,
        Payment::class => PaymentPolicy::class,
        Expense::class => ExpensePolicy::class,
        Report::class => ReportPolicy::class,
        User::class => UserPolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
