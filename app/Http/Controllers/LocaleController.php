<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['en', 'ar'], true), 404);

        $request->session()->put('locale', $locale);

        $previous = url()->previous();
        $origin = $request->getSchemeAndHttpHost();

        if ($previous !== $origin && ! str_starts_with($previous, $origin.'/')) {
            return redirect()->route($request->user() ? 'dashboard' : 'login');
        }

        return redirect()->to($previous);
    }
}
