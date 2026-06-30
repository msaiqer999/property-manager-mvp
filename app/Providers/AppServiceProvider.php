<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole() && request()->headers->get('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($this->normalizedEmailIpKey($request));
        });

        RateLimiter::for('password-reset-email', function (Request $request) {
            return Limit::perMinute(3)->by($this->normalizedEmailIpKey($request));
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $token = hash('sha256', (string) $request->input('token', ''));

            return Limit::perMinute(5)->by($token.'|'.$request->ip());
        });

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

    private function normalizedEmailIpKey(Request $request): string
    {
        return Str::lower(trim((string) $request->input('email'))).'|'.$request->ip();
    }
}
