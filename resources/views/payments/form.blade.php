@extends('layouts.app')

@section('content')
@php
    $defaultAmountPaid = $payment->amount_paid_minor > 0 ? $payment->amount_paid : $payment->amount_due;
    $defaultPaymentDate = $payment->payment_date?->toDateString() ?? now()->toDateString();
@endphp
<h1 class="mb-4 text-xl font-semibold">{{ __('payments.record_payment') }}</h1>

<div data-payment-summary class="mb-4 rounded border bg-white p-4 shadow-sm">
    <h2 class="text-base font-semibold">{{ __('payments.form.summary') }}</h2>
    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="text-slate-500">{{ __('payments.columns.tenant') }}</dt>
            <dd class="mt-1 font-medium">{{ $payment->contract?->tenant?->full_name ?? __('payments.not_available') }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.form.building') }}</dt>
            <dd class="mt-1 font-medium">{{ $payment->contract?->unit?->building?->name ?? __('payments.not_available') }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.columns.unit') }}</dt>
            <dd class="mt-1 font-medium"><bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('payments.not_available') }}</bdi></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.columns.contract') }}</dt>
            <dd class="mt-1 font-medium"><bdi dir="ltr">{{ $payment->contract?->contract_number ?? __('payments.not_available') }}</bdi></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.columns.due_date') }}</dt>
            <dd class="mt-1 font-medium"><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.amount_due') }}</dt>
            <dd class="mt-1 font-medium"><bdi dir="ltr">{{ number_format($payment->amount_due, 2) }}</bdi></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.status') }}</dt>
            <dd class="mt-1"><span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('payments.statuses.'.$payment->display_status_key) }}</span></dd>
        </div>
    </dl>
</div>

<form data-payment-record-form method="post" enctype="multipart/form-data" action="{{ route('payments.update', $payment) }}" class="max-w-xl space-y-4 rounded border bg-white p-4 shadow-sm">
    @csrf @method('put')
    <p class="text-sm text-slate-600">{{ __('payments.form.due') }} <span dir="ltr">{{ $payment->due_date->toDateString() }}</span> / <span dir="ltr">{{ number_format($payment->amount_due, 2) }}</span></p>
    <label class="block text-sm font-medium">{{ __('payments.form.amount_paid') }} <input name="amount_paid" type="number" step="0.01" value="{{ old('amount_paid', $defaultAmountPaid) }}" class="tap-target mt-1 min-h-11 w-full rounded border p-3" required></label>
    <label class="block text-sm font-medium">{{ __('payments.form.payment_date') }} <input name="payment_date" type="date" value="{{ old('payment_date', $defaultPaymentDate) }}" class="tap-target mt-1 min-h-11 w-full rounded border p-3" required></label>
    <label class="block text-sm font-medium">{{ __('payments.form.method') }} <select name="payment_method" class="form-select-safe tap-target mt-1 min-h-11 w-full rounded border p-3"><option value=""></option>@foreach(['cash','bank_transfer','cheque','other'] as $method)<option value="{{ $method }}" @selected(old('payment_method', $payment->payment_method)==$method)>{{ __('payments.methods.'.$method) }}</option>@endforeach</select></label>
    <label class="block text-sm font-medium">{{ __('payments.form.proof_image') }} <input name="proof_image" type="file" accept="image/*" class="tap-target mt-1 min-h-11 w-full rounded border p-3"></label>
    <label class="block text-sm font-medium">{{ __('payments.form.notes') }} <textarea name="notes" rows="4" class="mt-1 w-full rounded border p-3">{{ old('notes', $payment->notes) }}</textarea></label>
    <button data-payment-action class="tap-target min-h-11 w-full rounded bg-slate-900 px-4 text-white sm:w-auto">{{ __('payments.form.save') }}</button>
</form>
@endsection
