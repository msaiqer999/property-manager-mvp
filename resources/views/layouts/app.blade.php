<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Property Manager">
    <title>{{ config('app.name', 'Property Manager') }}</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon-180x180.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen pb-20 sm:pb-0">
        @auth
            <header class="sticky top-0 z-20 border-b bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <a href="{{ route('dashboard') }}" class="text-base font-semibold leading-tight">Property Manager</a>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="tap-target rounded border px-3 text-sm text-slate-700">Logout</button>
                    </form>
                </div>
                <nav class="scrollbar-soft mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 pb-3 text-sm" aria-label="Primary">
                    @php
                        $navigation = ['dashboard' => '/', 'units' => 'units', 'payments' => 'payments', 'contracts' => 'contracts', 'tenants' => 'tenants', 'expenses' => 'expenses', 'reports' => 'reports', 'buildings' => 'buildings', 'users' => 'users', 'activity' => 'activity-logs'];

                        if (auth()->user()->role->value !== 'owner') {
                            unset($navigation['users'], $navigation['activity']);
                        }
                    @endphp
                    @foreach($navigation as $label => $path)
                        <a class="tap-target flex shrink-0 items-center rounded border bg-white px-4 font-medium text-slate-700" href="{{ url($path) }}">{{ ucfirst($label) }}</a>
                    @endforeach
                </nav>
            </header>
        @endauth

        <main class="mx-auto max-w-6xl px-4 py-4 sm:py-6">
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
