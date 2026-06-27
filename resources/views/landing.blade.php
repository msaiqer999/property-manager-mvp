@extends('layouts.app')

@section('content')
@php
    $registrationEnabled = config('app.registration_enabled', true) && \Illuminate\Support\Facades\Route::has('register');
    $plansRoute = collect(['plans.index', 'packages.index', 'pricing'])
        ->first(fn ($route) => \Illuminate\Support\Facades\Route::has($route));
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
    <div class="grid lg:grid-cols-2">
        <div class="bg-slate-950 px-5 py-8 text-white sm:px-8 lg:flex lg:min-h-[34rem] lg:flex-col lg:justify-between lg:px-10 lg:py-10">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 text-lg font-bold leading-tight text-white">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sm font-black text-slate-950">PM</span>
                    <span>{{ __('app.name') }}</span>
                </a>

                <div class="mt-7 max-w-xl">
                    <h1 class="text-3xl font-bold leading-tight text-white sm:text-5xl">{{ __('landing.hero_title') }}</h1>
                    <p class="mt-5 text-base leading-7 text-slate-200 sm:text-lg">{{ __('landing.hero_subtitle') }}</p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-blue-200">{{ __('landing.benefits_title') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach(__('landing.benefits') as $benefit)
                        <article class="rounded-2xl border border-blue-900 bg-slate-900 p-4">
                            <p class="text-sm font-semibold leading-6 text-white">{{ $benefit }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="login-card" class="flex items-center bg-white px-5 py-8 sm:px-8 lg:min-h-[34rem] lg:px-10">
            <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-blue-700">{{ __('app.name') }}</p>
                    <h2 class="mt-2 text-3xl font-bold text-slate-950">{{ __('app.auth.login') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('landing.cta_body') }}</p>
                </div>

                <form method="post" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('app.auth.email') }}
                        <input name="email" type="email" value="{{ old('email') }}" class="tap-target mt-2 min-h-12 w-full rounded-lg border-slate-300 p-3" required autofocus autocomplete="username">
                    </label>

                    <label class="block text-sm font-medium text-slate-700">
                        {{ __('app.auth.password') }}
                        <input name="password" type="password" class="tap-target mt-2 min-h-12 w-full rounded-lg border-slate-300 p-3" required autocomplete="current-password">
                    </label>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input name="remember" type="checkbox" class="rounded border-slate-300 text-slate-900">
                            <span>{{ __('app.auth.remember_me') }}</span>
                        </label>

                        @if(\Illuminate\Support\Facades\Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-700">{{ __('app.auth.forgot_password') }}</a>
                        @endif
                    </div>

                    <button class="tap-target min-h-12 w-full rounded-lg bg-slate-950 px-5 text-sm font-semibold text-white shadow-sm">{{ __('app.auth.login') }}</button>
                </form>

                <div class="mt-5 grid gap-2 text-center text-sm">
                    @if($registrationEnabled)
                        <a href="{{ route('register') }}" class="tap-target inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">{{ __('app.auth.create_account') }}</a>
                    @else
                        <a href="mailto:demo@property-manager.local?subject=Property%20Manager%20Demo" class="tap-target inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 font-semibold text-slate-800">{{ __('landing.request_demo') }}</a>
                    @endif

                    @if($plansRoute)
                        <a href="{{ route($plansRoute) }}" class="tap-target inline-flex min-h-11 items-center justify-center rounded px-4 font-medium text-blue-700">{{ __('landing.plans') }}</a>
                    @else
                        <p class="text-xs font-medium text-slate-500">{{ __('landing.plans_soon') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
