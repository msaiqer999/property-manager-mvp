@extends('layouts.app')
@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">Buildings</h1><a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('buildings.create') }}">Add building</a></div>
<x-table min-width="min-w-[36rem]">
<thead><tr class="text-left"><th class="p-4">Name</th><th class="p-4">Location</th><th class="p-4"></th></tr></thead>
<tbody>@foreach($buildings as $building)<tr class="border-t"><td class="p-4 font-medium">{{ $building->name }}</td><td class="p-4">{{ $building->location }}</td><td class="p-4"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('buildings.show', $building) }}">View</a></td></tr>@endforeach</tbody>
</x-table>
{{ $buildings->links() }}
@endsection
