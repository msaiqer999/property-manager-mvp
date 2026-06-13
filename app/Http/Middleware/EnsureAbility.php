<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        abort_unless($request->user()?->role->can($ability), 403);

        return $next($request);
    }
}
