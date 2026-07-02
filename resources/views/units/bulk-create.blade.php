@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ __('units.bulk.title') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('units.bulk.description', ['building' => $building->name]) }}</p>
</div>

<form method="post" action="{{ route('buildings.units.bulk.preview', $building) }}" class="grid max-w-3xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
    @csrf
    <label class="block text-sm font-medium">{{ __('units.bulk.prefix') }} <input name="prefix" value="{{ old('prefix') }}" class="tap-target mt-1 w-full rounded border p-2"></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.start_number') }} <input name="start_number" type="number" min="0" value="{{ old('start_number') }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.end_number') }} <input name="end_number" type="number" min="0" value="{{ old('end_number') }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.default_type') }} <select name="type" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach($types as $type)<option value="{{ $type }}" @selected(old('type', 'apartment') === $type)>{{ __('units.types.'.$type) }}</option>@endforeach</select></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.default_rent') }} <input name="rent_amount" type="number" step="0.01" min="0" value="{{ old('rent_amount', 0) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.default_rooms') }} <input name="rooms" type="number" min="0" value="{{ old('rooms') }}" class="tap-target mt-1 w-full rounded border p-2"></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.default_size') }} <input name="size" type="number" step="0.01" min="0" value="{{ old('size') }}" class="tap-target mt-1 w-full rounded border p-2"></label>
    <label class="block text-sm font-medium">{{ __('units.bulk.default_status') }} <select name="status" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status', 'vacant') === $status)>{{ __('units.statuses.'.$status) }}</option>@endforeach</select></label>
    <label class="block text-sm font-medium md:col-span-2">{{ __('units.bulk.default_notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes') }}</textarea></label>
    <button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">{{ __('units.bulk.generate_preview') }}</button>
</form>
@endsection
