@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">{{ __('app.auth.login') }}</h1>
    <form method="post" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">{{ __('app.auth.email') }} <input name="email" type="email" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.password') }} <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="flex items-center gap-2 text-sm"><input name="remember" type="checkbox"> {{ __('app.auth.remember_me') }}</label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">{{ __('app.auth.login') }}</button>
        <a href="{{ route('password.request') }}" class="block text-center text-sm text-slate-600">{{ __('app.auth.forgot_password') }}</a>
        <a href="{{ route('register') }}" class="block text-center text-sm text-slate-600">{{ __('app.auth.create_account') }}</a>
    </form>
</div>
@endsection
