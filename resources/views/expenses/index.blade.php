@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('expenses.title') }}</h1>
    @can('create', App\Models\Expense::class)
        <a class="tap-target flex items-center justify-center rounded bg-brand-primary px-4 text-sm font-medium text-white" href="{{ route('expenses.create') }}">{{ __('expenses.add') }}</a>
    @endcan
</div>

<form method="get" action="{{ route('expenses.index') }}" class="mb-4 grid gap-3 rounded border bg-brand-surface p-4 sm:grid-cols-2 lg:grid-cols-5">
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
        <button class="tap-target w-full rounded bg-brand-primary px-4 text-sm font-medium text-white">{{ __('app.actions.filter') }}</button>
    </div>
</form>

<div data-mobile-expenses-list class="grid gap-3 md:hidden">
    @forelse($expenses as $expense)
        <article class="rounded border bg-brand-surface p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-brand-text">{{ __('expenses.categories.'.$expense->category) }}</p>
                    <p class="mt-1 text-xs text-brand-muted">{{ __('expenses.show.date') }}: <bdi dir="ltr">{{ $expense->expense_date->toDateString() }}</bdi></p>
                </div>
                @if($expense->voided_at)
                    <x-status-badge class="shrink-0" status="voided" :label="__('expenses.lifecycle.voided')" />
                @else
                    <x-status-badge class="shrink-0" status="active" :label="__('expenses.lifecycle.active')" />
                @endif
            </div>

            <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-xs text-brand-muted">{{ __('expenses.show.building') }}</dt>
                    <dd class="font-medium text-brand-text">{{ $expense->building->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-brand-muted">{{ __('expenses.show.unit') }}</dt>
                    <dd class="font-medium text-brand-text"><bdi dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</bdi></dd>
                </div>
                <div>
                    <dt class="text-xs text-brand-muted">{{ __('expenses.show.amount') }}</dt>
                    <dd class="font-semibold text-brand-text"><bdi dir="ltr">{{ number_format($expense->amount, 2) }}</bdi></dd>
                </div>
                <div>
                    <dt class="text-xs text-brand-muted">{{ __('expenses.show.status') }}</dt>
                    <dd class="font-medium text-brand-text">{{ $expense->voided_at ? __('expenses.lifecycle.voided') : __('expenses.lifecycle.active') }}</dd>
                </div>
            </dl>

            <div class="mt-4">
                <a class="tap-target inline-flex w-full items-center justify-center rounded border px-3 text-sm font-medium text-brand-text" href="{{ route('expenses.show', $expense) }}">{{ __('expenses.view') }}</a>
            </div>
        </article>
    @empty
        <section data-empty-state-expenses class="rounded border bg-brand-surface p-5 text-center shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-brand-text">{{ __('app.empty_states.expenses.title') }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-brand-muted">{{ __('app.empty_states.expenses.body') }}</p>
            @can('create', App\Models\Expense::class)
                <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('expenses.create') }}">{{ __('app.empty_states.expenses.action') }}</a>
            @endcan
        </section>
    @endforelse
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-full">
        <thead>
            <tr>
                <th class="p-3 text-start">{{ __('expenses.show.date') }}</th>
                <th class="p-3 text-start">{{ __('expenses.show.building') }} / {{ __('expenses.show.unit') }}</th>
                <th class="p-3 text-start">{{ __('expenses.show.category') }}</th>
                <th class="p-3 text-end">{{ __('expenses.show.amount') }}</th>
                <th class="p-3 text-center">{{ __('expenses.show.status') }}</th>
                <th class="p-3 text-center">{{ __('expenses.show.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr class="border-t align-top">
                    <td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $expense->expense_date->toDateString() }}</bdi></td>
                    <td class="p-3">
                        <div class="font-medium text-brand-text">{{ $expense->building->name }}</div>
                        <div class="mt-1 text-xs text-brand-muted">{{ __('expenses.show.unit') }}: <bdi dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</bdi></div>
                    </td>
                    <td class="p-3 capitalize">{{ __('expenses.categories.'.$expense->category) }}</td>
                    <td class="p-3 text-end whitespace-nowrap"><bdi dir="ltr">{{ number_format($expense->amount, 2) }}</bdi></td>
                    <td class="p-3 text-center whitespace-nowrap">
                        @if($expense->voided_at)
                            <x-status-badge status="voided" :label="__('expenses.lifecycle.voided')" />
                        @else
                            <x-status-badge status="active" :label="__('expenses.lifecycle.active')" />
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <a class="tap-target inline-flex items-center rounded border px-3 text-sm font-medium text-brand-text" href="{{ route('expenses.show', $expense) }}">{{ __('expenses.view') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4">
                        <section data-empty-state-expenses class="rounded border bg-brand-surface p-5 text-center shadow-sm">
                            <h2 class="text-lg font-semibold text-brand-text">{{ __('app.empty_states.expenses.title') }}</h2>
                            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-brand-muted">{{ __('app.empty_states.expenses.body') }}</p>
                            @can('create', App\Models\Expense::class)
                                <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('expenses.create') }}">{{ __('app.empty_states.expenses.action') }}</a>
                            @endcan
                        </section>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>
</div>

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
