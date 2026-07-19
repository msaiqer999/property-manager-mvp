@extends('layouts.app')

@section('content')
<div class="auth-card mx-auto max-w-md p-5">
    <x-app-identity class="mb-5" />
    <h1 class="mb-4 text-xl font-semibold text-brand-text">{{ __('app.auth.create_owner_account') }}</h1>
    @if(! ($registrationCountrySetupReady ?? true))
        <div role="alert" class="rounded border border-state-warning/40 bg-state-warning/10 p-3 text-sm text-brand-text">
            {{ __('app.auth.country_setup_required') }}
        </div>
    @else
    <form method="post" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <label class="form-label">{{ __('app.auth.organization_optional') }} <input name="organization_name" class="form-control tap-target mt-1"></label>
        @if(($countries ?? collect())->isNotEmpty())
            <label class="form-label">
                {{ __('app.auth.properties_country') }}
                <select name="country_id" class="form-control form-select-safe tap-target mt-1">
                    <option value="">{{ __('app.auth.choose_country_later') }}</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" @selected((string) old('country_id') === (string) $country->id)>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
            </label>
        @endif
        <label class="form-label">{{ __('app.auth.name') }} <input name="name" class="form-control tap-target mt-1" required></label>
        <label class="form-label">{{ __('app.auth.email') }} <input name="email" type="email" class="form-control tap-target mt-1" required></label>
        <label class="form-label">
            {{ __('app.auth.password') }}
            <span class="mt-1 flex rounded border bg-brand-surface focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                <input id="register-password" name="password" type="password" class="tap-target min-w-0 flex-1 border-0 bg-transparent p-2 outline-none focus:ring-0" required>
                <button type="button" class="tap-target inline-flex min-h-11 w-11 shrink-0 items-center justify-center text-brand-muted hover:text-brand-primary" data-password-toggle data-target="register-password" data-show-label="{{ __('app.auth.show_password') }}" data-hide-label="{{ __('app.auth.hide_password') }}" aria-label="{{ __('app.auth.show_password') }}">
                    <svg data-eye-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                        <circle cx="12" cy="12" r="2.75" />
                    </svg>
                    <svg data-eye-closed class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18" />
                        <path d="M10.6 5.9A9.6 9.6 0 0 1 12 5.75c6.25 0 9.75 6.25 9.75 6.25a17.6 17.6 0 0 1-3.2 3.9" />
                        <path d="M6.2 7.3A17.1 17.1 0 0 0 2.25 12S5.75 18.25 12 18.25a9.4 9.4 0 0 0 4.1-.95" />
                    </svg>
                    <span class="sr-only">{{ __('app.auth.show_password') }}</span>
                </button>
            </span>
        </label>
        <label class="form-label">
            {{ __('app.auth.confirm_password') }}
            <span class="mt-1 flex rounded border bg-brand-surface focus-within:border-brand-primary focus-within:ring-2 focus-within:ring-brand-primary/20">
                <input id="register-password-confirmation" name="password_confirmation" type="password" class="tap-target min-w-0 flex-1 border-0 bg-transparent p-2 outline-none focus:ring-0" required>
                <button type="button" class="tap-target inline-flex min-h-11 w-11 shrink-0 items-center justify-center text-brand-muted hover:text-brand-primary" data-password-toggle data-target="register-password-confirmation" data-show-label="{{ __('app.auth.show_password') }}" data-hide-label="{{ __('app.auth.hide_password') }}" aria-label="{{ __('app.auth.show_password') }}">
                    <svg data-eye-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M2.25 12s3.5-6.25 9.75-6.25S21.75 12 21.75 12 18.25 18.25 12 18.25 2.25 12 2.25 12Z" />
                        <circle cx="12" cy="12" r="2.75" />
                    </svg>
                    <svg data-eye-closed class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M3 3l18 18" />
                        <path d="M10.6 5.9A9.6 9.6 0 0 1 12 5.75c6.25 0 9.75 6.25 9.75 6.25a17.6 17.6 0 0 1-3.2 3.9" />
                        <path d="M6.2 7.3A17.1 17.1 0 0 0 2.25 12S5.75 18.25 12 18.25a9.4 9.4 0 0 0 4.1-.95" />
                    </svg>
                    <span class="sr-only">{{ __('app.auth.show_password') }}</span>
                </button>
            </span>
        </label>
        <button class="btn-primary tap-target w-full px-4">{{ __('app.auth.register') }}</button>
    </form>
    @endif
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
            button.setAttribute('aria-label', label);
            const srLabel = button.querySelector('.sr-only');
            if (srLabel) {
                srLabel.textContent = label;
            }
            button.querySelector('[data-eye-open]')?.classList.toggle('hidden', shouldShow);
            button.querySelector('[data-eye-closed]')?.classList.toggle('hidden', ! shouldShow);
        });
    });
</script>
@endsection
