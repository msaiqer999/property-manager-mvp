@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">Payment</h1><a class="rounded border px-3 py-2 text-sm" href="{{ route('payments.edit', $payment) }}">Record</a></div>
<div class="rounded border bg-white p-4"><p>Due: {{ $payment->due_date->toDateString() }}</p><p>Amount due: {{ number_format($payment->amount_due, 2) }}</p><p>Paid: {{ number_format($payment->amount_paid, 2) }}</p><p>Status: {{ $payment->status }}</p></div>
@endsection
