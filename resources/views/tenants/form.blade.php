@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $tenant->exists ? __('tenants.edit') : __('tenants.add') }}</h1>
<form method="post" action="{{ $tenant->exists ? route('tenants.update', $tenant) : route('tenants.store') }}" class="grid max-w-2xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($tenant->exists) @method('put') @endif
@foreach(['full_name' => __('tenants.fields.full_name'), 'phone' => __('tenants.fields.phone'), 'email' => __('tenants.fields.email'), 'id_number' => __('tenants.fields.id_number'), 'nationality' => __('tenants.fields.nationality')] as $field => $label)
<label class="block text-sm font-medium">{{ $label }} <input name="{{ $field }}" value="{{ old($field, $tenant->$field) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
@endforeach
<label class="block text-sm font-medium md:col-span-2">{{ __('tenants.fields.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $tenant->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">{{ __('app.actions.save') }}</button>
</form>
@endsection
