@extends('layouts.app')

@section('content')
@php
    $registrationEnabled = config('app.registration_enabled', true) && \Illuminate\Support\Facades\Route::has('register');
    $plansRoute = collect(['plans.index', 'packages.index', 'pricing'])
        ->first(fn ($route) => \Illuminate\Support\Facades\Route::has($route));
@endphp

<section class="overflow-hidden rounded-lg border border-brand-border bg-brand-surface shadow-xl">
    <div class="grid lg:grid-cols-2">
        <div class="bg-brand-primary-hover px-5 py-8 text-white sm:px-8 lg:flex lg:min-h-[34rem] lg:flex-col lg:justify-between lg:px-10 lg:py-10">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-lg font-bold leading-tight text-white">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-brand-surface text-sm font-black text-brand-text">PM</span>
                    <span>{{ __('app.name') }}</span>
                </a>

                <div class="mt-7 max-w-xl">
                    <h1 class="text-3xl font-bold leading-tight text-white sm:text-5xl">{{ __('landing.hero_title') }}</h1>
                    <p class="mt-5 text-base leading-7 text-brand-accent-soft sm:text-lg">{{ __('landing.hero_subtitle') }}</p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-brand-accent-soft">{{ __('landing.benefits_title') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach(__('landing.benefits') as $benefit)
                        <article class="rounded-lg border border-brand-primary-hover bg-brand-primary p-4">
                            <p class="text-sm font-semibold leading-6 text-white">{{ $benefit }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="login-card" class="flex items-center bg-brand-surface px-5 py-8 sm:px-8 lg:min-h-[34rem] lg:px-10">
            <div class="mx-auto w-full max-w-md rounded-lg border border-brand-border bg-brand-surface p-6 shadow-xl sm:p-7">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-brand-primary">{{ __('app.name') }}</p>
                    <h2 class="mt-2 text-3xl font-bold text-brand-text">{{ __('app.auth.login') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-brand-muted">{{ __('landing.cta_body') }}</p>
                </div>

                <form method="post" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <label class="block text-sm font-medium text-brand-text">
                        {{ __('app.auth.email') }}
                        <input name="email" type="email" value="{{ old('email') }}" class="tap-target mt-2 min-h-12 w-full rounded-lg border-brand-border p-3" required autofocus autocomplete="username">
                    </label>

                    <label class="block text-sm font-medium text-brand-text">
                        {{ __('app.auth.password') }}
                        <input name="password" type="password" class="tap-target mt-2 min-h-12 w-full rounded-lg border-brand-border p-3" required autocomplete="current-password">
                    </label>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm text-brand-text">
                            <input name="remember" type="checkbox" class="rounded border-brand-border text-brand-text">
                            <span>{{ __('app.auth.remember_me') }}</span>
                        </label>

                        @if(\Illuminate\Support\Facades\Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-primary">{{ __('app.auth.forgot_password') }}</a>
                        @endif
                    </div>

                    <button class="tap-target min-h-12 w-full rounded-lg bg-brand-primary-hover px-5 text-sm font-semibold text-white shadow-sm">{{ __('app.auth.login') }}</button>
                </form>

                <div class="mt-5 grid gap-2 text-center text-sm">
                    @if($registrationEnabled)
                        <a href="{{ route('register') }}" class="tap-target inline-flex min-h-11 items-center justify-center rounded-lg border border-brand-border bg-brand-surface px-4 font-semibold text-brand-text">{{ __('app.auth.create_account') }}</a>
                    @else
                        <a href="mailto:demo@property-manager.local?subject=Property%20Manager%20Demo" class="tap-target inline-flex min-h-11 items-center justify-center rounded-lg border border-brand-border bg-brand-surface px-4 font-semibold text-brand-text">{{ __('landing.request_demo') }}</a>
                    @endif

                    @if($plansRoute)
                        <a href="{{ route($plansRoute) }}" class="tap-target inline-flex min-h-11 items-center justify-center rounded px-4 font-medium text-brand-primary">{{ __('landing.plans') }}</a>
                    @else
                        <p class="text-xs font-medium text-brand-muted">{{ __('landing.plans_soon') }}</p>
                    @endif

                    <nav data-landing-legal-links aria-label="{{ __('legal.links.label') }}" class="flex flex-wrap justify-center gap-x-3 gap-y-1 text-xs">
                        <a href="{{ route('legal.beta') }}" class="font-medium text-brand-primary">{{ __('legal.links.beta') }}</a>
                        <a href="{{ route('legal.privacy') }}" class="font-medium text-brand-primary">{{ __('legal.links.privacy') }}</a>
                        <a href="{{ route('legal.terms') }}" class="font-medium text-brand-primary">{{ __('legal.links.terms') }}</a>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
