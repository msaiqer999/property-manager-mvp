@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ $building->name }}</h1>
    <div class="flex flex-wrap gap-2">
        <a class="tap-target inline-flex items-center rounded bg-slate-900 px-3 text-sm text-white" href="{{ route('units.create', ['building_id' => $building->id]) }}">{{ __('units.add') }}</a>
        <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('buildings.edit', $building) }}">{{ __('app.actions.edit') }}</a>
    </div>
</div>

<div class="rounded border bg-white p-4">
    <p>{{ $building->location }}</p>
    <p class="mt-2 text-sm text-slate-600">{{ $building->description }}</p>
</div>

<h2 class="mb-2 mt-6 font-semibold">{{ __('buildings.sections.units') }}</h2>
<x-table min-width="min-w-[32rem]">
    <tbody>
        @foreach($building->units as $unit)
            <tr class="border-t">
                <td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ $unit->unit_number }}</bdi></td>
                <td class="p-3 whitespace-nowrap">{{ __('units.statuses.'.$unit->status) }}</td>
                <td class="p-3 whitespace-nowrap"><bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></td>
            </tr>
        @endforeach
    </tbody>
</x-table>
@endsection
