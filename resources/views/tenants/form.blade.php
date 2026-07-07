@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $tenant->exists ? __('tenants.edit') : __('tenants.add') }}</h1>
<form data-tenant-form method="post" action="{{ $tenant->exists ? route('tenants.update', $tenant) : route('tenants.store') }}" class="grid max-w-2xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($tenant->exists) @method('put') @endif
@foreach(['full_name' => __('tenants.fields.full_name'), 'phone' => __('tenants.fields.phone'), 'email' => __('tenants.fields.email'), 'id_number' => __('tenants.fields.id_number'), 'nationality' => __('tenants.fields.nationality')] as $field => $label)
<label class="block text-sm font-medium">{{ $label }} <input name="{{ $field }}" value="{{ old($field, $tenant->$field) }}" class="tap-target mt-1 min-h-11 w-full rounded border p-3"></label>
@endforeach
<label class="block text-sm font-medium md:col-span-2">{{ __('tenants.fields.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-3">{{ old('notes', $tenant->notes) }}</textarea></label>
<div class="grid gap-2 md:col-span-2 sm:grid-cols-2">
    <button class="tap-target min-h-11 w-full rounded bg-slate-900 px-4 text-white">{{ __('app.actions.save') }}</button>
    @if(! $tenant->exists)
        @can('create', \App\Models\Contract::class)
            <button type="submit" name="after_save" value="create_contract" class="tap-target min-h-11 w-full rounded border border-slate-300 bg-white px-4 text-slate-900">{{ __('tenants.actions.save_and_create_contract') }}</button>
        @endcan
    @endif
</div>
</form>
@endsection
