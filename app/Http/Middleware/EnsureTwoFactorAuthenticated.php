<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for users without 2FA enabled
        if (!$user || !$user->isTwoFactorEnabled()) {
            return $next($request);
        }

        // Allow if 2FA already verified in this session
        if (session()->has('auth.2fa_verified')) {
            return $next($request);
        }

        // Redirect to 2FA verification
        return redirect()->route('two-factor.verify')
            ->with('warning', 'يرجى إكمال المصادقة الثنائية');
    }
}
