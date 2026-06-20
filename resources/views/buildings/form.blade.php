@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $building->exists ? __('buildings.edit') : __('buildings.add') }}</h1>
<form method="post" action="{{ $building->exists ? route('buildings.update', $building) : route('buildings.store') }}" class="max-w-xl space-y-4 rounded border bg-white p-4 shadow-sm">
@csrf @if($building->exists) @method('put') @endif
<label class="block text-sm font-medium">{{ __('buildings.fields.name') }} <input name="name" value="{{ old('name', $building->name) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('buildings.fields.location') }} <input name="location" value="{{ old('location', $building->location) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">{{ __('buildings.fields.description') }} <textarea name="description" rows="4" class="mt-1 w-full rounded border p-2">{{ old('description', $building->description) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white sm:w-auto">{{ __('app.actions.save') }}</button>
</form>
@endsection
