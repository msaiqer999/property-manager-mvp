<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.registration_enabled', true), 404);

        return $next($request);
    }
}
