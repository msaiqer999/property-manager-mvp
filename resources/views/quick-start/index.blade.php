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
        <p class="text-sm font-medium text-brand-muted">{{ __('app.quick_start.eyebrow') }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-brand-text">{{ __('app.quick_start.title') }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-brand-muted">{{ __('app.quick_start.subtitle') }}</p>
    </div>
    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-text" href="{{ route('dashboard') }}">
        {{ __('app.actions.back') }}
    </a>
</div>

<section data-quick-start-pilot-guide class="mb-4 rounded-lg border border-brand-primary/20 bg-brand-primary-soft p-4">
    <div class="grid gap-3 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium leading-6 text-brand-primary-hover">{{ __('app.pilot_guide.quick_start_link') }}</p>
            <p class="mt-1 text-sm leading-6 text-brand-primary-hover">{{ __('app.pilot_guide.quick_start_feedback_note') }}</p>
        </div>
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('pilot-guide.index') }}">
            {{ __('app.pilot_guide.open') }}
        </a>
    </div>
</section>

<section data-quick-start-progress class="mb-4 rounded-lg border bg-brand-surface p-4 shadow-sm sm:p-5">
    <div class="grid gap-3 sm:flex sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-brand-text">{{ __('app.quick_start.progress_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-brand-muted">
                {{ __('app.quick_start.progress_summary', ['completed' => $completedSetupSteps, 'total' => $totalSetupSteps]) }}
            </p>
        </div>
        @if($recommendedActionAllowed)
            <a data-quick-start-next-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ $recommendedActionHref }}">
                {{ __($recommendedActionLabelKey) }}
            </a>
        @else
            <span data-quick-start-next-action class="inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-muted">
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
            <div data-quick-start-progress-item class="rounded border p-3 {{ $isComplete ? 'border-state-success/25 bg-state-success-soft' : ($isNext ? 'border-brand-primary/30 bg-brand-primary-soft' : 'border-brand-border bg-brand-background') }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-brand-text">{{ __('app.quick_start.steps.'.$progressKey.'.title') }}</p>
                    <span class="shrink-0 rounded px-2 py-1 text-xs font-medium {{ $isComplete ? 'bg-state-success-soft text-state-success' : ($isNext ? 'bg-brand-primary-soft text-brand-primary-hover' : 'bg-brand-muted-soft text-brand-text') }}">
                        {{ $isComplete ? __('app.quick_start.completed') : ($isNext ? __('app.quick_start.next_step') : __('app.quick_start.not_started')) }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-brand-muted">
                    {{ __('app.quick_start.record_count', ['count' => $counts[$progressKey] ?? 0]) }}
                </p>
            </div>
        @endforeach

        <div data-quick-start-progress-item class="rounded border p-3 {{ $nextStep === 'report' ? 'border-brand-primary/30 bg-brand-primary-soft' : 'border-brand-border bg-brand-background' }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold text-brand-text">{{ __('app.quick_start.steps.report.title') }}</p>
                <span class="shrink-0 rounded px-2 py-1 text-xs font-medium {{ $nextStep === 'report' ? 'bg-brand-primary-soft text-brand-primary-hover' : 'bg-brand-muted-soft text-brand-text' }}">
                    {{ $nextStep === 'report' ? __('app.quick_start.next_step') : __('app.quick_start.available') }}
                </span>
            </div>
            <p class="mt-2 text-xs text-brand-muted">{{ __('app.quick_start.reports_available_body') }}</p>
        </div>
    </div>
</section>

@if(in_array($quickStartRole?->value, ['owner', 'manager'], true))
    @php
        $pilotChecks = [
            'building' => $counts['building'] > 0,
            'units' => $counts['units'] > 0,
            'tenant' => $counts['tenant'] > 0,
            'contract' => $counts['contract'] > 0,
            'payment' => $counts['payment'] > 0,
            'feedback' => true,
        ];
        $pilotReady = ! in_array(false, $pilotChecks, true);
    @endphp
    <section data-pilot-readiness class="mb-4 rounded-lg border bg-brand-surface p-4 shadow-sm sm:p-5">
        <div class="grid gap-2 sm:flex sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-brand-text">{{ __('app.quick_start.pilot.title') }}</h2>
                <p class="mt-1 text-sm leading-6 text-brand-muted">{{ __('app.quick_start.pilot.body') }}</p>
            </div>
            <span class="inline-flex min-h-9 items-center justify-center rounded px-3 text-sm font-medium {{ $pilotReady ? 'bg-state-success-soft text-state-success' : 'bg-state-warning-soft text-state-warning' }}">
                {{ $pilotReady ? __('app.quick_start.pilot.ready') : __('app.quick_start.pilot.needs_setup') }}
            </span>
        </div>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($pilotChecks as $checkKey => $isReady)
                <div class="rounded border p-3 {{ $isReady ? 'border-state-success/25 bg-state-success-soft' : 'border-brand-border bg-brand-background' }}">
                    <p class="text-sm font-medium text-brand-text">{{ __('app.quick_start.pilot.checks.'.$checkKey) }}</p>
                    <p class="mt-1 text-xs {{ $isReady ? 'text-state-success' : 'text-brand-muted' }}">
                        {{ $isReady ? __('app.quick_start.pilot.ready') : __('app.quick_start.pilot.needs_setup') }}
                    </p>
                </div>
            @endforeach
        </div>
        <p class="mt-4 rounded border border-brand-primary/20 bg-brand-primary-soft p-3 text-sm leading-6 text-brand-primary-hover">{{ __('app.quick_start.pilot.guidance') }}</p>
    </section>
@endif

<section class="rounded-lg border bg-brand-surface p-4 shadow-sm sm:p-5">
    <div class="mb-4">
        <h2 class="text-lg font-semibold text-brand-text">{{ __('app.quick_start.order_title') }}</h2>
        <p class="mt-1 text-sm leading-6 text-brand-muted">{{ __('app.quick_start.order_body') }}</p>
    </div>

    <div class="grid gap-3">
        @foreach($quickStartSteps as $step)
            <article data-quick-start-step class="grid gap-3 rounded-lg border border-brand-border bg-brand-background p-4 sm:grid-cols-[3rem_1fr_auto] sm:items-center">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary text-sm font-semibold text-white" dir="ltr">
                    {{ $step['number'] }}
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-brand-text">{{ __('app.quick_start.steps.'.$step['key'].'.title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-brand-muted">{{ __('app.quick_start.steps.'.$step['key'].'.body') }}</p>
                </div>
                @if($step['allowed'])
                    <div class="grid gap-2 sm:min-w-44">
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ $step['href'] }}">
                            {{ __('app.quick_start.steps.'.$step['key'].'.action') }}
                        </a>
                        @if(isset($step['secondary_href']))
                            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-text" href="{{ $step['secondary_href'] }}">
                                {{ __('app.quick_start.steps.'.$step['key'].'.secondary_action') }}
                            </a>
                        @endif
                    </div>
                @else
                    <span class="inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-muted">
                        {{ __('app.quick_start.not_available') }}
                    </span>
                @endif
            </article>
        @endforeach
    </div>
</section>
@endsection
