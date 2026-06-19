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
            @php
                $navigation = ['dashboard' => '/', 'units' => 'units', 'payments' => 'payments', 'contracts' => 'contracts', 'tenants' => 'tenants', 'expenses' => 'expenses', 'reports' => 'reports', 'buildings' => 'buildings', 'users' => 'users', 'activity' => 'activity-logs'];

                if (auth()->user()->role->value !== 'owner') {
                    unset($navigation['users'], $navigation['activity']);
                }
            @endphp
            <header class="sticky top-0 z-20 border-b bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <a href="{{ route('dashboard') }}" class="min-w-0 break-words text-base font-semibold leading-tight">Property Manager</a>
                    <form method="post" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="tap-target rounded border px-3 text-sm text-slate-700">Logout</button>
                    </form>
                    <details data-mobile-navigation class="group relative shrink-0 sm:hidden">
                        <summary data-mobile-menu-control aria-label="Toggle navigation menu" class="tap-target flex cursor-pointer list-none items-center gap-2 rounded border px-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 [&::-webkit-details-marker]:hidden">
                            <span class="group-open:hidden">Menu</span>
                            <span class="hidden group-open:inline">Close</span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="size-4 shrink-0 fill-current">
                                <path d="M3 5h14v2H3V5Zm0 4h14v2H3V9Zm0 4h14v2H3v-2Z"/>
                            </svg>
                        </summary>
                        <div class="absolute end-0 top-[calc(100%+0.75rem)] w-72 max-w-[calc(100vw-2rem)] rounded border bg-white shadow-lg">
                            <nav aria-label="Mobile primary" class="grid grid-cols-1 gap-2 p-3 text-sm">
                                @foreach($navigation as $label => $path)
                                    @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                                    <a
                                        class="tap-target flex min-w-0 items-center break-words rounded border px-4 font-medium {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}"
                                        href="{{ url($path) }}"
                                        @if($isActive) aria-current="page" data-active-navigation @endif
                                    >{{ ucfirst($label) }}</a>
                                @endforeach
                            </nav>
                            <form method="post" action="{{ route('logout') }}" class="border-t p-3">
                                @csrf
                                <button class="tap-target w-full rounded border px-4 text-sm font-medium text-slate-700">Logout</button>
                            </form>
                        </div>
                    </details>
                </div>
                <nav data-desktop-navigation class="scrollbar-soft mx-auto hidden max-w-6xl gap-2 overflow-x-auto px-4 pb-3 text-sm sm:flex" aria-label="Primary">
                    @foreach($navigation as $label => $path)
                        @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                        <a
                            class="tap-target flex shrink-0 items-center rounded border px-4 font-medium {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}"
                            href="{{ url($path) }}"
                            @if($isActive) aria-current="page" data-active-navigation @endif
                        >{{ ucfirst($label) }}</a>
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
