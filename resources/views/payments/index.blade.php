@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">Payments</h1>
<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-[1fr_auto_auto]"><select name="status" class="tap-target rounded border p-2"><option value="">All statuses</option>@foreach(['pending','paid','partial','overdue'] as $status)<option @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select><button class="tap-target rounded bg-slate-900 px-4 text-white">Filter</button><a class="tap-target flex items-center justify-center rounded border bg-white px-4" href="{{ route('payments.index', ['overdue' => 1]) }}">Overdue</a></form>
<x-table><tbody>@foreach($payments as $payment)<tr class="border-t"><td class="p-4">{{ $payment->due_date->toDateString() }}</td><td class="p-4 font-medium">{{ $payment->contract->tenant->full_name }}</td><td class="p-4">{{ number_format($payment->amount_due, 2) }}</td><td class="p-4"><span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ $payment->status }}</span></td><td class="p-4"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('payments.edit', $payment) }}">Record</a></td></tr>@endforeach</tbody></x-table>
{{ $payments->links() }}
@endsection
