@extends('layouts.app')

@section('content')
@php
    $dashboardUser = auth()->user();
    $role = $role ?? $dashboardUser?->role;
    $canManageProperties = $role?->can('manage-properties') ?? false;
    $canManageContracts = $role?->can('manage-contracts') ?? false;
    $canViewExpenses = $role?->can('view-expenses') ?? false;
    $canViewReports = $role?->can('view-reports') ?? false;
    $dashboardCurrency = $dashboardUser?->organization?->effectiveCurrencyCode();
    $formatDashboardMoney = fn ($value) => $dashboardCurrency.' '.number_format((float) $value, 2);
    $formatDashboardCount = fn ($value) => number_format((int) $value);
    $ownerOnboardingState = $canManageProperties ? ($buildingCount === 0 ? 'buildings' : ($unitCount === 0 ? 'units' : ($contractCount === 0 ? 'contracts' : null))) : null;
    $guidedStartSteps = $canManageProperties ? [
        [
            'label' => __('app.dashboard.guided_step_building'),
            'complete' => $buildingCount > 0,
            'href' => route('buildings.create'),
        ],
        [
            'label' => __('app.dashboard.guided_step_units'),
            'complete' => $unitCount > 0,
            'href' => $firstBuilding ? route('buildings.units.bulk.create', $firstBuilding) : route('buildings.create'),
        ],
        [
            'label' => __('app.dashboard.guided_step_tenants'),
            'complete' => $tenantCount > 0,
            'href' => route('tenants.create'),
        ],
        [
            'label' => __('app.dashboard.guided_step_contracts'),
            'complete' => $contractCount > 0,
            'href' => route('contracts.create'),
        ],
        [
            'label' => __('app.dashboard.guided_step_payments'),
            'complete' => $recordedPaymentCount > 0,
            'href' => $nextPaymentToRecord ? route('payments.edit', $nextPaymentToRecord) : route('payments.index'),
        ],
    ] : [];
    $guidedStartComplete = $guidedStartSteps !== [] && collect($guidedStartSteps)->every(fn ($step) => $step['complete']);
    $nextGuidedIndex = collect($guidedStartSteps)->search(fn ($step) => ! $step['complete']);
    $setupProgressCompleted = collect([
        $buildingCount > 0,
        $unitCount > 0,
        $tenantCount > 0,
        $contractCount > 0,
        $recordedPaymentCount > 0,
        $canViewExpenses ? ($expenseCount ?? 0) > 0 : false,
    ])->filter()->count();
    $setupProgressTotal = 6;
@endphp

<div class="mb-4">
    <p class="text-sm text-brand-muted">{{ $dashboardUser->organization->name }}</p>
    <h1 class="mt-1 text-2xl font-semibold">{{ __('app.dashboard.title') }}</h1>
</div>

<section data-dashboard-pilot-guide class="mb-4 rounded border bg-brand-surface p-4 shadow-sm">
    <div class="grid gap-3 sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-brand-text">{{ __('app.pilot_guide.title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-brand-muted">{{ __('app.pilot_guide.dashboard_body') }}</p>
        </div>
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-text" href="{{ route('pilot-guide.index') }}">
            {{ __('app.pilot_guide.open') }}
        </a>
    </div>
</section>

@if($canManageProperties)
    <section data-dashboard-guided-start class="mb-6 rounded border bg-brand-surface p-4 shadow-sm sm:p-5">
        <div class="grid gap-3 sm:flex sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">{{ __('app.dashboard.guided_start_title') }}</h2>
                <p class="mt-1 text-sm text-brand-muted">
                    {{ $guidedStartComplete ? __('app.dashboard.guided_start_complete') : __('app.dashboard.guided_start_body') }}
                </p>
            </div>
            <div class="grid gap-2 sm:flex sm:flex-wrap sm:justify-end">
                <a data-dashboard-quick-start class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-text" href="{{ route('quick-start.index') }}">
                    <span class="grid gap-0.5">
                        <span>{{ __('app.dashboard.quick_start_button') }}</span>
                        <span class="text-xs font-normal text-brand-muted">{{ __('app.dashboard.setup_progress_summary', ['completed' => $setupProgressCompleted, 'total' => $setupProgressTotal]) }}</span>
                    </span>
                </a>
                @if(! $guidedStartComplete && $nextGuidedIndex !== false)
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ $guidedStartSteps[$nextGuidedIndex]['href'] }}">
                        {{ __('app.dashboard.continue_setup') }}
                    </a>
                @endif
            </div>
        </div>
        <div class="mt-4 grid gap-3 lg:grid-cols-5">
            @foreach($guidedStartSteps as $index => $step)
                @php($isNext = ! $guidedStartComplete && $nextGuidedIndex === $index)
                <div data-guided-start-step class="rounded border p-3 {{ $step['complete'] ? 'border-state-success/25 bg-state-success-soft' : ($isNext ? 'border-brand-primary/30 bg-brand-primary-soft' : 'bg-brand-surface') }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                        <span class="shrink-0 rounded-full px-2 py-1 text-xs font-medium {{ $step['complete'] ? 'bg-state-success-soft text-state-success' : ($isNext ? 'bg-brand-primary-soft text-brand-primary-hover' : 'bg-brand-muted-soft text-brand-text') }}">
                            {{ $step['complete'] ? __('app.dashboard.step_completed') : ($isNext ? __('app.dashboard.step_next') : __('app.dashboard.step_pending')) }}
                        </span>
                    </div>
                    @if(! $step['complete'])
                        <a class="tap-target mt-3 inline-flex min-h-10 w-full items-center justify-center rounded border bg-brand-surface px-3 text-center text-sm font-medium text-brand-text" href="{{ $step['href'] }}">
                            {{ __('app.dashboard.step_action') }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    @if($buildingCount === 0)
        <section data-owner-onboarding-empty-buildings class="rounded border bg-brand-surface p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_buildings_title') }}</h2>
            <p class="mt-2 text-sm text-brand-muted">{{ __('app.dashboard.empty_no_buildings_body') }}</p>
            <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('buildings.create') }}">{{ __('buildings.add') }}</a>
        </section>
    @else
        @if($unitCount === 0)
            <section data-owner-onboarding-empty-units class="rounded border bg-brand-surface p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_units_title') }}</h2>
                <p class="mt-2 text-sm text-brand-muted">{{ __('app.dashboard.empty_no_units_body') }}</p>
                <div class="mt-4 grid gap-3 sm:flex sm:flex-wrap">
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('units.create', ['building_id' => $firstBuilding->id]) }}">{{ __('units.add') }}</a>
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.units.bulk.create', $firstBuilding) }}">{{ __('units.bulk.add_multiple') }}</a>
                </div>
            </section>
        @elseif($contractCount === 0)
            <section data-owner-onboarding-empty-contracts class="rounded border bg-brand-surface p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_contracts_title') }}</h2>
                <p class="mt-2 text-sm text-brand-muted">{{ __('app.dashboard.empty_no_contracts_body') }}</p>
                <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('contracts.create') }}">{{ __('contracts.add') }}</a>
            </section>
        @else

        <div data-dashboard-with-roadmap class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] xl:grid-cols-[20rem_minmax(0,1fr)]">
            <aside data-dashboard-roadmap class="order-2 lg:order-none">
                <section class="rounded-lg border border-brand-accent/40 bg-brand-primary p-4 text-white shadow-lg sm:p-5 lg:sticky lg:top-24">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-accent-soft">{{ __('app.dashboard.roadmap_label') }}</p>
                        <h2 class="mt-3 text-2xl font-semibold leading-tight">{{ __('app.dashboard.roadmap_title') }}</h2>
                        <p class="mt-3 text-sm leading-6 text-brand-accent-soft">{{ __('app.dashboard.roadmap_body') }}</p>
                        <div class="mt-5 grid gap-2">
                            @foreach([
                                __('app.dashboard.roadmap_unit_documents'),
                                __('app.dashboard.roadmap_smart_alerts'),
                                __('app.dashboard.roadmap_maintenance_requests'),
                                __('app.dashboard.roadmap_tenant_account'),
                                __('app.dashboard.roadmap_vacant_listing'),
                            ] as $roadmapItem)
                                <div data-dashboard-roadmap-item class="rounded-lg border border-brand-surface/15 bg-brand-surface/10 px-3 py-2 text-sm font-medium text-white">
                                    {{ $roadmapItem }}
                                </div>
                            @endforeach
                        </div>
                        <button type="button" disabled aria-disabled="true" class="tap-target mt-5 inline-flex min-h-11 w-full cursor-not-allowed items-center justify-center rounded-lg border border-brand-surface/20 bg-brand-surface/10 px-4 text-center text-sm font-medium text-white/80">
                            {{ __('app.dashboard.suggest_feature') }}
                        </button>
                    </div>
                </section>
            </aside>

            <div class="order-1 min-w-0 lg:order-none">
                <div data-mobile-owner-dashboard class="grid items-stretch gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div data-dashboard-kpi-card class="flex min-h-32 flex-col justify-between rounded border bg-brand-surface p-4 shadow-sm sm:min-h-36 sm:p-5">
                        <p class="text-sm font-medium leading-5 text-brand-muted sm:whitespace-nowrap">{{ __('app.dashboard.collected_this_month') }}</p>
                        <p class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-brand-text sm:text-3xl" dir="ltr"><bdi>{{ $formatDashboardMoney($monthlyIncome) }}</bdi></p>
                    </div>
                    <div data-dashboard-kpi-card class="flex min-h-32 flex-col justify-between rounded border bg-brand-surface p-4 shadow-sm sm:min-h-36 sm:p-5">
                        <p class="text-sm font-medium leading-5 text-brand-muted sm:whitespace-nowrap">{{ __('app.dashboard.overdue_amount') }}</p>
                        <p class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-brand-text sm:text-3xl" dir="ltr"><bdi>{{ $formatDashboardMoney($overdueAmount) }}</bdi></p>
                    </div>
                    <a data-dashboard-kpi-card href="{{ route('units.index', ['status' => 'vacant']) }}" class="tap-target flex min-h-32 flex-col justify-between rounded border bg-brand-surface p-4 shadow-sm sm:min-h-36 sm:p-5">
                        <p class="text-sm font-medium leading-5 text-brand-muted sm:whitespace-nowrap">{{ __('app.dashboard.vacant_units') }}</p>
                        <p class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-brand-text sm:text-4xl" dir="ltr"><bdi>{{ $formatDashboardCount($vacantUnits) }}</bdi></p>
                    </a>
                    <div data-dashboard-kpi-card class="flex min-h-32 flex-col justify-between rounded border bg-brand-surface p-4 shadow-sm sm:min-h-36 sm:p-5">
                        <p class="text-sm font-medium leading-5 text-brand-muted sm:whitespace-nowrap">{{ __('app.dashboard.contracts_expiring_soon') }}</p>
                        <p class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-brand-text sm:text-4xl" dir="ltr"><bdi>{{ $formatDashboardCount($expiringSoonCount) }}</bdi></p>
                    </div>
                </div>

                @include('dashboard.partials.todays-priorities')

                <section data-daily-actions data-attention-section class="mt-6 rounded border bg-brand-surface p-4 shadow-sm sm:p-5">
                    <div class="mb-3">
                        <h2 class="text-lg font-semibold sm:text-base">{{ __('app.dashboard.daily_actions_title') }}</h2>
                        <p class="mt-1 text-sm text-brand-muted">{{ __('app.dashboard.needs_attention') }}</p>
                    </div>
                    <div class="grid items-stretch gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach([
                            [
                                'label' => __('app.dashboard.attention_overdue_payments'),
                                'count' => $overduePaymentCount,
                                'body' => __('app.dashboard.daily_overdue_body'),
                                'action' => __('app.dashboard.view_overdue_payments'),
                                'href' => route('payments.index', ['overdue' => 1]),
                            ],
                            [
                                'label' => __('app.dashboard.attention_partial_payments'),
                                'count' => $partialPaymentCount,
                                'body' => __('app.dashboard.daily_partial_body'),
                                'action' => __('app.dashboard.review_partial_payments'),
                                'href' => route('payments.index', ['status' => 'partial']),
                            ],
                            [
                                'label' => __('app.dashboard.attention_expiring_contracts'),
                                'count' => $expiringSoonCount,
                                'body' => __('app.dashboard.daily_expiring_body'),
                                'action' => __('app.dashboard.view_expiring_contracts'),
                                'href' => route('contracts.index'),
                            ],
                            [
                                'label' => __('app.dashboard.attention_vacant_units'),
                                'count' => $vacantUnits,
                                'body' => __('app.dashboard.daily_vacant_body'),
                                'action' => __('app.dashboard.view_vacant_units'),
                                'href' => route('units.index', ['status' => 'vacant']),
                            ],
                        ] as $item)
                            <a href="{{ $item['href'] }}" class="tap-target group flex min-h-44 flex-col justify-between rounded-lg border border-brand-border bg-brand-surface p-4 shadow-sm transition hover:border-brand-primary/30 hover:bg-brand-primary-soft focus:outline-none focus:ring-2 focus:ring-brand-primary/20">
                                <div>
                                    <p class="text-sm font-semibold leading-5 text-brand-text">{{ $item['label'] }}</p>
                                    <p class="mt-3 text-3xl font-semibold leading-none tracking-tight text-brand-text" dir="ltr"><bdi>{{ number_format((int) $item['count']) }}</bdi></p>
                                    <p class="mt-3 text-sm leading-6 text-brand-muted">{{ $item['body'] }}</p>
                                </div>
                                <span class="mt-4 inline-flex min-h-10 items-center justify-center rounded border border-brand-border bg-brand-background px-3 text-center text-sm font-medium text-brand-text transition group-hover:border-brand-primary/30 group-hover:bg-brand-surface group-hover:text-brand-primary-hover">
                                    {{ $item['action'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                    @if($overduePaymentCount === 0 && $partialPaymentCount === 0 && $expiringSoonCount === 0 && $vacantUnits === 0)
                        <p class="mt-3 text-sm text-brand-muted">{{ __('app.dashboard.no_attention_items') }}</p>
                    @endif
                    <div class="mt-4 border-t pt-3">
                        <h3 class="text-sm font-semibold text-brand-text">{{ __('app.dashboard.contracts_expiring_soon') }}</h3>
                        @forelse($expiringSoon as $contract)
                            <div class="border-t py-3 text-sm first:border-t-0">
                                <a class="bidi-isolate tap-target inline-flex items-center font-medium text-brand-primary" dir="ltr" href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a>
                                <p class="text-brand-muted">{{ $contract->tenant->full_name }} &middot; {{ $contract->unit->building->name }} / <bdi>{{ $contract->unit->unit_number }}</bdi></p>
                                <p class="text-brand-muted">
                                    {{ __('app.dashboard.ends', ['date' => $contract->end_date->toDateString()]) }}
                                    &middot;
                                    @if($contract->daysUntilExpiry() === 0)
                                        {{ __('app.dashboard.expires_today') }}
                                    @elseif($contract->daysUntilExpiry() === 1)
                                        {{ __('app.dashboard.expires_in_one_day') }}
                                    @else
                                        {{ __('app.dashboard.expires_in_days', ['count' => $contract->daysUntilExpiry()]) }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="mt-2 text-sm text-brand-muted">{{ __('app.dashboard.no_expiring_contracts') }}</p>
                        @endforelse
                    </div>
                </section>

                <section data-quick-actions class="mt-6 rounded border bg-brand-surface p-4 shadow-sm sm:p-5">
                    <h2 class="mb-3 text-lg font-semibold sm:text-base">{{ __('app.dashboard.quick_actions') }}</h2>
                    <div class="grid gap-3 sm:flex sm:flex-wrap">
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ $nextPaymentToRecord ? route('payments.edit', $nextPaymentToRecord) : route('payments.index') }}">{{ __('payments.record_payment') }}</a>
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.create') }}">{{ __('contracts.add') }}</a>
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('tenants.create') }}">{{ __('tenants.add') }}</a>
                        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.create') }}">{{ __('buildings.add') }}</a>
                        @if($firstBuilding)
                            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.units.bulk.create', $firstBuilding) }}">{{ __('units.bulk.add_multiple') }}</a>
                        @endif
                    </div>
                </section>
            </div>
        </div>
        @endif
    @endif
@elseif($canViewReports || $canViewExpenses)
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            __('app.dashboard.monthly_income') => $monthlyIncome,
            __('app.dashboard.monthly_expenses') => $monthlyExpenses,
            __('app.dashboard.net_profit') => $monthlyIncome - $monthlyExpenses,
            __('app.dashboard.overdue_amount') => $overdueAmount,
        ] as $label => $value)
            <div class="flex min-h-32 flex-col justify-between rounded border bg-brand-surface p-4 shadow-sm">
                <p class="text-sm font-medium leading-5 text-brand-muted sm:whitespace-nowrap">{{ $label }}</p>
                <p class="mt-4 text-2xl font-semibold leading-tight tracking-tight text-brand-text" dir="ltr"><bdi>{{ $formatDashboardMoney($value) }}</bdi></p>
            </div>
        @endforeach
    </div>
@endif

@if(! $ownerOnboardingState)
<div data-dashboard-secondary-lists class="mt-6 grid gap-4 {{ $canManageContracts && $canViewExpenses ? 'lg:grid-cols-3' : '' }}">
    @if($canManageContracts && ! $canManageProperties)
        <section class="rounded border bg-brand-surface p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">{{ __('app.dashboard.contracts_expiring_soon') }}</h2>
            @forelse($expiringSoon as $contract)
                <div class="border-t py-3 text-sm">
                    <a class="bidi-isolate tap-target inline-flex items-center font-medium text-brand-primary" dir="ltr" href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a>
                    <p class="text-brand-muted">{{ $contract->tenant->full_name }} &middot; {{ $contract->unit->building->name }} / <bdi>{{ $contract->unit->unit_number }}</bdi></p>
                    <p class="text-brand-muted">{{ __('app.dashboard.ends', ['date' => $contract->end_date->toDateString()]) }}</p>
                </div>
            @empty
                <p class="text-sm text-brand-muted">{{ __('app.dashboard.no_expiring_contracts') }}</p>
            @endforelse
        </section>
    @endif

    <section data-latest-payments class="rounded border bg-brand-surface p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">{{ __('app.dashboard.latest_payments') }}</h2>
        @forelse($latestPayments as $payment)
            <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                <span class="bidi-isolate font-medium" dir="ltr">{{ number_format($payment->amount_paid, 2) }}</span>
                <x-status-badge :status="$payment->status" :label="__('app.statuses.'.$payment->status)" />
            </div>
        @empty
            <p class="text-sm text-brand-muted">{{ __('app.dashboard.no_payments') }}</p>
        @endforelse
    </section>

    @if($canViewExpenses)
        <section data-latest-expenses class="rounded border bg-brand-surface p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">{{ __('app.dashboard.latest_expenses') }}</h2>
            @forelse($latestExpenses as $expense)
                <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                    <span class="capitalize">{{ __('expenses.categories.'.$expense->category) }}</span>
                    <span class="bidi-isolate font-medium" dir="ltr">{{ number_format($expense->amount, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-brand-muted">{{ __('app.dashboard.no_expenses') }}</p>
            @endforelse
        </section>
    @endif
</div>
@endif
@endsection
