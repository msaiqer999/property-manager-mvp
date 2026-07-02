@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('expenses.title') }}</h1>
    @can('create', App\Models\Expense::class)
        <a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('expenses.create') }}">{{ __('expenses.add') }}</a>
    @endcan
</div>

<form method="get" action="{{ route('expenses.index') }}" class="mb-4 grid gap-3 rounded border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5">
    <label class="block text-sm font-medium">
        {{ __('expenses.form.building') }}
        <select name="building_id" data-building-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('expenses.filters.all_buildings') }}</option>
            @foreach($buildings as $building)
                <option value="{{ $building->id }}" @selected(request('building_id') == $building->id)>{{ $building->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('expenses.form.unit') }}
        <select name="unit_id" data-unit-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('expenses.filters.all_units') }}</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" @selected(request('unit_id') == $unit->id)><bdi dir="ltr">{{ $unit->unit_number }}</bdi></option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('expenses.form.category') }}
        <select name="category" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="">{{ __('expenses.filters.all_categories') }}</option>
            @foreach(['maintenance','electricity','water','cleaning','security','management','other'] as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>{{ __('expenses.categories.'.$category) }}</option>
            @endforeach
        </select>
    </label>

    <label class="block text-sm font-medium">
        {{ __('expenses.show.status') }}
        <select name="lifecycle" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
            <option value="active" @selected($lifecycle === 'active')>{{ __('expenses.lifecycle.active') }}</option>
            <option value="voided" @selected($lifecycle === 'voided')>{{ __('expenses.lifecycle.voided_filter') }}</option>
            <option value="all" @selected($lifecycle === 'all')>{{ __('expenses.lifecycle.all') }}</option>
        </select>
    </label>

    <div class="flex items-end">
        <button class="tap-target w-full rounded bg-slate-900 px-4 text-sm font-medium text-white">{{ __('app.actions.filter') }}</button>
    </div>
</form>

<x-table min-width="min-w-[60rem]">
    <thead>
        <tr>
            <th class="p-4 text-start">{{ __('expenses.show.date') }}</th>
            <th class="p-4 text-start">{{ __('expenses.show.building') }}</th>
            <th class="p-4 text-center">{{ __('expenses.show.unit') }}</th>
            <th class="p-4 text-start">{{ __('expenses.show.category') }}</th>
            <th class="p-4 text-end">{{ __('expenses.show.amount') }}</th>
            <th class="p-4 text-center">{{ __('expenses.show.status') }}</th>
            <th class="p-4 text-center">{{ __('expenses.show.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($expenses as $expense)
            <tr class="border-t">
                <td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $expense->expense_date->toDateString() }}</bdi></td>
                <td class="p-4 font-medium">{{ $expense->building->name }}</td>
                <td class="p-4 text-center whitespace-nowrap"><bdi dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</bdi></td>
                <td class="p-4 capitalize whitespace-nowrap">{{ __('expenses.categories.'.$expense->category) }}</td>
                <td class="p-4 text-end whitespace-nowrap"><bdi dir="ltr">{{ number_format($expense->amount, 2) }}</bdi></td>
                <td class="p-4 text-center whitespace-nowrap">
                    @if($expense->voided_at)
                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('expenses.lifecycle.voided') }}</span>
                    @else
                        <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">{{ __('expenses.lifecycle.active') }}</span>
                    @endif
                </td>
                <td class="p-4 text-center whitespace-nowrap">
                    <a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('expenses.show', $expense) }}">{{ __('expenses.view') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-4 text-center text-slate-500">{{ __('expenses.empty') }}</td></tr>
        @endforelse
    </tbody>
</x-table>

{{ $expenses->links() }}

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
