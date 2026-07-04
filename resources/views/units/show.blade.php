@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('units.fields.unit') }} <bdi dir="ltr">{{ $unit->unit_number }}</bdi></h1>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @if(auth()->user()?->role?->can('view-reports'))
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('reports.index', ['unit_id' => $unit->id]) }}">{{ __('reports.statement.view_statement') }}</a>
        @endif
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('units.edit', $unit) }}">{{ __('app.actions.edit') }}</a>
    </div>
</div>

<div data-unit-show-card class="grid gap-3 rounded border bg-white p-4 shadow-sm sm:grid-cols-2">
    <p><span class="font-medium">{{ __('units.labels.building') }}</span> {{ $unit->building->name }}</p>
    <p><span class="font-medium">{{ __('units.labels.status') }}</span> {{ __('units.statuses.'.$unit->status) }}</p>
    <p><span class="font-medium">{{ __('units.labels.type') }}</span> {{ __('units.types.'.$unit->type) }}</p>
    <p><span class="font-medium">{{ __('units.labels.rent') }}</span> <bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.size') }}:</span> <bdi dir="ltr">{{ $unit->size !== null ? number_format((float) $unit->size, 2) : __('payments.not_available') }}</bdi></p>
    <p><span class="font-medium">{{ __('units.fields.rooms') }}:</span> <bdi dir="ltr">{{ $unit->rooms ?? __('payments.not_available') }}</bdi></p>
    <p class="sm:col-span-2"><span class="font-medium">{{ __('units.fields.notes') }}:</span> {{ $unit->notes ?: __('payments.not_available') }}</p>
</div>
@endsection
