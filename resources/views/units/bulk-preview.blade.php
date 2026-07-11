@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ __('units.bulk.preview_title') }}</h1>
    <p class="mt-1 text-sm text-brand-muted">{{ __('units.bulk.preview_description', ['building' => $building->name]) }}</p>
</div>

<form method="post" action="{{ route('buildings.units.bulk.store', $building) }}" class="space-y-4">
    @csrf
    <x-table min-width="min-w-[72rem]">
        <thead class="bg-brand-background">
            <tr>
                <th class="p-3 text-start font-medium">{{ __('units.fields.unit_number') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.type') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.rent') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.rooms') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.size') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.status') }}</th>
                <th class="p-3 text-start font-medium">{{ __('units.fields.notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
                <tr class="border-t">
                    <td class="p-2"><input name="units[{{ $index }}][unit_number]" value="{{ old("units.$index.unit_number", $row['unit_number']) }}" class="tap-target w-32 rounded border p-2" required></td>
                    <td class="p-2"><select name="units[{{ $index }}][type]" class="form-select-safe tap-target w-40 rounded border p-2">@foreach($types as $type)<option value="{{ $type }}" @selected(old("units.$index.type", $row['type']) === $type)>{{ __('units.types.'.$type) }}</option>@endforeach</select></td>
                    <td class="p-2"><input name="units[{{ $index }}][rent_amount]" type="number" step="0.01" min="0" value="{{ old("units.$index.rent_amount", $row['rent_amount']) }}" class="tap-target w-32 rounded border p-2" required></td>
                    <td class="p-2"><input name="units[{{ $index }}][rooms]" type="number" min="0" value="{{ old("units.$index.rooms", $row['rooms']) }}" class="tap-target w-24 rounded border p-2"></td>
                    <td class="p-2"><input name="units[{{ $index }}][size]" type="number" step="0.01" min="0" value="{{ old("units.$index.size", $row['size']) }}" class="tap-target w-28 rounded border p-2"></td>
                    <td class="p-2"><select name="units[{{ $index }}][status]" class="form-select-safe tap-target w-40 rounded border p-2">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old("units.$index.status", $row['status']) === $status)>{{ __('units.statuses.'.$status) }}</option>@endforeach</select></td>
                    <td class="p-2"><input name="units[{{ $index }}][notes]" value="{{ old("units.$index.notes", $row['notes']) }}" class="tap-target w-56 rounded border p-2"></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    <button class="tap-target rounded bg-brand-primary px-4 text-white">{{ __('units.bulk.create_units') }}</button>
</form>
@endsection
