@extends('layouts.app')

@section('content')
<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-xl font-semibold">{{ __('units.fields.unit') }} <bdi dir="ltr">{{ $unit->unit_number }}</bdi></h1>
    <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('units.edit', $unit) }}">{{ __('app.actions.edit') }}</a>
</div>

<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2">
    <p><span class="font-medium">{{ __('units.labels.building') }}</span> {{ $unit->building->name }}</p>
    <p><span class="font-medium">{{ __('units.labels.status') }}</span> {{ __('units.statuses.'.$unit->status) }}</p>
    <p><span class="font-medium">{{ __('units.labels.type') }}</span> {{ __('units.types.'.$unit->type) }}</p>
    <p><span class="font-medium">{{ __('units.labels.rent') }}</span> <bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.size') }}:</span> <bdi dir="ltr">{{ $unit->size !== null ? number_format((float) $unit->size, 2) : __('payments.not_available') }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.rooms') }}:</span> <bdi dir="ltr">{{ $unit->rooms ?? __('payments.not_available') }}</bdi></p>
    <p class="sm:col-span-2"><span class="font-medium">{{ __('units.fields.notes') }}:</span> {{ $unit->notes ?: __('payments.not_available') }}</p>
</div>
@endsection
