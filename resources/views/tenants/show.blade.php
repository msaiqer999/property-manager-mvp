@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">{{ $tenant->full_name }}</h1><a class="rounded border px-3 py-2 text-sm" href="{{ route('tenants.edit', $tenant) }}">Edit</a></div>
<div class="rounded border bg-white p-4 text-sm"><p>{{ $tenant->phone }}</p><p>{{ $tenant->email }}</p><p>{{ $tenant->id_number }}</p></div>
<h2 class="mb-2 mt-6 font-semibold">Contracts</h2>
<x-table><tbody>@foreach($tenant->contracts as $contract)<tr class="border-t"><td class="p-3">{{ $contract->contract_number }}</td><td class="p-3">{{ $contract->status }}</td><td class="p-3"><a class="text-blue-700" href="{{ route('contracts.show', $contract) }}">View</a></td></tr>@endforeach</tbody></x-table>
@endsection
