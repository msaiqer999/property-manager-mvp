<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\SupportedLocales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0F4C5C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ __('app.name') }}">
    <title>{{ __('app.name') }}</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon-180x180.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-background text-brand-text antialiased">
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
                $feedbackButtonLabel = trans('feedback.button', [], app()->getLocale());
                $helpButtonLabel = trans('app.help.button', [], app()->getLocale());

                if ($pageHelpKey === 'expenses' && ! auth()->user()->can('create', \App\Models\Expense::class)) {
                    $pageHelp['can'] = __('app.help.pages.expenses.can_view_only');
                    $pageHelp['next'] = __('app.help.pages.expenses.next_view_only');
                }
            @endphp
            <header class="app-header sticky top-0 z-20">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <x-app-identity :href="route('dashboard')" class="text-base sm:text-lg" />
                    <div class="hidden items-center gap-2 sm:flex">
                        <button type="button" data-feedback-open class="btn-secondary tap-target px-3">{{ $feedbackButtonLabel }}</button>
                        <button type="button" data-help-open class="btn-secondary tap-target px-3">{{ $helpButtonLabel }}</button>
                        <a href="{{ route('password.change') }}" class="btn-secondary tap-target px-3">{{ __('app.auth.change_password') }}</a>
                        <x-language-switcher />
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn-secondary tap-target px-3">{{ __('app.logout') }}</button>
                        </form>
                    </div>
                    <details data-mobile-navigation class="group relative shrink-0 sm:hidden">
                        <summary data-mobile-menu-control aria-label="{{ __('app.toggle_navigation') }}" class="btn-secondary tap-target flex min-h-11 cursor-pointer list-none items-center gap-2 px-4 [&::-webkit-details-marker]:hidden">
                            <span class="group-open:hidden">{{ __('app.menu') }}</span>
                            <span class="hidden group-open:inline">{{ __('app.close') }}</span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" class="size-4 shrink-0 fill-current">
                                <path d="M3 5h14v2H3V5Zm0 4h14v2H3V9Zm0 4h14v2H3v-2Z"/>
                            </svg>
                        </summary>
                        <div class="surface-card fixed inset-x-2 top-16 z-30 shadow-lg">
                            <nav aria-label="{{ __('app.mobile_navigation_label') }}" class="grid grid-cols-1 gap-2 p-3 text-sm">
                                @foreach($navigation as $label => $path)
                                    @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                                    <a
                                        data-mobile-nav-link
                                        class="app-nav-link tap-target min-h-11 min-w-0 w-full justify-start whitespace-normal break-words px-4 text-start {{ $isActive ? 'app-nav-link-active' : '' }}"
                                        href="{{ url($path) }}"
                                        @if($isActive) aria-current="page" data-active-navigation data-mobile-nav-current @endif
                                    >{{ __('app.navigation.'.$label) }}</a>
                                @endforeach
                            </nav>
                            <div class="grid gap-2 border-t p-3">
                                <button type="button" data-feedback-open class="btn-secondary tap-target min-h-11 w-full px-4">{{ $feedbackButtonLabel }}</button>
                                <button type="button" data-help-open class="btn-secondary tap-target min-h-11 w-full px-4">{{ $helpButtonLabel }}</button>
                                <a href="{{ route('password.change') }}" class="btn-secondary tap-target min-h-11 w-full px-4">{{ __('app.auth.change_password') }}</a>
                                <x-language-switcher />
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="btn-secondary tap-target min-h-11 w-full px-4">{{ __('app.logout') }}</button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
                <nav data-desktop-navigation class="scrollbar-soft mx-auto hidden max-w-6xl gap-2 overflow-x-auto px-4 pb-3 text-sm sm:flex" aria-label="{{ __('app.navigation_label') }}">
                    @foreach($navigation as $label => $path)
                        @php($isActive = $path === '/' ? request()->routeIs('dashboard') : request()->is($path) || request()->is($path.'/*'))
                        <a
                            class="app-nav-link tap-target px-4 {{ $isActive ? 'app-nav-link-active' : '' }}"
                            href="{{ url($path) }}"
                            @if($isActive) aria-current="page" data-active-navigation @endif
                        >{{ __('app.navigation.'.$label) }}</a>
                    @endforeach
                </nav>
            </header>
            <div
                data-help-panel
                data-page-help-key="{{ $pageHelpKey }}"
                class="fixed inset-0 z-50 hidden bg-brand-overlay p-3 sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="page-help-title"
            >
                <div class="ms-auto flex min-h-full max-w-md items-end sm:items-center">
                    <section class="surface-card w-full p-5 shadow-2xl sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p data-first-visit-label class="mb-1 hidden text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ __('app.help.first_visit') }}</p>
                                <h2 id="page-help-title" class="text-lg font-semibold">{{ $pageHelp['title'] ?? __('app.help.pages.default.title') }}</h2>
                            </div>
                            <button type="button" data-help-close aria-label="{{ __('app.close') }}" class="btn-secondary tap-target min-h-11 min-w-11 px-0">&times;</button>
                        </div>
                        <div class="mt-4 space-y-4 text-sm text-brand-muted">
                            <div>
                                <h3 class="font-semibold text-brand-text">{{ __('app.help.what_for') }}</h3>
                                <p class="mt-1">{{ $pageHelp['for'] ?? __('app.help.pages.default.for') }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-brand-text">{{ __('app.help.can_do') }}</h3>
                                <p class="mt-1">{{ $pageHelp['can'] ?? __('app.help.pages.default.can') }}</p>
                            </div>
                            <div>
                                <h3 class="font-semibold text-brand-text">{{ __('app.help.next_action') }}</h3>
                                <p class="mt-1">{{ $pageHelp['next'] ?? __('app.help.pages.default.next') }}</p>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-2 sm:flex sm:justify-end">
                            <button type="button" data-help-got-it class="btn-primary tap-target min-h-11 px-4">{{ __('app.help.got_it') }}</button>
                            <button type="button" data-help-dont-show class="btn-secondary tap-target min-h-11 px-4">{{ __('app.help.dont_show_again') }}</button>
                        </div>
                    </section>
                </div>
            </div>
            <div
                data-feedback-panel
                class="fixed inset-0 z-50 hidden bg-brand-overlay p-3 sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="feedback-title"
            >
                <div class="mx-auto flex min-h-full max-w-md items-end sm:items-center">
                    <section class="surface-card w-full p-5 shadow-2xl sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 id="feedback-title" class="text-lg font-semibold">{{ __('feedback.title') }}</h2>
                                <p class="mt-1 text-sm text-brand-muted">{{ __('feedback.intro') }}</p>
                            </div>
                            <button type="button" data-feedback-close aria-label="{{ __('app.close') }}" class="btn-secondary tap-target min-h-11 min-w-11 px-0">&times;</button>
                        </div>
                        <form method="post" action="{{ route('feedback.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <label class="form-label">
                                {{ __('feedback.page_url') }}
                                <input type="text" name="page_url" data-feedback-page-url value="{{ url()->current() }}" readonly class="form-control mt-1 bg-brand-background text-sm text-brand-muted" dir="ltr">
                            </label>
                            <label class="form-label">
                                {{ __('feedback.type') }}
                                <select name="type" class="form-control form-select-safe tap-target mt-1" required>
                                    @foreach(['bug', 'confusion', 'suggestion', 'other'] as $type)
                                        <option value="{{ $type }}">{{ __('feedback.types.'.$type) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="form-label">
                                {{ __('feedback.message') }}
                                <textarea name="message" rows="4" class="form-control tap-target mt-1" required></textarea>
                            </label>
                            <label class="form-label">
                                {{ __('feedback.screenshot_note') }}
                                <span class="text-xs font-normal text-brand-muted">({{ __('feedback.optional') }})</span>
                                <textarea name="screenshot_note" rows="2" class="form-control tap-target mt-1" placeholder="{{ __('feedback.screenshot_note_placeholder') }}"></textarea>
                            </label>
                            <button class="btn-primary tap-target min-h-11 w-full px-4">{{ __('feedback.submit') }}</button>
                            @if(in_array(auth()->user()?->role?->value, ['owner', 'manager'], true))
                                <a class="btn-secondary tap-target min-h-11 w-full px-4" href="{{ route('feedback.index') }}">{{ __('feedback.inbox_link') }}</a>
                            @endif
                        </form>
                    </section>
                </div>
            </div>
        @else
            <header class="app-header">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                    <x-app-identity :href="route('login')" class="text-base" />
                    <x-language-switcher class="shrink-0" />
                </div>
            </header>
        @endauth

        <main class="mx-auto max-w-6xl px-4 py-4 sm:py-6">
            @if ($errors->any())
                <div class="alert-danger mb-4">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('status'))
                <div class="alert-success mb-4">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
        @auth
            <footer data-app-legal-links class="mx-auto max-w-6xl px-4 pb-8 text-sm text-brand-muted">
                <nav aria-label="{{ __('legal.links.label') }}" class="flex flex-wrap gap-x-4 gap-y-2">
                    <a class="link-primary" href="{{ route('legal.beta') }}">{{ __('legal.links.beta') }}</a>
                    <a class="link-primary" href="{{ route('legal.privacy') }}">{{ __('legal.links.privacy') }}</a>
                    <a class="link-primary" href="{{ route('legal.terms') }}">{{ __('legal.links.terms') }}</a>
                </nav>
            </footer>
        @endauth
    </div>
    @auth
        <script>
            (() => {
                const panel = document.querySelector('[data-help-panel]');
                if (! panel) return;

                const pageKey = panel.getAttribute('data-page-help-key') || 'default';
                const key = `property-manager-help:${pageKey}`;
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

            (() => {
                const panel = document.querySelector('[data-feedback-panel]');
                if (! panel) return;

                const safeCurrentPage = () => {
                    const url = new URL(window.location.href);
                    ['token', 'pass' + 'word', 'sec' + 'ret', 'cookie', 'session', 'api_key', 'apikey'].forEach((key) => {
                        url.searchParams.delete(key);
                    });

                    return url.toString();
                };
                const openPanel = () => {
                    const pageUrl = panel.querySelector('[data-feedback-page-url]');
                    if (pageUrl) {
                        pageUrl.value = safeCurrentPage();
                    }

                    panel.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                };
                const closePanel = () => {
                    panel.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                };

                document.querySelectorAll('[data-feedback-open]').forEach((button) => {
                    button.addEventListener('click', openPanel);
                });
                panel.querySelectorAll('[data-feedback-close]').forEach((button) => {
                    button.addEventListener('click', closePanel);
                });
                panel.addEventListener('click', (event) => {
                    if (event.target === panel) {
                        closePanel();
                    }
                });
            })();
        </script>
    @endauth
</body>
</html>
