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
            'secondary_href' => route('units.bulk-create'),
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
    $recommendedStep = collect($quickStartSteps)->firstWhere('key', $nextStep);
    $recommendedActionHref = $nextStep === 'units'
        ? route('units.bulk-create')
        : ($recommendedStep['href'] ?? route('quick-start.index'));
    $recommendedActionLabelKey = $nextStep === 'units'
        ? 'app.quick_start.steps.units.secondary_action'
        : 'app.quick_start.steps.'.$nextStep.'.action';
    $recommendedActionAllowed = $recommendedStep['allowed'] ?? false;
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

<section data-quick-start-progress class="mb-4 rounded-xl border bg-white p-4 shadow-sm sm:p-5">
    <div class="grid gap-3 sm:flex sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ __('app.quick_start.progress_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-600">
                {{ __('app.quick_start.progress_summary', ['completed' => $completedSetupSteps, 'total' => $totalSetupSteps]) }}
            </p>
        </div>
        @if($recommendedActionAllowed)
            <a data-quick-start-next-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ $recommendedActionHref }}">
                {{ __($recommendedActionLabelKey) }}
            </a>
        @else
            <span data-quick-start-next-action class="inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-500">
                {{ __('app.quick_start.not_available') }}
            </span>
        @endif
    </div>

    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach(['building', 'units', 'tenant', 'contract', 'payment', 'expense'] as $progressKey)
            @php
                $isComplete = $progressSteps[$progressKey] ?? false;
                $isNext = $nextStep === $progressKey;
            @endphp
            <div data-quick-start-progress-item class="rounded border p-3 {{ $isComplete ? 'border-emerald-200 bg-emerald-50' : ($isNext ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50') }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">{{ __('app.quick_start.steps.'.$progressKey.'.title') }}</p>
                    <span class="shrink-0 rounded px-2 py-1 text-xs font-medium {{ $isComplete ? 'bg-emerald-100 text-emerald-800' : ($isNext ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700') }}">
                        {{ $isComplete ? __('app.quick_start.completed') : ($isNext ? __('app.quick_start.next_step') : __('app.quick_start.not_started')) }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    {{ __('app.quick_start.record_count', ['count' => $counts[$progressKey] ?? 0]) }}
                </p>
            </div>
        @endforeach

        <div data-quick-start-progress-item class="rounded border p-3 {{ $nextStep === 'report' ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold text-slate-900">{{ __('app.quick_start.steps.report.title') }}</p>
                <span class="shrink-0 rounded px-2 py-1 text-xs font-medium {{ $nextStep === 'report' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                    {{ $nextStep === 'report' ? __('app.quick_start.next_step') : __('app.quick_start.available') }}
                </span>
            </div>
            <p class="mt-2 text-xs text-slate-600">{{ __('app.quick_start.reports_available_body') }}</p>
        </div>
    </div>
</section>

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
                    <div class="grid gap-2 sm:min-w-44">
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ $step['href'] }}">
                            {{ __('app.quick_start.steps.'.$step['key'].'.action') }}
                        </a>
                        @if(isset($step['secondary_href']))
                            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-800" href="{{ $step['secondary_href'] }}">
                                {{ __('app.quick_start.steps.'.$step['key'].'.secondary_action') }}
                            </a>
                        @endif
                    </div>
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
