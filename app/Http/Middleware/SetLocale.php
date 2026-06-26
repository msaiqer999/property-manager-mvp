<?php

namespace App\Http\Middleware;

use App\Support\SupportedLocales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if (! SupportedLocales::isSupported($locale)) {
            $locale = config('app.locale', 'en');
        }

        if (! SupportedLocales::isSupported($locale)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
