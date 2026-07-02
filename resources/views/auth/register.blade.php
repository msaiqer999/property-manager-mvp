@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-md rounded border bg-white p-5 shadow-sm">
    <h1 class="mb-4 text-xl font-semibold">{{ __('app.auth.create_owner_account') }}</h1>
    <form method="post" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">{{ __('app.auth.organization_optional') }} <input name="organization_name" class="tap-target mt-1 w-full rounded border p-2"></label>
        <label class="block text-sm font-medium">{{ __('app.auth.name') }} <input name="name" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">{{ __('app.auth.email') }} <input name="email" type="email" class="tap-target mt-1 w-full rounded border p-2" required></label>
        <label class="block text-sm font-medium">
            {{ __('app.auth.password') }}
            <span class="mt-1 flex rounded border bg-white focus-within:ring-2 focus-within:ring-slate-200">
                <input id="register-password" name="password" type="password" class="tap-target min-w-0 flex-1 rounded p-2 outline-none" required>
                <button type="button" class="tap-target min-h-11 shrink-0 px-3 text-sm font-medium text-slate-600" data-password-toggle data-target="register-password" data-show-label="{{ __('app.auth.show_password') }}" data-hide-label="{{ __('app.auth.hide_password') }}" aria-label="{{ __('app.auth.show_password') }}">
                    {{ __('app.auth.show_password') }}
                </button>
            </span>
        </label>
        <label class="block text-sm font-medium">
            {{ __('app.auth.confirm_password') }}
            <span class="mt-1 flex rounded border bg-white focus-within:ring-2 focus-within:ring-slate-200">
                <input id="register-password-confirmation" name="password_confirmation" type="password" class="tap-target min-w-0 flex-1 rounded p-2 outline-none" required>
                <button type="button" class="tap-target min-h-11 shrink-0 px-3 text-sm font-medium text-slate-600" data-password-toggle data-target="register-password-confirmation" data-show-label="{{ __('app.auth.show_password') }}" data-hide-label="{{ __('app.auth.hide_password') }}" aria-label="{{ __('app.auth.show_password') }}">
                    {{ __('app.auth.show_password') }}
                </button>
            </span>
        </label>
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-white">{{ __('app.auth.register') }}</button>
    </form>
</div>
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (! input) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            const label = shouldShow ? button.dataset.hideLabel : button.dataset.showLabel;
            button.textContent = label;
            button.setAttribute('aria-label', label);
        });
    });
</script>
@endsection
