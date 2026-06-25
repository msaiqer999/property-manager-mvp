@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('payments.payment') }}</h1>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @if($payment->proof_image)
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('payments.proof.download', $payment) }}">{{ __('payments.download_proof') }}</a>
        @endif
        @if($payment->amount_paid > 0)
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.receipt', $payment) }}">{{ __('payments.download_receipt_pdf') }}</a>
        @elseif($payment->status !== 'cancelled')
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>
        @endif
    </div>
</div>

<div class="rounded border bg-white p-4 shadow-sm">
    <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-slate-500">{{ __('payments.show.due') }}</dt>
            <dd class="mt-1 font-medium"><span dir="ltr">{{ $payment->due_date->toDateString() }}</span></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.amount_due') }}</dt>
            <dd class="mt-1 font-medium"><span dir="ltr">{{ number_format($payment->amount_due, 2) }}</span></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.paid') }}</dt>
            <dd class="mt-1 font-medium"><span dir="ltr">{{ number_format($payment->amount_paid, 2) }}</span></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.status') }}</dt>
            <dd class="mt-1"><span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('payments.statuses.'.$payment->status) }}</span></dd>
        </div>
    </dl>
    @if($payment->status === 'cancelled')
        <p class="mt-3 text-sm text-slate-600">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</p>
    @endif
</div>
@endsection
