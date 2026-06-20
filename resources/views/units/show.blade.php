@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3"><h1 class="text-xl font-semibold">{{ __('units.fields.unit') }} <bdi dir="ltr">{{ $unit->unit_number }}</bdi></h1><a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('units.edit', $unit) }}">{{ __('app.actions.edit') }}</a></div>
<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2"><p>{{ __('units.labels.building') }} {{ $unit->building->name }}</p><p>{{ __('units.labels.status') }} {{ __('units.statuses.'.$unit->status) }}</p><p>{{ __('units.labels.type') }} {{ __('units.types.'.$unit->type) }}</p><p>{{ __('units.labels.rent') }} <bdi dir="ltr">{{ number_format($unit->rent_amount, 2) }}</bdi></p></div>
@endsection
