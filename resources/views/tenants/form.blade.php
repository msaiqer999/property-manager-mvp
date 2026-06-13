@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ $tenant->exists ? 'Edit tenant' : 'Add tenant' }}</h1>
<form method="post" action="{{ $tenant->exists ? route('tenants.update', $tenant) : route('tenants.store') }}" class="grid max-w-2xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($tenant->exists) @method('put') @endif
@foreach(['full_name'=>'Full name','phone'=>'Phone','email'=>'Email','id_number'=>'ID number','nationality'=>'Nationality'] as $field => $label)
<label class="block text-sm font-medium">{{ $label }} <input name="{{ $field }}" value="{{ old($field, $tenant->$field) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
@endforeach
<label class="block text-sm font-medium md:col-span-2">Notes <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $tenant->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">Save</button>
</form>
@endsection
