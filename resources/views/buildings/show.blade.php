@extends('layouts.app')

@section('content')
@php
    $formatBuildingUnitMoney = fn ($value) => \App\Support\MoneyFormatter::forBuilding($building, $value);
@endphp

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ $building->name }}</h1>
    <div data-building-actions class="grid gap-2 sm:flex sm:flex-wrap">
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('units.bulk-create', ['building_id' => $building->id]) }}">{{ __('units.bulk.add_multiple') }}</a>
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('units.create', ['building_id' => $building->id]) }}">{{ __('units.add') }}</a>
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('buildings.edit', $building) }}">{{ __('app.actions.edit') }}</a>
    </div>
</div>

@if(session('status'))
    <p class="alert-success mb-4">{{ session('status') }}</p>
@endif

<div class="rounded border bg-brand-surface p-4 shadow-sm">
    <p>{{ $building->location }}</p>
    <p class="mt-2 text-sm text-brand-muted">{{ $building->description }}</p>
</div>

<h2 class="mb-2 mt-6 font-semibold">{{ __('buildings.sections.units') }}</h2>

@if($building->units->isEmpty())
    <section data-building-empty-units class="mb-4 rounded border bg-brand-surface p-5 text-center shadow-sm sm:p-6">
        <h3 class="text-lg font-semibold text-brand-text">{{ __('app.empty_states.units.title') }}</h3>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-brand-muted">{{ __('buildings.empty_units_guidance') }}</p>
        <div class="mt-4 grid gap-2 sm:flex sm:justify-center">
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('units.bulk-create', ['building_id' => $building->id]) }}">{{ __('units.bulk.add_multiple') }}</a>
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4 text-center text-sm font-medium text-brand-text" href="{{ route('units.create', ['building_id' => $building->id]) }}">{{ __('units.add') }}</a>
        </div>
    </section>
@endif

<div data-building-units-mobile-list class="grid gap-3 md:hidden">
    @foreach($building->units as $unit)
        <article data-building-unit-mobile-card class="rounded border bg-brand-surface p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold"><bdi dir="ltr">{{ $unit->unit_number }}</bdi></h3>
                    <p class="mt-1 text-sm text-brand-muted">{{ __('units.types.'.$unit->type) }}</p>
                </div>
                <x-status-badge class="shrink-0" :status="$unit->status" :label="__('units.statuses.'.$unit->status)" />
            </div>
            <p class="mt-3 text-sm text-brand-muted">{{ __('units.fields.rent') }}: <bdi dir="ltr">{{ $formatBuildingUnitMoney($unit->rent_amount) }}</bdi></p>
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('units.show', $unit) }}">{{ __('app.actions.view') }}</a>
        </article>
    @endforeach
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-[32rem]">
        <tbody>
            @foreach($building->units as $unit)
                <tr class="border-t">
                    <td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $unit->unit_number }}</bdi></td>
                    <td class="p-3 whitespace-nowrap"><x-status-badge :status="$unit->status" :label="__('units.statuses.'.$unit->status)" /></td>
                    <td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $formatBuildingUnitMoney($unit->rent_amount) }}</bdi></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>
@endsection
