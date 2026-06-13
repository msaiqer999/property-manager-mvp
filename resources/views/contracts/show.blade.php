@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">{{ $contract->contract_number }}</h1><div class="flex gap-2"><a class="rounded border px-3 py-2 text-sm" href="{{ route('contracts.pdf', $contract) }}">PDF</a><a class="rounded border px-3 py-2 text-sm" href="{{ route('contracts.edit', $contract) }}">Edit</a></div></div>
<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2"><p>Tenant: {{ $contract->tenant->full_name }}</p><p>Unit: {{ $contract->unit->unit_number }}</p><p>Start: {{ $contract->start_date->toDateString() }}</p><p>End: {{ $contract->end_date->toDateString() }}</p><p>Rent: {{ number_format($contract->rent_amount, 2) }}</p><p>Status: {{ $contract->status }}</p></div>
<h2 class="mb-2 mt-6 font-semibold">Payment schedule</h2>
<x-table><tbody>@foreach($contract->payments as $payment)<tr class="border-t"><td class="p-3">{{ $payment->due_date->toDateString() }}</td><td class="p-3">{{ number_format($payment->amount_due, 2) }}</td><td class="p-3">{{ $payment->status }}</td><td class="p-3"><a class="text-blue-700" href="{{ route('payments.edit', $payment) }}">Record</a></td></tr>@endforeach</tbody></x-table>
@endsection
