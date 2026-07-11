@extends('layouts.app')

@section('content')
<div class="auth-card mx-auto max-w-md p-5">
    <x-app-identity class="mb-5" />
    <h1 class="mb-4 text-xl font-semibold text-brand-text">{{ __('app.auth.choose_new_password') }}</h1>
    <form method="post" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="form-label">{{ __('app.auth.email') }} <input name="email" type="email" value="{{ old('email', $email) }}" class="form-control tap-target mt-1" required></label>
        <label class="form-label">{{ __('app.auth.password') }} <input name="password" type="password" class="form-control tap-target mt-1" required></label>
        <label class="form-label">{{ __('app.auth.confirm_password') }} <input name="password_confirmation" type="password" class="form-control tap-target mt-1" required></label>
        <button class="btn-primary tap-target w-full px-4">{{ __('app.auth.reset_password') }}</button>
    </form>
</div>
@endsection
