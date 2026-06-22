@extends('layouts.app')
@section('content')
@php($isRenewal = isset($renewalSource) && $renewalSource)
@php($displayContract = $isRenewal ? $renewalSource : $contract)
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ $isRenewal ? __('contracts.form.prepare_renewal_title') : ($contract->exists ? __('contracts.form.edit_title') : __('contracts.form.add_title')) }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $isRenewal ? __('contracts.form.renewal_description') : __('contracts.form.description').' '.($contract->exists ? __('contracts.form.number_read_only') : __('contracts.form.number_generated')) }}</p>
</div>
@php($tenantMode = old('tenant_mode', 'existing'))
<form method="post" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}" class="grid max-w-3xl gap-4 rounded border bg-white p-4 shadow-sm md:grid-cols-2">
@csrf @if($contract->exists) @method('put') @endif
@if($isRenewal)<input type="hidden" name="renew_from" value="{{ $renewalSource->id }}">@endif
@if(! $contract->exists && ! $isRenewal)
<fieldset class="rounded border p-3 md:col-span-2">
    <legend class="px-1 text-sm font-medium">{{ __('contracts.form.tenant') }}</legend>
    <div class="mt-2 grid gap-2 sm:grid-cols-2">
        <label class="flex items-center gap-2 text-sm"><input type="radio" name="tenant_mode" value="existing" @checked($tenantMode !== 'new')> {{ __('contracts.form.select_existing_tenant') }}</label>
        <label class="flex items-center gap-2 text-sm"><input type="radio" name="tenant_mode" value="new" @checked($tenantMode === 'new')> {{ __('contracts.form.add_new_tenant') }}</label>
    </div>
</fieldset>
@endif
@if($contract->exists || $isRenewal)
<div class="block text-sm font-medium">{{ __('contracts.form.tenant') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ $displayContract->tenant?->full_name }}</div><span class="mt-1 block text-xs text-slate-500">{{ $isRenewal ? __('contracts.form.tenant_fixed_renewal') : __('contracts.form.tenant_locked') }}</span></div>
@else
<label id="existing-tenant-fields" class="block text-sm font-medium {{ $tenantMode === 'new' ? 'hidden' : '' }}">{{ __('contracts.form.tenant') }} <select name="tenant_id" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode === 'new')>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected(old('tenant_id', $contract->tenant_id)==$tenant->id)>{{ $tenant->full_name }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.select_existing_tenant_hint') }}</span></label>
@endif
@if(! $contract->exists && ! $isRenewal)
<div id="new-tenant-fields" class="grid gap-4 rounded border border-dashed p-3 md:col-span-2 md:grid-cols-2 {{ $tenantMode === 'new' ? '' : 'hidden' }}">
    <p class="text-sm text-slate-600 md:col-span-2">{{ __('contracts.form.new_tenant_hint') }}</p>
    <label class="block text-sm font-medium">{{ __('contracts.form.full_name') }} <input name="new_tenant[full_name]" value="{{ old('new_tenant.full_name') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
    <label class="block text-sm font-medium">{{ __('contracts.form.phone') }} <input name="new_tenant[phone]" value="{{ old('new_tenant.phone') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
    <label class="block text-sm font-medium">{{ __('contracts.form.email') }} <input name="new_tenant[email]" type="email" value="{{ old('new_tenant.email') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
    <label class="block text-sm font-medium">{{ __('contracts.form.id_number') }} <input name="new_tenant[id_number]" value="{{ old('new_tenant.id_number') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
    <label class="block text-sm font-medium">{{ __('contracts.form.nationality') }} <input name="new_tenant[nationality]" value="{{ old('new_tenant.nationality') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
    <label class="block text-sm font-medium md:col-span-2">{{ __('contracts.form.tenant_notes') }} <textarea name="new_tenant[notes]" rows="3" class="mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')>{{ old('new_tenant.notes') }}</textarea></label>
</div>
@endif
@if($contract->exists || $isRenewal)
<div class="block text-sm font-medium">{{ __('contracts.form.unit') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ $displayContract->unit?->building?->name }} / {{ $displayContract->unit?->unit_number }}</div><span class="mt-1 block text-xs text-slate-500">{{ $isRenewal ? __('contracts.form.unit_fixed_renewal') : __('contracts.form.unit_locked') }}</span></div>
@else
<label class="block text-sm font-medium">{{ __('contracts.form.unit') }} <select name="unit_id" class="tap-target mt-1 w-full rounded border p-2">@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $contract->unit_id)==$unit->id)>{{ $unit->building->name }} / {{ $unit->unit_number }} - {{ $unit->availability_label }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.unit_hint') }}</span></label>
@endif
@if($contract->exists)<label class="block text-sm font-medium">{{ __('contracts.form.contract_number') }} <input value="{{ $contract->contract_number }}" class="tap-target mt-1 w-full rounded border bg-slate-50 p-2" readonly><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.contract_number_locked') }}</span></label>@endif
@if($isRenewal)<div class="block text-sm font-medium">{{ __('contracts.form.status') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ __('contracts.statuses.active') }}</div><input type="hidden" name="status" value="active"></div>@else<label class="block text-sm font-medium">{{ __('contracts.form.status') }} <select name="status" class="tap-target mt-1 w-full rounded border p-2">@foreach(['active','expired'] as $status)<option value="{{ $status }}" @selected(old('status', $contract->status)==$status)>{{ __('contracts.statuses.'.$status) }}</option>@endforeach</select></label>@endif
<label class="block text-sm font-medium">{{ __('contracts.form.start_date') }} <input name="start_date" type="date" value="{{ old('start_date', optional($contract->start_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('contracts.form.end_date') }} <input name="end_date" type="date" value="{{ old('end_date', optional($contract->end_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required></label>
<label class="block text-sm font-medium">{{ __('contracts.form.rent_amount') }} <input name="rent_amount" type="number" step="0.01" value="{{ old('rent_amount', $contract->rent_amount) }}" class="tap-target mt-1 w-full rounded border p-2"><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.rent_amount_hint') }}</span></label>
<label class="block text-sm font-medium">{{ __('contracts.form.payment_frequency') }} <select name="payment_frequency" class="tap-target mt-1 w-full rounded border p-2">@foreach(['monthly','quarterly','semi_annual','annual'] as $frequency)<option value="{{ $frequency }}" @selected(old('payment_frequency', $contract->payment_frequency)==$frequency)>{{ __('contracts.frequencies.'.$frequency) }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.payment_frequency_hint') }}</span></label>
<label class="block text-sm font-medium">{{ __('contracts.form.deposit_amount') }} <input name="deposit_amount" type="number" step="0.01" value="{{ old('deposit_amount', $contract->deposit_amount) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium md:col-span-2">{{ __('contracts.form.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $contract->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white md:col-span-2">{{ __('contracts.form.save') }}</button>
</form>
@if(! $contract->exists && ! $isRenewal)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="tenant_mode"]');
    const existing = document.getElementById('existing-tenant-fields');
    const newFields = document.getElementById('new-tenant-fields');

    const setDisabled = (container, disabled) => {
        container.querySelectorAll('input, select, textarea').forEach((field) => {
            field.disabled = disabled;
        });
    };

    const syncTenantMode = () => {
        const mode = document.querySelector('input[name="tenant_mode"]:checked')?.value || 'existing';
        const addingNew = mode === 'new';

        existing.classList.toggle('hidden', addingNew);
        newFields.classList.toggle('hidden', ! addingNew);
        setDisabled(existing, addingNew);
        setDisabled(newFields, ! addingNew);
    };

    radios.forEach((radio) => radio.addEventListener('change', syncTenantMode));
    syncTenantMode();
});
</script>
@endif
@endsection
