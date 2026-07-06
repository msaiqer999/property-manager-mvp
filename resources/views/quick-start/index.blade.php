@extends('layouts.app')

@section('content')
@php
    $quickStartUser = auth()->user();
    $quickStartRole = $quickStartUser?->role;
    $quickStartSteps = [
        [
            'key' => 'building',
            'number' => 1,
            'href' => route('buildings.create'),
            'allowed' => $quickStartRole?->can('manage-properties') ?? false,
        ],
        [
            'key' => 'units',
            'number' => 2,
            'href' => route('units.create'),
            'allowed' => $quickStartRole?->can('manage-properties') ?? false,
        ],
        [
            'key' => 'tenant',
            'number' => 3,
            'href' => route('tenants.create'),
            'allowed' => $quickStartRole?->can('manage-tenants') ?? false,
        ],
        [
            'key' => 'contract',
            'number' => 4,
            'href' => route('contracts.create'),
            'allowed' => $quickStartRole?->can('manage-contracts') ?? false,
        ],
        [
            'key' => 'payment',
            'number' => 5,
            'href' => route('payments.index'),
            'allowed' => $quickStartRole?->can('view-payments') ?? false,
        ],
        [
            'key' => 'expense',
            'number' => 6,
            'href' => route('expenses.create'),
            'allowed' => $quickStartRole?->can('manage-expenses') ?? false,
        ],
        [
            'key' => 'report',
            'number' => 7,
            'href' => route('reports.index'),
            'allowed' => $quickStartRole?->can('view-reports') ?? false,
        ],
    ];
@endphp

<div class="mb-5 grid gap-3 sm:flex sm:items-start sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-500">{{ __('app.quick_start.eyebrow') }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">{{ __('app.quick_start.title') }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ __('app.quick_start.subtitle') }}</p>
    </div>
    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-800" href="{{ route('dashboard') }}">
        {{ __('app.actions.back') }}
    </a>
</div>

<section class="rounded-xl border bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('app.quick_start.order_title') }}</h2>
        <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('app.quick_start.order_body') }}</p>
    </div>

    <div class="grid gap-3">
        @foreach($quickStartSteps as $step)
            <article data-quick-start-step class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[3rem_1fr_auto] sm:items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white" dir="ltr">
                    {{ $step['number'] }}
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-slate-950">{{ __('app.quick_start.steps.'.$step['key'].'.title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('app.quick_start.steps.'.$step['key'].'.body') }}</p>
                </div>
                @if($step['allowed'])
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ $step['href'] }}">
                        {{ __('app.quick_start.steps.'.$step['key'].'.action') }}
                    </a>
                @else
                    <span class="inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-500">
                        {{ __('app.quick_start.not_available') }}
                    </span>
                @endif
            </article>
        @endforeach
    </div>
</section>
@endsection
