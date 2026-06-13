@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">Record payment</h1>
<form method="post" enctype="multipart/form-data" action="{{ route('payments.update', $payment) }}" class="max-w-xl space-y-4 rounded border bg-white p-4 shadow-sm">
@csrf @method('put')
<p class="text-sm text-slate-600">Due {{ $payment->due_date->toDateString() }} / {{ number_format($payment->amount_due, 2) }}</p>
<label class="block text-sm font-medium">Amount paid <input name="amount_paid" type="number" step="0.01" value="{{ old('amount_paid', $payment->amount_paid) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">Payment date <input name="payment_date" type="date" value="{{ old('payment_date', optional($payment->payment_date)->toDateString()) }}" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">Method <select name="payment_method" class="tap-target mt-1 w-full rounded border p-2"><option value=""></option>@foreach(['cash','bank_transfer','cheque','other'] as $method)<option @selected(old('payment_method', $payment->payment_method)==$method)>{{ $method }}</option>@endforeach</select></label>
<label class="block text-sm font-medium">Proof image <input name="proof_image" type="file" accept="image/*" class="tap-target mt-1 w-full rounded border p-2"></label>
<label class="block text-sm font-medium">Notes <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-2">{{ old('notes', $payment->notes) }}</textarea></label>
<button class="tap-target w-full rounded bg-slate-900 px-4 text-white sm:w-auto">Save</button>
</form>
@endsection
