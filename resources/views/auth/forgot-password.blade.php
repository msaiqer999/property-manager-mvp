@extends('layouts.app')

@section('content')
<div class="auth-card mx-auto max-w-md p-5">
    <x-app-identity class="mb-5" />
    <h1 class="mb-4 text-xl font-semibold text-brand-text">{{ __('app.auth.reset_password') }}</h1>
    @if(session('status'))<p class="alert-success mb-4">{{ session('status') }}</p>@endif
    <form method="post" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <label class="form-label">{{ __('app.auth.email') }} <input name="email" type="email" class="form-control tap-target mt-1" required></label>
        <button class="btn-primary tap-target w-full px-4">{{ __('app.auth.send_reset_link') }}</button>
    </form>
</div>
@endsection
