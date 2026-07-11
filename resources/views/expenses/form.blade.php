@extends('layouts.app')

@section('content')
@php($selectedBuildingId = old('building_id', $expense->building_id))

<div class="mb-4 max-w-3xl">
    <h1 class="text-xl font-semibold">{{ $expense->exists ? __('expenses.edit') : __('expenses.add') }}</h1>
    <p class="mt-1 text-sm text-brand-muted">{{ __('expenses.form.description') }}</p>
</div>

<form method="post" enctype="multipart/form-data" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="max-w-3xl rounded border bg-brand-surface p-4 shadow-sm sm:p-5">
    @csrf
    @if($expense->exists)
        @method('put')
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <label class="block text-sm font-medium">
            {{ __('expenses.form.building') }}
            <select name="building_id" data-building-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                @foreach($buildings as $building)
                    <option value="{{ $building->id }}" @selected($selectedBuildingId == $building->id)>{{ $building->name }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.building_help') }}</span>
        </label>

        <label class="block text-sm font-medium">
            {{ __('expenses.form.unit') }}
            <select name="unit_id" data-unit-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                <option value="">{{ __('expenses.form.optional_unit') }}</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" data-building-id="{{ $unit->building_id }}" @selected(old('unit_id', $expense->unit_id) == $unit->id)><bdi dir="ltr">{{ $unit->unit_number }}</bdi></option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.unit_help') }}</span>
        </label>

        <label class="block text-sm font-medium">{{ __('expenses.form.category') }}
            <select name="category" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                @foreach(['maintenance','electricity','water','cleaning','security','management','other'] as $category)
                    <option value="{{ $category }}" @selected(old('category', $expense->category) == $category)>{{ __('expenses.categories.'.$category) }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.category_help') }}</span>
        </label>

        <label class="block text-sm font-medium">{{ __('expenses.form.amount') }}
            <input name="amount" type="number" step="0.01" value="{{ old('amount', $expense->amount) }}" class="tap-target mt-1 w-full rounded border p-2">
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.amount_help') }}</span>
        </label>

        <label class="block text-sm font-medium">{{ __('expenses.form.date') }}
            <input name="expense_date" type="date" value="{{ old('expense_date', optional($expense->expense_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2">
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.date_help') }}</span>
        </label>

        <label class="block text-sm font-medium">{{ __('expenses.form.invoice') }}
            <input name="invoice_image" type="file" accept="image/*" class="tap-target mt-1 w-full rounded border p-2">
            <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('expenses.form.invoice_help') }}</span>
        </label>

        <label class="block text-sm font-medium md:col-span-2">{{ __('expenses.form.notes') }}
            <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $expense->notes) }}</textarea>
        </label>
    </div>

    <div class="mt-5">
        <button class="tap-target w-full rounded bg-brand-primary px-4 text-white">{{ __('expenses.form.save') }}</button>
    </div>
</form>

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
