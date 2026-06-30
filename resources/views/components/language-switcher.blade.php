@php
    $currentLocale = app()->getLocale();
    $locales = \App\Support\SupportedLocales::all();
    $currentLabel = $locales[$currentLocale]['label'] ?? 'English';
@endphp

<details data-language-switcher data-language-dropdown {{ $attributes->merge(['class' => 'relative']) }}>
    <summary class="tap-target flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 rounded border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 [&::-webkit-details-marker]:hidden">
        <span>{{ __('app.language') }}</span>
        <span class="max-w-36 truncate text-slate-500" lang="{{ $currentLocale }}">{{ $currentLabel }}</span>
        <svg aria-hidden="true" viewBox="0 0 20 20" class="size-4 shrink-0 fill-current text-slate-500">
            <path d="M5.25 7.5 10 12.25 14.75 7.5h-9.5Z"/>
        </svg>
    </summary>
    <div class="absolute end-0 z-40 mt-2 w-64 max-w-[calc(100vw-1rem)] rounded border border-slate-200 bg-white p-2 shadow-xl">
        @foreach($locales as $locale => $meta)
            <form method="post" action="{{ route('locale.switch', $locale) }}">
                @csrf
                <button
                    type="submit"
                    data-language-option
                    class="tap-target flex min-h-11 w-full items-center justify-between rounded px-3 text-start text-sm font-medium {{ $currentLocale === $locale ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50' }}"
                    lang="{{ $locale }}"
                    dir="{{ $meta['dir'] }}"
                    aria-label="{{ __('app.switch_language', ['language' => $meta['label']]) }}"
                    @if($currentLocale === $locale) aria-current="true" @endif
                >
                    <span>{{ $meta['label'] }}</span>
                    @if($currentLocale === $locale)
                        <span aria-hidden="true">✓</span>
                    @endif
                </button>
            </form>
        @endforeach
    </div>
</details>
