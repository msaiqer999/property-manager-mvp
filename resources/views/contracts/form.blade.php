@extends('layouts.app')
@section('content')
@php
    $contract = $contract ?? new \App\Models\Contract;
    $renewalSource = $renewalSource ?? null;
    $isRenewal = $renewalSource !== null;
    $isEdit = isset($contract) && (bool) $contract->exists;
    $displayContract = $isRenewal ? $renewalSource : $contract;
    $buildings = collect($buildings ?? []);
    $units = collect($units ?? []);
    $tenants = collect($tenants ?? []);
    $contractMode = (string) old('contract_mode', request('contract_mode', $contractMode ?? 'vacant'));
    $contractMode = in_array($contractMode, ['vacant', 'future'], true) ? $contractMode : 'vacant';
    $tenantMode = (string) old('tenant_mode', $tenantMode ?? 'existing');
    $tenantMode = in_array($tenantMode, ['existing', 'new'], true) ? $tenantMode : 'existing';
    $selectedUnitId = (string) old('unit_id', $selectedUnitId ?? $contract->unit_id ?? $renewalSource?->unit_id ?? '');
    $selectedTenantId = (string) old('tenant_id', $selectedTenantId ?? $contract->tenant_id ?? $renewalSource?->tenant_id ?? '');
    $selectedUnit = $selectedUnitId !== '' ? $units->firstWhere('id', (int) $selectedUnitId) : null;
    $selectedBuildingId = (string) old('building_id', request('building_id', $selectedBuildingId ?? $selectedUnit?->building_id ?? ''));
    $contractUnitOptions = $units->map(function ($unit) {
        $activeContracts = method_exists($unit, 'relationLoaded') && $unit->relationLoaded('contracts')
            ? $unit->contracts->where('status', 'active')
            : collect();
        $availableAfter = $activeContracts->max(fn ($contract) => $contract->end_date?->toDateString());
        $displayDate = $availableAfter
            ? (app()->getLocale() === 'ar' ? \Illuminate\Support\Carbon::parse($availableAfter)->format('d-m-Y') : $availableAfter)
            : null;

        return [
            'id' => (string) $unit->id,
            'building_id' => (string) $unit->building_id,
            'unit_number' => (string) $unit->unit_number,
            'vacant' => $unit->status === 'vacant' && $activeContracts->isEmpty(),
            'future' => $activeContracts->isNotEmpty(),
            'future_label' => $displayDate
                ? $unit->unit_number.' - '.__('contracts.form.available_after', ['date' => $displayDate])
                : (string) $unit->unit_number,
        ];
    })->values();
    $initialUnitOptions = $selectedBuildingId === ''
        ? collect()
        : $contractUnitOptions->filter(fn ($unit) => $unit['building_id'] === $selectedBuildingId && ($contractMode === 'future' ? $unit['future'] : $unit['vacant']));
@endphp
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ $isRenewal ? __('contracts.form.prepare_renewal_title') : ($isEdit ? __('contracts.form.edit_title') : __('contracts.form.add_title')) }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ $isRenewal ? __('contracts.form.renewal_description') : __('contracts.form.description').' '.($isEdit ? __('contracts.form.number_read_only') : __('contracts.form.number_generated')) }}</p>
</div>
<form data-contract-form method="post" action="{{ $isEdit ? route('contracts.update', $contract) : route('contracts.store') }}" class="max-w-4xl space-y-4">
@csrf @if($isEdit) @method('put') @endif
@if($isRenewal)<input type="hidden" name="renew_from" value="{{ $renewalSource->id }}">@endif
<section data-contract-step="unit" class="rounded border bg-white p-4 shadow-sm">
    <div class="mb-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contracts.form.step_1') }}</p>
        <h2 class="text-base font-semibold">{{ __('contracts.form.step_unit_title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.form.step_unit_body') }}</p>
    </div>
    @if($isEdit || $isRenewal)
        <div class="block text-sm font-medium">{{ __('contracts.form.unit') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ $displayContract->unit?->building?->name }} / {{ $displayContract->unit?->unit_number }}</div><span class="mt-1 block text-xs text-slate-500">{{ $isRenewal ? __('contracts.form.unit_fixed_renewal') : __('contracts.form.unit_locked') }}</span></div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <fieldset class="rounded border p-3 md:col-span-2">
                <legend class="px-1 text-sm font-medium">{{ __('contracts.form.contract_mode') }}</legend>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="contract_mode" value="vacant" @checked(($contractMode ?? 'vacant') !== 'future')> <span>{{ __('contracts.form.mode_vacant') }}</span></label>
                    <label class="flex items-start gap-2 text-sm"><input type="radio" name="contract_mode" value="future" @checked(($contractMode ?? 'vacant') === 'future')> <span>{{ __('contracts.form.mode_future') }}</span></label>
                </div>
            </fieldset>
            <label class="block text-sm font-medium">
                {{ __('contracts.form.building') }}
                <select name="building_id" data-contract-building-select class="form-select-safe tap-target mt-1 w-full rounded border p-2">
                    <option value="">{{ __('contracts.form.select_building') }}</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" @selected($selectedBuildingId === (string) $building->id)>{{ $building->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">
                {{ __('contracts.form.unit') }}
                <select name="unit_id" data-contract-unit-select data-selected-unit="{{ $selectedUnitId }}" class="form-select-safe tap-target mt-1 w-full rounded border p-2" required>
                    <option value="">{{ __('contracts.form.select_unit') }}</option>
                    @foreach($initialUnitOptions as $unitOption)
                        <option value="{{ $unitOption['id'] }}" @selected($selectedUnitId === $unitOption['id'])>{{ ($contractMode ?? 'vacant') === 'future' ? $unitOption['future_label'] : $unitOption['unit_number'] }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.unit_hint') }}</span>
            </label>
            <p data-contract-unit-empty class="{{ $selectedBuildingId !== '' && $initialUnitOptions->isNotEmpty() ? 'hidden ' : '' }}rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 md:col-span-2">
                @if($selectedBuildingId === '')
                    {{ __('contracts.form.select_building_first') }}
                @elseif(($contractMode ?? 'vacant') === 'future')
                    {{ __('contracts.form.no_future_units') }}
                @else
                    {{ __('contracts.form.no_vacant_units') }}
                @endif
            </p>
        </div>
    @endif
</section>

<section data-contract-step="tenant" class="rounded border bg-white p-4 shadow-sm">
    <div class="mb-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contracts.form.step_2') }}</p>
        <h2 class="text-base font-semibold">{{ __('contracts.form.step_tenant_title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.form.step_tenant_body') }}</p>
    </div>
    @if(! $isEdit && ! $isRenewal)
    <fieldset class="rounded border p-3">
        <legend class="px-1 text-sm font-medium">{{ __('contracts.form.tenant') }}</legend>
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            <label class="flex items-center gap-2 text-sm"><input type="radio" name="tenant_mode" value="existing" @checked($tenantMode !== 'new')> {{ __('contracts.form.select_existing_tenant') }}</label>
            <label class="flex items-center gap-2 text-sm"><input type="radio" name="tenant_mode" value="new" @checked($tenantMode === 'new')> {{ __('contracts.form.add_new_tenant') }}</label>
        </div>
    </fieldset>
    @endif
    @if($isEdit || $isRenewal)
        <div class="mt-3 block text-sm font-medium">{{ __('contracts.form.tenant') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ $displayContract->tenant?->full_name }}</div><span class="mt-1 block text-xs text-slate-500">{{ $isRenewal ? __('contracts.form.tenant_fixed_renewal') : __('contracts.form.tenant_locked') }}</span></div>
    @else
        <label id="existing-tenant-fields" class="mt-3 block text-sm font-medium {{ $tenantMode === 'new' ? 'hidden' : '' }}">
            {{ __('contracts.form.tenant') }}
            <select name="tenant_id" class="form-select-safe tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode === 'new')>
                <option value="">{{ __('contracts.form.select_tenant') }}</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" @selected($selectedTenantId === (string) $tenant->id)>{{ $tenant->full_name }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.select_existing_tenant_hint') }}</span>
        </label>
        <div id="new-tenant-fields" class="mt-3 grid gap-4 rounded border border-dashed p-3 md:grid-cols-2 {{ $tenantMode === 'new' ? '' : 'hidden' }}">
            <p class="text-sm text-slate-600 md:col-span-2">{{ __('contracts.form.new_tenant_hint') }}</p>
            <label class="block text-sm font-medium">{{ __('contracts.form.full_name') }} <input name="new_tenant[full_name]" value="{{ old('new_tenant.full_name') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
            <label class="block text-sm font-medium">{{ __('contracts.form.phone') }} <input name="new_tenant[phone]" value="{{ old('new_tenant.phone') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
            <label class="block text-sm font-medium">{{ __('contracts.form.email') }} <input name="new_tenant[email]" type="email" value="{{ old('new_tenant.email') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
            <label class="block text-sm font-medium">{{ __('contracts.form.id_number') }} <input name="new_tenant[id_number]" value="{{ old('new_tenant.id_number') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
            <label class="block text-sm font-medium">{{ __('contracts.form.nationality') }} <input name="new_tenant[nationality]" value="{{ old('new_tenant.nationality') }}" class="tap-target mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')></label>
            <label class="block text-sm font-medium md:col-span-2">{{ __('contracts.form.tenant_notes') }} <textarea name="new_tenant[notes]" rows="3" class="mt-1 w-full rounded border p-2" @disabled($tenantMode !== 'new')>{{ old('new_tenant.notes') }}</textarea></label>
        </div>
    @endif
</section>

<section data-contract-step="details" class="rounded border bg-white p-4 shadow-sm">
    <div class="mb-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contracts.form.step_3') }}</p>
        <h2 class="text-base font-semibold">{{ __('contracts.form.step_details_title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.form.step_details_body') }}</p>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        @if($isEdit)<label class="block text-sm font-medium">{{ __('contracts.form.contract_number') }} <input value="{{ $contract->contract_number }}" class="tap-target mt-1 w-full rounded border bg-slate-50 p-2" readonly><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.contract_number_locked') }}</span></label>@endif
        @if($isRenewal)<div class="block text-sm font-medium">{{ __('contracts.form.status') }} <div class="tap-target mt-1 w-full rounded border bg-slate-50 p-2">{{ __('contracts.statuses.active') }}</div><input type="hidden" name="status" value="active"></div>@else<label class="block text-sm font-medium">{{ __('contracts.form.status') }} <select name="status" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach(['active','expired'] as $status)<option value="{{ $status }}" @selected(old('status', $contract->status)==$status)>{{ __('contracts.statuses.'.$status) }}</option>@endforeach</select></label>@endif
        <label class="block text-sm font-medium">{{ __('contracts.form.start_date') }} <input name="start_date" type="date" value="{{ old('start_date', optional($contract->start_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.start_date_hint') }}</span></label>
        <label class="block text-sm font-medium">{{ __('contracts.form.end_date') }} <input name="end_date" type="date" value="{{ old('end_date', optional($contract->end_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2" required><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.end_date_hint') }}</span></label>
        <label class="block text-sm font-medium">{{ __('contracts.form.rent_amount') }} <input name="rent_amount" type="number" step="0.01" value="{{ old('rent_amount', $contract->rent_amount) }}" class="tap-target mt-1 w-full rounded border p-2"><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.rent_amount_hint') }}</span></label>
        <label class="block text-sm font-medium">{{ __('contracts.form.payment_frequency') }} <select name="payment_frequency" class="form-select-safe tap-target mt-1 w-full rounded border p-2">@foreach(['monthly','quarterly','semi_annual','annual'] as $frequency)<option value="{{ $frequency }}" @selected(old('payment_frequency', $contract->payment_frequency)==$frequency)>{{ __('contracts.frequencies.'.$frequency) }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.payment_frequency_hint') }}</span></label>
        <label class="block text-sm font-medium">{{ __('contracts.form.deposit_amount') }} <input name="deposit_amount" type="number" step="0.01" value="{{ old('deposit_amount', $contract->deposit_amount) }}" class="tap-target mt-1 w-full rounded border p-2"><span class="mt-1 block text-xs text-slate-500">{{ __('contracts.form.deposit_amount_hint') }}</span></label>
        <label class="block text-sm font-medium md:col-span-2">{{ __('contracts.form.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $contract->notes) }}</textarea></label>
    </div>
</section>

<section data-contract-step="review" class="rounded border border-slate-200 bg-slate-50 p-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contracts.form.step_4') }}</p>
    <h2 class="text-base font-semibold">{{ __('contracts.form.step_review_title') }}</h2>
    <p class="mt-1 text-sm text-slate-600">{{ __('contracts.form.step_review_body') }}</p>
    <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
        <div class="rounded border bg-white p-3"><span class="block text-slate-500">{{ __('contracts.form.rent_amount') }}</span><span class="font-medium">{{ __('contracts.form.schedule_uses_rent') }}</span></div>
        <div class="rounded border bg-white p-3"><span class="block text-slate-500">{{ __('contracts.form.payment_frequency') }}</span><span class="font-medium">{{ __('contracts.form.schedule_uses_frequency') }}</span></div>
        <div class="rounded border bg-white p-3"><span class="block text-slate-500">{{ __('contracts.form.after_save_title') }}</span><span class="font-medium">{{ __('contracts.form.after_save_body') }}</span></div>
    </div>
</section>

<button type="submit" class="tap-target w-full rounded bg-slate-900 px-4 text-white">{{ __('contracts.form.save') }}</button>
</form>
@if(! $isEdit && ! $isRenewal)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="tenant_mode"]');
    const existing = document.getElementById('existing-tenant-fields');
    const newFields = document.getElementById('new-tenant-fields');
    const contractModeRadios = document.querySelectorAll('input[name="contract_mode"]');
    const buildingSelect = document.querySelector('[data-contract-building-select]');
    const unitSelect = document.querySelector('[data-contract-unit-select]');
    const unitEmpty = document.querySelector('[data-contract-unit-empty]');
    const unitOptions = @json($contractUnitOptions);
    const unitMessages = {
        selectUnit: @json(__('contracts.form.select_unit')),
        selectBuildingFirst: @json(__('contracts.form.select_building_first')),
        noVacantUnits: @json(__('contracts.form.no_vacant_units')),
        noFutureUnits: @json(__('contracts.form.no_future_units')),
    };

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

    const syncUnits = () => {
        if (! buildingSelect || ! unitSelect || ! unitEmpty) return;

        const buildingId = buildingSelect.value;
        const mode = document.querySelector('input[name="contract_mode"]:checked')?.value || 'vacant';
        const selectedUnit = unitSelect.dataset.selectedUnit || '';

        unitSelect.innerHTML = '';
        const placeholder = new Option(unitMessages.selectUnit, '');
        unitSelect.appendChild(placeholder);

        if (! buildingId) {
            unitSelect.disabled = true;
            unitEmpty.textContent = unitMessages.selectBuildingFirst;
            unitEmpty.classList.remove('hidden');
            return;
        }

        const filteredUnits = unitOptions.filter((unit) => {
            if (unit.building_id !== buildingId) return false;

            return mode === 'future' ? unit.future : unit.vacant;
        });

        filteredUnits.forEach((unit) => {
            const option = new Option(mode === 'future' ? unit.future_label : unit.unit_number, unit.id);
            option.selected = unit.id === selectedUnit;
            unitSelect.appendChild(option);
        });

        unitSelect.disabled = filteredUnits.length === 0;
        unitEmpty.textContent = filteredUnits.length === 0
            ? (mode === 'future' ? unitMessages.noFutureUnits : unitMessages.noVacantUnits)
            : '';
        unitEmpty.classList.toggle('hidden', filteredUnits.length > 0);
    };

    buildingSelect?.addEventListener('change', () => {
        if (unitSelect) unitSelect.dataset.selectedUnit = '';
        syncUnits();
    });
    contractModeRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            if (unitSelect) unitSelect.dataset.selectedUnit = '';
            syncUnits();
        });
    });
    syncUnits();
});
</script>
@endif
@endsection
