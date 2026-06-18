@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">Payment</h1><div class="flex flex-wrap gap-2">@if($payment->amount_paid > 0 || $payment->payment_date)<a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('payments.receipt', $payment) }}">Download receipt PDF</a>@else<a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('payments.edit', $payment) }}">Record payment</a>@endif</div></div>
<div class="rounded border bg-white p-4"><p>Due: {{ $payment->due_date->toDateString() }}</p><p>Amount due: {{ number_format($payment->amount_due, 2) }}</p><p>Paid: {{ number_format($payment->amount_paid, 2) }}</p><p>Status: {{ $payment->status }}</p></div>
@endsection
