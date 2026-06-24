@extends('layouts.app')

@section('content')
@php($role = auth()->user()->role)

<div class="mb-4">
    <p class="text-sm text-slate-500">{{ auth()->user()->organization->name }}</p>
    <h1 class="mt-1 text-2xl font-semibold">{{ __('app.dashboard.title') }}</h1>
</div>

@if($role->can('view-reports') || $role->can('view-expenses'))
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

@if($role->can('manage-properties'))
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        <a href="{{ route('units.index', ['status' => 'vacant']) }}" class="tap-target rounded border bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.vacant_units') }}</p>
            <p class="bidi-isolate mt-1 text-2xl font-semibold" dir="ltr">{{ $vacantUnits }}</p>
        </a>
        <a href="{{ route('units.index', ['status' => 'rented']) }}" class="tap-target rounded border bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ __('app.dashboard.rented_units') }}</p>
            <p class="bidi-isolate mt-1 text-2xl font-semibold" dir="ltr">{{ $rentedUnits }}</p>
        </a>
    </div>
@endif

<div class="mt-6 grid gap-4 {{ $role->can('manage-contracts') && $role->can('view-expenses') ? 'lg:grid-cols-3' : '' }}">
    @if($role->can('manage-contracts'))
        <section class="rounded border bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-semibold">{{ __('app.dashboard.contracts_expiring_soon') }}</h2>
            <div class="mb-3 grid grid-cols-3 gap-2 text-center text-sm">
                <div class="rounded bg-slate-50 p-2">
                    <p class="bidi-isolate font-semibold" dir="ltr">{{ $expiryCounts['30'] }}</p>
                    <p class="text-xs text-slate-500">{{ __('app.dashboard.days', ['count' => 30]) }}</p>
                </div>
                <div class="rounded bg-slate-50 p-2">
                    <p class="bidi-isolate font-semibold" dir="ltr">{{ $expiryCounts['60'] }}</p>
                    <p class="text-xs text-slate-500">{{ __('app.dashboard.days', ['count' => 60]) }}</p>
                </div>
                <div class="rounded bg-slate-50 p-2">
                    <p class="bidi-isolate font-semibold" dir="ltr">{{ $expiryCounts['90'] }}</p>
                    <p class="text-xs text-slate-500">{{ __('app.dashboard.days', ['count' => 90]) }}</p>
                </div>
            </div>
            @forelse($expiringSoon as $contract)
                <div class="border-t py-3 text-sm">
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
                <p class="text-sm text-slate-500">{{ __('app.dashboard.no_expiring_contracts') }}</p>
            @endforelse
        </section>
    @endif

    <section class="rounded border bg-white p-4 shadow-sm">
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
        <section class="rounded border bg-white p-4 shadow-sm">
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
