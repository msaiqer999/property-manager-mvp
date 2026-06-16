@extends('layouts.app')
@section('content')
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ $contract->exists ? 'Edit contract' : 'Add contract' }}</h1>
    <p class="mt-1 text-sm text-slate-600">Choose an existing tenant and unit, then enter the rental terms used to generate payment schedules.</p>
</div>
<form method="post" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}" class="grid max-w-3xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($contract->exists) @method('put') @endif
<label class="block text-sm font-medium">Tenant <select name="tenant_id" class="tap-target mt-1 w-full rounded border p-2">@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id', $contract->tenant_id)==$tenant->id)>{{ $tenant->full_name }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Select an existing tenant record.</span></label>
<label class="block text-sm font-medium">Unit <select name="unit_id" class="tap-target mt-1 w-full rounded border p-2">@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $contract->unit_id)==$unit->id)>{{ $unit->building->name }} / {{ $unit->unit_number }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Select the unit covered by this contract.</span></label>
<label class="block text-sm font-medium">Contract number <input name="contract_number" value="{{ old('contract_number', $contract->contract_number) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">Status <select name="status" class="tap-target mt-1 w-full rounded border p-2">@foreach(['active','expired','terminated'] as $status)<option @selected(old('status', $contract->status)==$status)>{{ $status }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">Start date <input name="start_date" type="date" value="{{ old('start_date', optional($contract->start_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">End date <input name="end_date" type="date" value="{{ old('end_date', optional($contract->end_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">Rent amount <input name="rent_amount" type="number" step="0.01" value="{{ old('rent_amount', $contract->rent_amount) }}" class="tap-target mt-1 w-full rounded border p-2"><span class="mt-1 block text-xs text-slate-500">Used to calculate scheduled rent payments.</span></label>
<label class="block text-sm font-medium">Payment frequency <select name="payment_frequency" class="tap-target mt-1 w-full rounded border p-2">@foreach(['monthly','quarterly','semi_annual','annual'] as $frequency)<option @selected(old('payment_frequency', $contract->payment_frequency)==$frequency)>{{ $frequency }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Controls how often rent payments are generated.</span></label>
<label class="block text-sm font-medium">Deposit amount <input name="deposit_amount" type="number" step="0.01" value="{{ old('deposit_amount', $contract->deposit_amount) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium md:col-span-2">Notes <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $contract->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">Save</button>
</form>
@endsection
