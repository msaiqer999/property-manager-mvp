@php
    $targetLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $label = app()->getLocale() === 'ar' ? 'English' : 'العربية';
@endphp

<form method="post" action="{{ route('locale.switch', $targetLocale) }}" {{ $attributes }}>
    @csrf
    <button
        type="submit"
        data-language-switcher
        class="tap-target inline-flex w-full items-center justify-center rounded border px-3 text-sm font-medium text-slate-700"
        lang="{{ $targetLocale }}"
        dir="{{ $targetLocale === 'ar' ? 'rtl' : 'ltr' }}"
        aria-label="{{ __('app.switch_language', ['language' => $label]) }}"
    >{{ $label }}</button>
</form>
