@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">{{ $building->name }}</h1><a class="rounded border px-3 py-2 text-sm" href="{{ route('buildings.edit', $building) }}">Edit</a></div>
<div class="rounded border bg-white p-4"><p>{{ $building->location }}</p><p class="mt-2 text-sm text-slate-600">{{ $building->description }}</p></div>
<h2 class="mb-2 mt-6 font-semibold">Units</h2>
<x-table><tbody>@foreach($building->units as $unit)<tr class="border-t"><td class="p-3">{{ $unit->unit_number }}</td><td class="p-3">{{ $unit->status }}</td><td class="p-3">{{ number_format($unit->rent_amount, 2) }}</td></tr>@endforeach</tbody></x-table>
@endsection
