<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\SupportedLocales::direction(app()->getLocale()) }}">
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

                $pageHelpKey = match (true) {
                    request()->routeIs('dashboard') || request()->routeIs('dashboard.show') => 'dashboard',
                    request()->is('buildings') || request()->is('buildings/*') => 'buildings',
                    request()->is('units') || request()->is('units/*') => 'units',
                    request()->is('tenants') || request()->is('tenants/*') => 'tenants',
                    request()->is('contracts') || request()->is('contracts/*') => 'contracts',
                    request()->is('payments') || request()->is('payments/*') => 'payments',
                    request()->is('expenses') || request()->is('expenses/*') => 'expenses',
                    request()->is('reports') || request()->is('reports/*') => 'reports',
                    default => 'default',
                };
                $pageHelp = trans("app.help.pages.{$pageHelpKey}");
                $pageHelp = is_array($pageHelp) ? $pageHelp : trans('app.help.pages.default');

                if ($pageHelpKey === 'expenses' && ! auth()->user()->can('create', \App\Models\Expense::class)) {
                    $pageHelp['can'] = __('app.help.pages.expenses.can_view_only');
                    $pageHelp['next'] = __('app.help.pages.expenses.next_view_only');
                }
            @endphp
            <header class="sticky top-0 z-20 border-b bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <a href="{{ route('dashboard') }}" class="min-w-0 break-words text-base font-semibold leading-tight sm:text-lg">{{ __('app.name') }}</a>
                    <div class="hidden items-center gap-2 sm:flex">
                        <button type="button" data-help-open class="tap-target rounded border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700">{{ __('app.help.button') }}</button>
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
                                <button type="button" data-help-open class="tap-target min-h-11 w-full rounded border px-4 text-sm font-medium text-slate-700">{{ __('app.help.button') }}</button>
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
            <div
                data-help-panel
                data-page-help-key="{{ $pageHelpKey }}"
                class="fixed inset-0 z-50 hidden bg-slate-950/50 p-3 sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="page-help-title"
            >
                <div class="ms-auto flex min-h-full max-w-md items-end sm:items-center">
                    <section class="w-full rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p data-first-visit-label class="mb-1 hidden text-xs font-semibold uppercase tracking-wide text-blue-700">{{ __('app.help.first_visit') }}</p>
                                <h2 id="page-help-title" class="text-lg font-semibold">{{ $pageHelp['title'] ?? __('app.help.pages.default.title') }}</h2>
                            </div>
                            <button type="button" data-help-close aria-label="{{ __('app.close') }}" class="tap-target inline-flex min-h-11 min-w-11 items-center justify-center rounded border text-slate-700">×</button>
                        </div>
                        <div class="mt-4 space-y-4 text-sm text-slate-700">
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ __('app.help.what_for') }}</h3>
                                <p class="mt-1">{{ $pageHelp['for'] ?? __('app.help.pages.default.for') }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ __('app.help.can_do') }}</h3>
                                <p class="mt-1">{{ $pageHelp['can'] ?? __('app.help.pages.default.can') }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ __('app.help.next_action') }}</h3>
                                <p class="mt-1">{{ $pageHelp['next'] ?? __('app.help.pages.default.next') }}</p>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-2 sm:flex sm:justify-end">
                            <button type="button" data-help-got-it class="tap-target min-h-11 rounded bg-slate-900 px-4 text-sm font-medium text-white">{{ __('app.help.got_it') }}</button>
                            <button type="button" data-help-dont-show class="tap-target min-h-11 rounded border px-4 text-sm font-medium text-slate-700">{{ __('app.help.dont_show_again') }}</button>
                        </div>
                    </section>
                </div>
            </div>
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
    @auth
        <script>
            (() => {
                const panel = document.querySelector('[data-help-panel]');
                if (! panel) return;

                const key = `property-manager-help:${panel.dataset.pageHelpKey}`;
                const firstVisitLabel = panel.querySelector('[data-first-visit-label]');
                const openPanel = (firstVisit = false) => {
                    firstVisitLabel?.classList.toggle('hidden', ! firstVisit);
                    panel.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                };
                const closePanel = (remember = false) => {
                    if (remember) {
                        localStorage.setItem(key, 'dismissed');
                    }

                    panel.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                };

                document.querySelectorAll('[data-help-open]').forEach((button) => {
                    button.addEventListener('click', () => openPanel(false));
                });

                panel.querySelectorAll('[data-help-close]').forEach((button) => {
                    button.addEventListener('click', () => closePanel(false));
                });

                panel.querySelector('[data-help-got-it]')?.addEventListener('click', () => closePanel(true));
                panel.querySelector('[data-help-dont-show]')?.addEventListener('click', () => closePanel(true));
                panel.addEventListener('click', (event) => {
                    if (event.target === panel) {
                        closePanel(false);
                    }
                });

                if (! localStorage.getItem(key)) {
                    window.setTimeout(() => openPanel(true), 350);
                }
            })();
        </script>
    @endauth
</body>
</html>
