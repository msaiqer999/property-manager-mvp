@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">{{ __('app.auth.create_owner_account') }}</h1>
    <form method="post" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">{{ __('app.auth.organization') }} <input name="organization_name" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.name') }} <input name="name" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.email') }} <input name="email" type="email" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.password') }} <input name="password" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.confirm_password') }} <input name="password_confirmation" type="password" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">{{ __('app.auth.register') }}</button>
    </form>
</div>
@endsection
