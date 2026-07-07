@extends('layouts.app')

@section('content')
@php
    $oldRows = old('units');
    $rows = is_array($oldRows) && count($oldRows) > 0
        ? $oldRows
        : array_fill(0, $rowCount, [
            'unit_number' => '',
            'type' => 'apartment',
            'status' => 'vacant',
            'rent_amount' => '',
            'rooms' => '',
            'size' => '',
            'notes' => '',
        ]);
@endphp

<div class="mb-4 grid gap-3 sm:flex sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('units.bulk.manual_title') }}</h1>
        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ __('units.bulk.manual_description') }}</p>
    </div>
    <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-800" href="{{ route('units.index') }}">
        {{ __('app.actions.back') }}
    </a>
</div>

<form method="post" action="{{ route('units.bulk-store') }}" class="grid gap-4">
    @csrf

    @if($errors->any())
        <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <p class="font-medium">{{ __('app.validation.check_fields') }}</p>
            <ul class="mt-2 list-disc space-y-1 ps-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded border bg-white p-4 shadow-sm">
        <label class="block text-sm font-medium text-slate-800">
            {{ __('units.bulk.choose_building') }}
            <select name="building_id" class="form-select-safe tap-target mt-1 w-full rounded border p-2" required>
                <option value="">{{ __('units.bulk.choose_building_placeholder') }}</option>
                @foreach($buildings as $building)
                    <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>{{ $building->name }}</option>
                @endforeach
            </select>
        </label>
        <p class="mt-2 text-sm text-slate-500">{{ __('units.bulk.choose_building_help') }}</p>
    </section>

    <div data-bulk-unit-entry-rows class="grid gap-4">
        @foreach($rows as $index => $row)
            <section data-bulk-unit-entry-row class="rounded border bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">{{ __('units.bulk.row_title', ['number' => $index + 1]) }}</h2>
                    <span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ __('units.bulk.unit_number_required_hint') }}</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="block text-sm font-medium">
                        {{ __('units.fields.unit_number') }}
                        <input name="units[{{ $index }}][unit_number]" value="{{ $row['unit_number'] ?? '' }}" class="tap-target mt-1 w-full rounded border p-2" autocomplete="off">
                    </label>

                    <label class="block text-sm font-medium">
                        {{ __('units.fields.type') }}
                        <select name="units[{{ $index }}][type]" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(($row['type'] ?? 'apartment') === $type)>{{ __('units.types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-sm font-medium">
                        {{ __('units.fields.status') }}
                        <select name="units[{{ $index }}][status]" class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(($row['status'] ?? 'vacant') === $status)>{{ __('units.statuses.'.$status) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-sm font-medium">
                        {{ __('units.fields.rent') }}
                        <input name="units[{{ $index }}][rent_amount]" type="number" step="0.01" min="0" value="{{ $row['rent_amount'] ?? '' }}" class="tap-target mt-1 w-full rounded border p-2">
                    </label>

                    <label class="block text-sm font-medium">
                        {{ __('units.fields.rooms') }}
                        <input name="units[{{ $index }}][rooms]" type="number" min="0" value="{{ $row['rooms'] ?? '' }}" class="tap-target mt-1 w-full rounded border p-2">
                    </label>

                    <label class="block text-sm font-medium">
                        {{ __('units.fields.size') }}
                        <input name="units[{{ $index }}][size]" type="number" step="0.01" min="0" value="{{ $row['size'] ?? '' }}" class="tap-target mt-1 w-full rounded border p-2">
                    </label>

                    <label class="block text-sm font-medium sm:col-span-2 lg:col-span-3">
                        {{ __('units.fields.notes') }}
                        <textarea name="units[{{ $index }}][notes]" rows="2" class="mt-1 w-full rounded border p-2">{{ $row['notes'] ?? '' }}</textarea>
                    </label>
                </div>
            </section>
        @endforeach
    </div>

    <div class="grid gap-3 sm:flex sm:items-center sm:justify-end">
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border bg-white px-4 text-center text-sm font-medium text-slate-800" href="{{ route('units.index') }}">
            {{ __('app.actions.cancel') }}
        </a>
        <button type="submit" class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white">
            {{ __('units.bulk.save_manual_units') }}
        </button>
    </div>
</form>
@endsection
