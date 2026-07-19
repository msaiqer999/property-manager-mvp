@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $unit->exists ? __('units.edit') : __('units.add') }}</h1>
<form method="post" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}" class="grid max-w-2xl gap-4 rounded border bg-brand-surface p-4 shadow-sm md:grid-cols-2">
@csrf @if($unit->exists) @method('put') @endif
<label class="block text-sm font-medium">{{ __('units.fields.building') }} <select name="building_id" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach($buildings as $building)<option value="{{ $building->id }}" @selected(old('building_id', $unit->building_id)==$building->id)>{{ $building->name }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('units.fields.unit_number') }} <input name="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('units.fields.type') }} <select name="type" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach($types as $type)<option value="{{ $type }}" @selected(old('type', $unit->type)==$type)>{{ __('units.types.'.$type) }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('units.fields.status') }} <select name="status" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach(['vacant','rented','maintenance'] as $status)<option value="{{ $status }}" @selected(old('status', $unit->status)==$status)>{{ __('units.statuses.'.$status) }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">{{ __('units.fields.size') }} <input name="size" type="number" step="0.01" value="{{ old('size', $unit->size) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">{{ __('units.fields.rooms') }} <input name="rooms" type="number" value="{{ old('rooms', $unit->rooms) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">{{ __('units.fields.rent') }} <input name="rent_amount" type="number" step="0.01" value="{{ old('rent_amount', $unit->rent_amount) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium md:col-span-2">{{ __('units.fields.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $unit->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-brand-primary px-4 text-white md:col-span-2">{{ __('app.actions.save') }}</button>
</form>
@endsection
