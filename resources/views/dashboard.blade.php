@extends('layouts.app')

@section('content')
@php($role = auth()->user()->role)

<div class="mb-4">
    <p class="text-sm text-slate-500">{{ auth()->user()->organization->name }}</p>
    <h1 class="mt-1 text-2xl font-semibold">{{ __('app.dashboard.title') }}</h1>
</div>

@if($role->can('manage-properties'))
    @if($buildingCount === 0)
        <section class="rounded border bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_buildings_title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('app.dashboard.empty_no_buildings_body') }}</p>
            <a class="tap-target mt-4 inline-flex items-center rounded bg-slate-900 px-4 text-sm text-white" href="{{ route('buildings.create') }}">{{ __('buildings.add') }}</a>
        </section>
    @else
        @if($unitCount === 0)
            <section class="mb-4 rounded border bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_units_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('app.dashboard.empty_no_units_body') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a class="tap-target inline-flex items-center rounded bg-slate-900 px-4 text-sm text-white" href="{{ route('units.create', ['building_id' => $firstBuilding->id]) }}">{{ __('units.add') }}</a>
                    <a class="tap-target inline-flex items-center rounded border px-4 text-sm" href="{{ route('buildings.units.bulk.create', $firstBuilding) }}">{{ __('units.bulk.add_multiple') }}</a>
                </div>
            </section>
        @elseif($contractCount === 0)
            <section class="mb-4 rounded border bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">{{ __('app.dashboard.empty_no_contracts_title') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ __('app.dashboard.empty_no_contracts_body') }}</p>
                <a class="tap-target mt-4 inline-flex items-center rounded bg-slate-900 px-4 text-sm text-white" href="{{ route('contracts.create') }}">{{ __('contracts.add') }}</a>
            </section>
        @endif

        <div data-mobile-owner-dashboard class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div data-dashboard-kpi-card class="rounded border bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.collected_this_month') }}</p>
                <p class="bidi-isolate mt-2 break-words text-3xl font-semibold leading-tight sm:text-2xl" dir="ltr">{{ number_format($monthlyIncome, 2) }}</p>
            </div>
            <div data-dashboard-kpi-card class="rounded border bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.overdue_amount') }}</p>
                <p class="bidi-isolate mt-2 break-words text-3xl font-semibold leading-tight sm:text-2xl" dir="ltr">{{ number_format($overdueAmount, 2) }}</p>
            </div>
            <a data-dashboard-kpi-card href="{{ route('units.index', ['status' => 'vacant']) }}" class="tap-target rounded border bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.vacant_units') }}</p>
                <p class="bidi-isolate mt-2 text-3xl font-semibold sm:text-2xl" dir="ltr">{{ $vacantUnits }}</p>
            </a>
            <div data-dashboard-kpi-card class="rounded border bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.contracts_expiring_soon') }}</p>
                <p class="bidi-isolate mt-2 text-3xl font-semibold sm:text-2xl" dir="ltr">{{ $expiringSoonCount }}</p>
            </div>
        </div>

        <section data-attention-section class="mt-6 rounded border bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-3 text-lg font-semibold sm:text-base">{{ __('app.dashboard.needs_attention') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    __('app.dashboard.attention_overdue_payments') => [$overduePaymentCount, route('payments.index', ['overdue' => 1])],
                    __('app.dashboard.attention_expiring_contracts') => [$expiringSoonCount, route('contracts.index')],
                    __('app.dashboard.attention_vacant_units') => [$vacantUnits, route('units.index', ['status' => 'vacant'])],
                    __('app.dashboard.attention_pending_payments') => [$pendingPaymentCount, route('payments.index', ['status' => 'pending'])],
                ] as $label => [$count, $href])
                    <a href="{{ $href }}" class="tap-target rounded border p-4">
                        <p class="text-sm text-slate-500">{{ $label }}</p>
                        <p class="bidi-isolate mt-1 text-xl font-semibold" dir="ltr">{{ $count }}</p>
                    </a>
                @endforeach
            </div>
            @if($overduePaymentCount === 0 && $expiringSoonCount === 0 && $vacantUnits === 0 && $pendingPaymentCount === 0)
                <p class="mt-3 text-sm text-slate-500">{{ __('app.dashboard.no_attention_items') }}</p>
            @endif
            <div class="mt-4 border-t pt-3">
                <h3 class="text-sm font-semibold text-slate-700">{{ __('app.dashboard.contracts_expiring_soon') }}</h3>
                @forelse($expiringSoon as $contract)
                    <div class="border-t py-3 text-sm first:border-t-0">
                        <a class="bidi-isolate tap-target inline-flex items-center font-medium text-blue-700" dir="ltr" href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a>
                        <p class="text-slate-600">{{ $contract->tenant->full_name }} &middot; {{ $contract->unit->building->name }} / <bdi>{{ $contract->unit->unit_number }}</bdi></p>
                        <p class="text-slate-500">
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
                    <p class="mt-2 text-sm text-slate-500">{{ __('app.dashboard.no_expiring_contracts') }}</p>
                @endforelse
            </div>
        </section>

        <section data-quick-actions class="mt-6 rounded border bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-3 text-lg font-semibold sm:text-base">{{ __('app.dashboard.quick_actions') }}</h2>
            <div class="grid gap-3 sm:flex sm:flex-wrap">
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ $nextPaymentToRecord ? route('payments.edit', $nextPaymentToRecord) : route('payments.index') }}">{{ __('payments.record_payment') }}</a>
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.create') }}">{{ __('contracts.add') }}</a>
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('tenants.create') }}">{{ __('tenants.add') }}</a>
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.create') }}">{{ __('buildings.add') }}</a>
                @if($firstBuilding)
                    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.units.bulk.create', $firstBuilding) }}">{{ __('units.bulk.add_multiple') }}</a>
                @endif
            </div>
        </section>
    @endif
@elseif($role->can('view-reports') || $role->can('view-expenses'))
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            __('app.dashboard.monthly_income') => $monthlyIncome,
            __('app.dashboard.monthly_expenses') => $monthlyExpenses,
            __('app.dashboard.net_profit') => $monthlyIncome - $monthlyExpenses,
            __('app.dashboard.overdue_amount') => $overdueAmount,
        ] as $label => $value)
            <div class="rounded border bg-white p-4 shadow-sm">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="bidi-isolate mt-2 break-words text-2xl font-semibold leading-tight" dir="ltr">{{ number_format($value, 2) }}</p>
            </div>
        @endforeach
    </div>
@endif

<div data-dashboard-secondary-lists class="mt-6 grid gap-4 {{ $role->can('manage-contracts') && $role->can('view-expenses') ? 'lg:grid-cols-3' : '' }}">
    @if($role->can('manage-contracts') && ! $role->can('manage-properties'))
        <section class="rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">{{ __('app.dashboard.contracts_expiring_soon') }}</h2>
            @forelse($expiringSoon as $contract)
                <div class="border-t py-3 text-sm">
                    <a class="bidi-isolate tap-target inline-flex items-center font-medium text-blue-700" dir="ltr" href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a>
                    <p class="text-slate-600">{{ $contract->tenant->full_name }} &middot; {{ $contract->unit->building->name }} / <bdi>{{ $contract->unit->unit_number }}</bdi></p>
                    <p class="text-slate-500">{{ __('app.dashboard.ends', ['date' => $contract->end_date->toDateString()]) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('app.dashboard.no_expiring_contracts') }}</p>
            @endforelse
        </section>
    @endif

    <section data-latest-payments class="rounded border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">{{ __('app.dashboard.latest_payments') }}</h2>
        @forelse($latestPayments as $payment)
            <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                <span class="bidi-isolate font-medium" dir="ltr">{{ number_format($payment->amount_paid, 2) }}</span>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('app.statuses.'.$payment->status) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('app.dashboard.no_payments') }}</p>
        @endforelse
    </section>

    @if($role->can('view-expenses'))
        <section data-latest-expenses class="rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">{{ __('app.dashboard.latest_expenses') }}</h2>
            @forelse($latestExpenses as $expense)
                <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                    <span class="capitalize">{{ __('expenses.categories.'.$expense->category) }}</span>
                    <span class="bidi-isolate font-medium" dir="ltr">{{ number_format($expense->amount, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('app.dashboard.no_expenses') }}</p>
            @endforelse
        </section>
    @endif
</div>
@endsection
