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
        $user = $request->user();

        foreach ([
            $request->session()->get('locale'),
            $user?->preferred_locale,
            $user?->organization?->effectiveLocale(),
            config('app.locale', 'en'),
            'en',
        ] as $locale) {
            if (is_string($locale) && SupportedLocales::isSupported($locale)) {
                app()->setLocale($locale);

                return $next($request);
            }
        }

        app()->setLocale('en');

        return $next($request);
    }
}
