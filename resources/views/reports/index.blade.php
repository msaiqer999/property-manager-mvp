@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ __('reports.title') }}</h1>

<form method="get" action="{{ route('reports.index') }}" class="mb-4 grid gap-3 rounded border bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
    <label class="block text-sm font-medium">
        {{ __('reports.filters.building') }}
        <select name="building_id" data-building-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('reports.filters.all_buildings') }}</option>
            @foreach($buildings as $building)
                <option value="{{ $building->id }}" @selected($filters['building_id'] === $building->id)>{{ $building->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('reports.filters.unit') }}
        <select name="unit_id" data-unit-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('reports.filters.all_units') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" @selected($filters['unit_id'] === $unit->id)><bdi dir="ltr">{{ $unit->unit_number }}</bdi> - {{ $unit->building->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('reports.filters.tenant') }}
        <select name="tenant_id" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('reports.filters.all_tenants') }}</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected($filters['tenant_id'] === $tenant->id)>{{ $tenant->full_name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('reports.filters.from') }}
        <input name="from" type="date" value="{{ $filters['from_date'] }}" class="tap-target mt-1 w-full rounded border p-2">
    </label>

    <label class="block text-sm font-medium">
        {{ __('reports.filters.to') }}
        <input name="to" type="date" value="{{ $filters['to_date'] }}" class="tap-target mt-1 w-full rounded border p-2">
    </label>

    <div class="flex items-end">
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-sm font-medium text-white">{{ __('reports.filters.apply') }}</button>
    </div>
</form>

<div class="grid gap-3 sm:grid-cols-3">
@foreach(['income' => $income, 'expenses' => $expensesTotal, 'net_profit' => $netProfit] as $label => $value)
<div class="rounded border bg-white p-4 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ __('reports.summary.'.$label) }}</p><p class="mt-1 break-words text-2xl font-semibold" dir="ltr">{{ number_format($value, 2) }}</p></div>
@endforeach
</div>

@php
    $statementContractNumbers = $statementRows
        ->pluck('contract.contract_number')
        ->filter()
        ->unique()
        ->values();
    $statementContractLabel = $statementContractNumbers->count() === 1
        ? $statementContractNumbers->first()
        : __('reports.pdf.not_available');
@endphp

<section class="mt-6 rounded border bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold">{{ __('reports.statement.title') }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('reports.statement.subtitle') }}
            </p>
            <p class="mt-2 text-sm text-slate-500">
                {{ __('reports.filters.tenant') }}: {{ $filters['tenant_label'] }}
                @if($filters['tenant_phone'])
                    &middot;
                    {{ __('tenants.fields.phone') }}: <bdi dir="ltr">{{ $filters['tenant_phone'] }}</bdi>
                @endif
                &middot;
                {{ __('reports.filters.building') }}: {{ $filters['building_label'] }}
                &middot;
                {{ __('reports.filters.unit') }}: <bdi dir="ltr">{{ $filters['unit_label'] }}</bdi>
                &middot;
                {{ __('reports.columns.contract') }}: <bdi dir="ltr">{{ $statementContractLabel }}</bdi>
                &middot;
                {{ __('reports.filters.from') }}: <bdi dir="ltr">{{ $filters['from_date'] }}</bdi>
                &middot;
                {{ __('reports.filters.to') }}: <bdi dir="ltr">{{ $filters['to_date'] }}</bdi>
            </p>
        </div>
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium text-slate-800" href="{{ route('reports.pdf', ['type' => 'unit-statement'] + $filterQuery) }}">
            {{ __('reports.actions.unit_statement') }}
        </a>
    </div>

    @php
        $statementTotals = [
            'amount_due' => (float) $statementRows->sum('amount_due'),
            'amount_paid' => (float) $statementRows->sum('amount_paid'),
            'remaining_balance' => (float) $statementRows->sum(fn ($payment) => $payment->remaining_amount),
            'overdue_remaining' => (float) $statementRows->sum(fn ($payment) => in_array($payment->display_status_key, ['overdue', 'partial_overdue'], true) ? $payment->remaining_amount : 0),
        ];
    @endphp

    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($statementTotals as $key => $value)
            <div class="rounded border bg-slate-50 p-3">
                <p class="text-sm font-medium text-slate-500">{{ __('reports.columns.'.$key) }}</p>
                <p class="mt-1 text-xl font-semibold" dir="ltr"><bdi>{{ number_format($value, 2) }}</bdi></p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-3 md:hidden">
        @forelse($statementRows as $payment)
            <article class="rounded border p-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $payment->contract?->tenant?->full_name ?? __('reports.pdf.not_available') }}</p>
                        <p class="text-sm text-slate-600">{{ $payment->contract?->unit?->building?->name ?? __('reports.pdf.not_available') }} / <bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('reports.pdf.not_available') }}</bdi></p>
                    </div>
                    <span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('payments.statuses.'.$payment->display_status_key) }}</span>
                </div>
                <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">{{ __('reports.columns.due_date') }}</dt><dd dir="ltr"><bdi>{{ $payment->due_date->toDateString() }}</bdi></dd></div>
                    <div><dt class="text-slate-500">{{ __('reports.columns.contract') }}</dt><dd dir="ltr"><bdi>{{ $payment->contract?->contract_number ?? __('reports.pdf.not_available') }}</bdi></dd></div>
                    <div><dt class="text-slate-500">{{ __('reports.columns.amount_due') }}</dt><dd dir="ltr"><bdi>{{ number_format((float) $payment->amount_due, 2) }}</bdi></dd></div>
                    <div><dt class="text-slate-500">{{ __('reports.columns.amount_paid') }}</dt><dd dir="ltr"><bdi>{{ number_format((float) $payment->amount_paid, 2) }}</bdi></dd></div>
                    <div><dt class="text-slate-500">{{ __('reports.columns.remaining_amount') }}</dt><dd dir="ltr"><bdi>{{ number_format((float) $payment->remaining_amount, 2) }}</bdi></dd></div>
                    <div><dt class="text-slate-500">{{ __('reports.columns.paid_date') }}</dt><dd dir="ltr"><bdi>{{ $payment->payment_date?->toDateString() ?? __('reports.pdf.not_available') }}</bdi></dd></div>
                </dl>
                @if($payment->amount_paid_minor > 0)
                    @can('view', $payment)
                    <a class="tap-target mt-3 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-sm font-medium text-slate-800" href="{{ route('payments.show', $payment) }}">{{ __('reports.statement.view_receipt') }}</a>
                    @endcan
                @endif
            </article>
        @empty
            <p class="rounded border border-dashed p-4 text-sm text-slate-500">{{ __('reports.statement.empty') }}</p>
        @endforelse
    </div>

    <div class="mt-4 hidden md:block">
        <x-table min-width="min-w-[62rem]">
            <thead>
                <tr>
                    <th class="p-3 text-start">{{ __('reports.columns.due_date') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.tenant') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.unit') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.contract') }}</th>
                    <th class="p-3 text-end">{{ __('reports.columns.amount_due') }}</th>
                    <th class="p-3 text-end">{{ __('reports.columns.amount_paid') }}</th>
                    <th class="p-3 text-end">{{ __('reports.columns.remaining_amount') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.status') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.paid_date') }}</th>
                    <th class="p-3 text-start">{{ __('reports.columns.receipt') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statementRows as $payment)
                    <tr class="border-t">
                        <td class="p-3 whitespace-nowrap" dir="ltr"><bdi>{{ $payment->due_date->toDateString() }}</bdi></td>
                        <td class="p-3">{{ $payment->contract?->tenant?->full_name ?? __('reports.pdf.not_available') }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $payment->contract?->unit?->building?->name ?? __('reports.pdf.not_available') }} / <bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('reports.pdf.not_available') }}</bdi></td>
                        <td class="p-3 whitespace-nowrap" dir="ltr"><bdi>{{ $payment->contract?->contract_number ?? __('reports.pdf.not_available') }}</bdi></td>
                        <td class="p-3 text-end whitespace-nowrap" dir="ltr"><bdi>{{ number_format((float) $payment->amount_due, 2) }}</bdi></td>
                        <td class="p-3 text-end whitespace-nowrap" dir="ltr"><bdi>{{ number_format((float) $payment->amount_paid, 2) }}</bdi></td>
                        <td class="p-3 text-end whitespace-nowrap" dir="ltr"><bdi>{{ number_format((float) $payment->remaining_amount, 2) }}</bdi></td>
                        <td class="p-3 whitespace-nowrap">{{ __('payments.statuses.'.$payment->display_status_key) }}</td>
                        <td class="p-3 whitespace-nowrap" dir="ltr"><bdi>{{ $payment->payment_date?->toDateString() ?? __('reports.pdf.not_available') }}</bdi></td>
                        <td class="p-3 whitespace-nowrap">
                            @if($payment->amount_paid_minor > 0)
                                @can('view', $payment)
                                <a class="tap-target inline-flex items-center rounded border px-3 text-sm text-slate-700" href="{{ route('payments.show', $payment) }}">{{ __('reports.statement.view_receipt') }}</a>
                                @else
                                <span class="text-sm text-slate-500">{{ __('reports.pdf.not_available') }}</span>
                                @endcan
                            @else
                                <span class="text-sm text-slate-500">{{ __('reports.pdf.not_available') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="p-4 text-center text-slate-500" colspan="10">{{ __('reports.statement.empty') }}</td></tr>
                @endforelse
            </tbody>
        </x-table>
    </div>
</section>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
@foreach([
    'building-income' => __('reports.actions.building_income'),
    'unit-statement' => __('reports.actions.unit_statement'),
    'expenses' => __('reports.actions.expenses'),
    'overdue' => __('reports.actions.overdue'),
    'net-profit' => __('reports.actions.net_profit'),
    'monthly-summary' => __('reports.actions.monthly_summary'),
] as $type => $label)
<a class="tap-target flex items-center rounded border bg-white p-4 font-medium shadow-sm" href="{{ route('reports.pdf', ['type' => $type] + $filterQuery) }}">{{ $label }}</a>
@endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const building = document.querySelector('[data-building-select]');
        const unit = document.querySelector('[data-unit-select]');

        if (! building || ! unit) return;

        const syncUnits = () => {
            const buildingId = building.value;

            Array.from(unit.options).forEach((option) => {
                const optionBuildingId = option.dataset.buildingId;
                option.hidden = Boolean(buildingId && optionBuildingId && optionBuildingId !== buildingId);
            });

            if (unit.selectedOptions[0]?.hidden) {
                unit.value = '';
            }
        };

        building.addEventListener('change', syncUnits);
        syncUnits();
    });
</script>
@endsection
