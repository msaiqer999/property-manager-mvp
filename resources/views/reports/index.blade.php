@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ __('reports.title') }}</h1>

<form method="get" action="{{ route('reports.index') }}" class="mb-4 grid gap-3 rounded border bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
    <label class="block text-sm font-medium">
        {{ __('reports.filters.building') }}
        <select name="building_id" data-building-select class="tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('reports.filters.all_buildings') }}</option>
            @foreach($buildings as $building)
                <option value="{{ $building->id }}" @selected($filters['building_id'] === $building->id)>{{ $building->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('reports.filters.unit') }}
        <select name="unit_id" data-unit-select class="tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('reports.filters.all_units') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" @selected($filters['unit_id'] === $unit->id)><bdi dir="ltr">{{ $unit->unit_number }}</bdi> - {{ $unit->building->name }}</option>
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
