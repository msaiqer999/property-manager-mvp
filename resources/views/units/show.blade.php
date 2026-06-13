@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">Unit {{ $unit->unit_number }}</h1><a class="rounded border px-3 py-2 text-sm" href="{{ route('units.edit', $unit) }}">Edit</a></div>
<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2"><p>Building: {{ $unit->building->name }}</p><p>Status: {{ $unit->status }}</p><p>Type: {{ $unit->type }}</p><p>Rent: {{ number_format($unit->rent_amount, 2) }}</p></div>
@endsection
