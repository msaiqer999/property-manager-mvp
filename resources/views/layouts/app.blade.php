<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ __('app.name') }}">
    <title>{{ __('app.name') }}</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon-180x180.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen pb-20 sm:pb-0">
        @auth
            @php
                $role = auth()->user()->role;
                $navigation = ['dashboard' => '/'];

                if ($role->can('manage-properties')) {
                    $navigation += ['units' => 'units', 'buildings' => 'buildings'];
                }

                if ($role->can('view-payments')) {
                    $navigation += ['payments' => 'payments'];
                }

                if ($role->can('manage-contracts')) {
                    $navigation += ['contracts' => 'contracts'];
                }

                if ($role->can('manage-tenants')) {
                    $navigation += ['tenants' => 'tenants'];
                }

                if ($role->can('view-expenses')) {
                    $navigation += ['expenses' => 'expenses'];
                }

                if ($role->can('view-reports')) {
                    $navigation += ['reports' => 'reports'];
                }

                if ($role->can('manage-users')) {
                    $navigation += ['users' => 'users', 'activity' => 'activity-logs'];
                }
            @endphp
            <header class="sticky top-0 z-20 border-b bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <a href="{{ route('dashboard') }}" class="min-w-0 break-words text-base font-semibold leading-tight sm:text-lg">{{ __('app.name') }}</a>
                    <div class="hidden items-center gap-2 sm:flex">
                        <x-language-switcher />
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="tap-target rounded border px-3 text-sm text-slate-700">{{ __('app.logout') }}</button>
                        </form>
                    </div>
                    <details data-mobile-navigation class="group relative shrink-0 sm:hidden">
                        <summary data-mobile-menu-control aria-label="{{ __('app.toggle_navigation') }}" class="tap-target flex min-h-11 cursor-pointer list-none items-center gap-2 rounded border px-4 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 [&::-webkit-details-marker]:hidden">
                            <span class="group-open:hidden">{{ __('app.menu') }}</span>
                            <span class="hidden group-open:inline">{{ __('app.close') }}</span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="size-4 shrink-0 fill-current">
                                <path d="M3 5h14v2H3V5Zm0 4h14v2H3V9Zm0 4h14v2H3v-2Z"/>
                            </svg>
                        </summary>
                        <div class="fixed inset-x-2 top-16 z-30 rounded border bg-white shadow-lg">
                            <nav aria-label="{{ __('app.mobile_navigation_label') }}" class="grid grid-cols-1 gap-2 p-3 text-sm">
                                @foreach($navigation as $label => $path)
                                    @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                                    <a
                                        data-mobile-nav-link
                                        class="tap-target flex min-h-11 min-w-0 w-full items-center justify-start whitespace-normal break-words rounded border px-4 text-start font-medium {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}"
                                        href="{{ url($path) }}"
                                        @if($isActive) aria-current="page" data-active-navigation data-mobile-nav-current @endif
                                    >{{ __('app.navigation.'.$label) }}</a>
                                @endforeach
                            </nav>
                            <div class="grid gap-2 border-t p-3">
                                <x-language-switcher />
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="tap-target min-h-11 w-full rounded border px-4 text-sm font-medium text-slate-700">{{ __('app.logout') }}</button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
                <nav data-desktop-navigation class="scrollbar-soft mx-auto hidden max-w-6xl gap-2 overflow-x-auto px-4 pb-3 text-sm sm:flex" aria-label="{{ __('app.navigation_label') }}">
                    @foreach($navigation as $label => $path)
                        @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                        <a
                            class="tap-target flex shrink-0 items-center rounded border px-4 font-medium {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}"
                            href="{{ url($path) }}"
                            @if($isActive) aria-current="page" data-active-navigation @endif
                        >{{ __('app.navigation.'.$label) }}</a>
                    @endforeach
                </nav>
            </header>
        @else
            <header class="border-b bg-white">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <a href="{{ route('login') }}" class="min-w-0 break-words text-base font-semibold leading-tight">{{ __('app.name') }}</a>
                    <x-language-switcher class="shrink-0" />
                </div>
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
