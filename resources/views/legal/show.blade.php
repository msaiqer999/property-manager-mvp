@extends('layouts.app')

@section('content')
@php
    $content = trans("legal.pages.{$page}");
    $content = is_array($content) ? $content : trans('legal.pages.beta');
    $commonSections = trans('legal.common_sections');
    $commonSections = is_array($commonSections) ? $commonSections : [];
    $sections = array_merge($content['sections'], $commonSections);
    $links = [
        'beta' => route('legal.beta'),
        'privacy' => route('legal.privacy'),
        'terms' => route('legal.terms'),
    ];
@endphp

<article class="mx-auto max-w-4xl">
    <header class="rounded-lg border border-brand-border bg-brand-surface p-5 shadow-sm sm:p-7">
        <p class="text-sm font-semibold text-brand-primary">{{ __('legal.eyebrow') }}</p>
        <h1 class="mt-2 text-3xl font-bold text-brand-text">{{ $content['title'] }}</h1>
        <p class="mt-3 text-sm leading-6 text-brand-muted">{{ $content['intro'] }}</p>
    </header>

    <div class="mt-4 grid gap-4">
        @foreach($sections as $section)
            <section class="rounded-lg border border-brand-border bg-brand-surface p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-brand-text">{{ $section['title'] }}</h2>
                <div class="mt-3 space-y-3 text-sm leading-6 text-brand-muted">
                    @foreach($section['items'] as $item)
                        <p>{{ $item }}</p>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <nav data-legal-page-links aria-label="{{ __('legal.links.label') }}" class="mt-5 flex flex-wrap gap-2 text-sm">
        @foreach($links as $key => $href)
            <a class="tap-target inline-flex min-h-11 items-center rounded border border-brand-border bg-brand-surface px-4 font-medium text-brand-primary" href="{{ $href }}">
                {{ __('legal.links.'.$key) }}
            </a>
        @endforeach
    </nav>
</article>
@endsection
