@extends('layouts.app')

@section('content')
@php
    $pilotUser = auth()->user();
    $pilotRole = $pilotUser?->role;
    $isPilotHandoverRole = in_array($pilotRole?->value, ['owner', 'manager'], true);
    $pilotSteps = [
        [
            'key' => 'quick_start',
            'number' => 1,
            'href' => route('quick-start.index'),
            'allowed' => true,
        ],
        [
            'key' => 'building',
            'number' => 2,
            'href' => route('buildings.create'),
            'allowed' => $pilotRole?->can('manage-properties') ?? false,
        ],
        [
            'key' => 'units',
            'number' => 3,
            'href' => route('units.bulk-create'),
            'allowed' => $pilotRole?->can('manage-properties') ?? false,
        ],
        [
            'key' => 'tenant',
            'number' => 4,
            'href' => route('tenants.create'),
            'allowed' => $pilotRole?->can('manage-tenants') ?? false,
        ],
        [
            'key' => 'contract',
            'number' => 5,
            'href' => route('contracts.create'),
            'allowed' => $pilotRole?->can('manage-contracts') ?? false,
        ],
        [
            'key' => 'payments',
            'number' => 6,
            'href' => route('payments.index'),
            'allowed' => $pilotRole?->can('view-payments') ?? false,
        ],
        [
            'key' => 'expense',
            'number' => 7,
            'href' => route('expenses.create'),
            'allowed' => $pilotRole?->can('manage-expenses') ?? false,
        ],
        [
            'key' => 'reports',
            'number' => 8,
            'href' => route('reports.index'),
            'allowed' => $pilotRole?->can('view-reports') ?? false,
        ],
        [
            'key' => 'feedback',
            'number' => 9,
            'href' => null,
            'allowed' => true,
        ],
    ];
@endphp

<div class="mb-5 grid gap-3 sm:flex sm:items-start sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-500">{{ __('app.pilot_guide.eyebrow') }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">{{ __('app.pilot_guide.title') }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.subtitle') }}</p>
    </div>
    <div class="grid gap-2 sm:flex sm:flex-wrap sm:justify-end">
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('quick-start.index') }}">
            {{ __('app.pilot_guide.back_to_quick_start') }}
        </a>
        <button type="button" data-feedback-open class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-800">
            {{ __('app.pilot_guide.feedback_button') }}
        </button>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="grid gap-4">
        <section data-pilot-guide-purpose class="rounded-xl border bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('app.pilot_guide.purpose_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.purpose_body') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                    <h3 class="text-sm font-semibold text-blue-950">{{ __('app.pilot_guide.try_title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-blue-900">{{ __('app.pilot_guide.try_body') }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <h3 class="text-sm font-semibold text-slate-950">{{ __('app.pilot_guide.report_title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.report_body') }}</p>
                </div>
            </div>
        </section>

        <section data-pilot-guide-path class="rounded-xl border bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('app.pilot_guide.path_title') }}</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.path_body') }}</p>
            </div>

            <div class="grid gap-3">
                @foreach($pilotSteps as $step)
                    <article data-pilot-guide-step class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[3rem_1fr_auto] sm:items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-semibold text-white" dir="ltr">
                            {{ $step['number'] }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-950">{{ __('app.pilot_guide.steps.'.$step['key'].'.title') }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.steps.'.$step['key'].'.body') }}</p>
                        </div>
                        @if($step['key'] === 'feedback')
                            <button type="button" data-feedback-open class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white">
                                {{ __('app.pilot_guide.steps.feedback.action') }}
                            </button>
                        @elseif($step['allowed'])
                            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ $step['href'] }}">
                                {{ __('app.pilot_guide.steps.'.$step['key'].'.action') }}
                            </a>
                        @else
                            <span class="inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-500">
                                {{ __('app.pilot_guide.not_available') }}
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <aside class="grid content-start gap-4">
        <section data-pilot-guide-feedback class="rounded-xl border bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('app.pilot_guide.feedback_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.feedback_body') }}</p>
            <button type="button" data-feedback-open class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white">
                {{ __('app.pilot_guide.feedback_button') }}
            </button>
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <h3 class="text-sm font-semibold text-slate-950">{{ __('app.pilot_guide.examples_title') }}</h3>
                <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-600">
                    @foreach(__('app.pilot_guide.examples') as $example)
                        <li>{{ $example }}</li>
                    @endforeach
                </ul>
            </div>
        </section>

        @if($isPilotHandoverRole)
            <section data-pilot-guide-handover class="rounded-xl border bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('app.pilot_guide.handover_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('app.pilot_guide.handover_body') }}</p>
                <ul class="mt-4 space-y-3">
                    @foreach(__('app.pilot_guide.handover_items') as $item)
                        <li class="flex gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm leading-6 text-slate-700">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-slate-500"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </aside>
</div>
@endsection
