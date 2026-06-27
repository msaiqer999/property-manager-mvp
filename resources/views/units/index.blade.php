@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('units.title') }}</h1>
    <a class="tap-target flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('units.create') }}">{{ __('units.add') }}</a>
</div>

<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-3">
    <select name="building_id" class="tap-target min-h-11 rounded border p-2">
        <option value="">{{ __('units.filters.all_buildings') }}</option>
        @foreach($buildings as $building)
            <option value="{{ $building->id }}" @selected(request('building_id') == $building->id)>{{ $building->name }}</option>
        @endforeach
    </select>
    <select name="status" class="tap-target min-h-11 rounded border p-2">
        <option value="">{{ __('units.filters.all_statuses') }}</option>
        @foreach(['vacant','rented','maintenance'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('units.statuses.'.$status) }}</option>
        @endforeach
    </select>
    <button class="tap-target min-h-11 rounded bg-slate-900 px-4 text-white">{{ __('app.actions.filter') }}</button>
</form>

<div data-mobile-units-list class="grid gap-3 md:hidden">
    @foreach($units as $unit)
        <article data-unit-mobile-card class="rounded border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold"><bdi dir="ltr">{{ $unit->unit_number }}</bdi></h2>
                    <p class="mt-1 break-words text-sm text-slate-600">{{ $unit->building->name }}</p>
                </div>
                <span class="shrink-0 rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('units.statuses.'.$unit->status) }}</span>
            </div>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">{{ __('units.fields.type') }}</dt>
                    <dd>{{ __('units.types.'.$unit->type) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">{{ __('units.fields.rent') }}</dt>
                    <dd><bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></dd>
                </div>
            </dl>
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('units.show', $unit) }}">{{ __('app.actions.view') }}</a>
        </article>
    @endforeach
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-[44rem]">
        <thead>
            <tr>
                <th class="p-4 text-start">{{ __('units.fields.unit') }}</th>
                <th class="p-4 text-start">{{ __('units.fields.building') }}</th>
                <th class="p-4 text-center">{{ __('units.fields.status') }}</th>
                <th class="p-4 text-end">{{ __('units.fields.rent') }}</th>
                <th class="p-4 text-center">{{ __('units.fields.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($units as $unit)
                <tr class="border-t">
                    <td class="p-4 font-medium whitespace-nowrap"><bdi dir="ltr">{{ $unit->unit_number }}</bdi></td>
                    <td class="p-4">{{ $unit->building->name }}</td>
                    <td class="p-4 text-center whitespace-nowrap"><span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('units.statuses.'.$unit->status) }}</span></td>
                    <td class="p-4 text-end whitespace-nowrap"><bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></td>
                    <td class="p-4 text-center whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('units.show', $unit) }}">{{ __('app.actions.view') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>

{{ $units->links() }}
@endsection
