@extends('layouts.app')

@section('content')
<div class="auth-card mx-auto max-w-md p-5">
    <x-app-identity class="mb-5" />
    <h1 class="mb-2 text-xl font-semibold text-brand-text">{{ __('app.auth.change_password') }}</h1>
    <p class="mb-5 text-sm leading-6 text-brand-muted">{{ __('app.auth.change_password_body') }}</p>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <label class="form-label">
            {{ __('app.auth.current_password') }}
            <input name="current_password" type="password" class="form-control tap-target mt-1" required autocomplete="current-password">
        </label>

        <label class="form-label">
            {{ __('app.auth.new_password') }}
            <input name="password" type="password" class="form-control tap-target mt-1" required autocomplete="new-password">
        </label>

        <label class="form-label">
            {{ __('app.auth.confirm_new_password') }}
            <input name="password_confirmation" type="password" class="form-control tap-target mt-1" required autocomplete="new-password">
        </label>

        <button class="btn-primary tap-target w-full px-4">{{ __('app.auth.update_password') }}</button>
    </form>
</div>
@endsection
