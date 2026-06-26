@php
    $currentLocale = app()->getLocale();
    $locales = \App\Support\SupportedLocales::all();
@endphp

<div data-language-switcher {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    @foreach($locales as $locale => $meta)
        <form method="post" action="{{ route('locale.switch', $locale) }}">
            @csrf
            <button
                type="submit"
                class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-3 text-sm font-medium {{ $currentLocale === $locale ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700' }}"
                lang="{{ $locale }}"
                dir="{{ $meta['dir'] }}"
                aria-label="{{ __('app.switch_language', ['language' => $meta['label']]) }}"
                @if($currentLocale === $locale) aria-current="true" @endif
            >{{ $meta['label'] }}</button>
        </form>
    @endforeach
</div>
