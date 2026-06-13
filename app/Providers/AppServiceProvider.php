<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            $legacyRouteAbilities = [
                'manage-properties',
                'manage-tenants',
                'manage-contracts',
                'view-payments',
                'record-payment',
                'view-expenses',
                'view-reports',
                'manage-users',
            ];

            if (! in_array($ability, $legacyRouteAbilities, true)) {
                return null;
            }

            return $user->role->can($ability) ? true : null;
        });
    }
}
