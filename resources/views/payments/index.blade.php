@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ __('payments.title') }}</h1>
    <p class="mt-1 text-sm text-slate-600">{{ __('payments.description') }}</p>
</div>

<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-[1fr_auto_auto]">
    <select name="status" class="tap-target min-h-11 rounded border p-2">
        <option value="">{{ __('payments.all_statuses') }}</option>
        @foreach(['pending','paid','partial','overdue','cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('payments.statuses.'.$status) }}</option>
        @endforeach
    </select>
    <button class="tap-target min-h-11 rounded bg-slate-900 px-4 text-white">{{ __('payments.filter') }}</button>
    <a class="tap-target flex min-h-11 items-center justify-center rounded border bg-white px-4" href="{{ route('payments.index', ['overdue' => 1]) }}">{{ __('payments.overdue') }}</a>
</form>

<div data-mobile-payments-list class="grid gap-3 md:hidden">
    @foreach($payments as $payment)
        <article data-payment-mobile-card class="rounded border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="break-words text-base font-semibold">{{ $payment->contract?->tenant?->full_name ?? __('payments.not_available') }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ __('payments.columns.unit') }}: <bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('payments.not_available') }}</bdi></p>
                </div>
                <span class="shrink-0 rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('payments.statuses.'.$payment->display_status_key) }}</span>
            </div>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">{{ __('payments.columns.due_date') }}</dt>
                    <dd><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">{{ __('payments.columns.amount') }}</dt>
                    <dd><bdi dir="ltr">{{ number_format($payment->amount_paid, 2) }} / {{ number_format($payment->amount_due, 2) }}</bdi></dd>
                </div>
            </dl>
            <div class="mt-4 grid gap-2">
                @if($payment->amount_paid_minor > 0)
                    <a data-payment-action class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium text-slate-700" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>
                @endif

                @if($payment->status === 'cancelled')
                    <span class="text-sm text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                @elseif($payment->amount_paid_minor < $payment->amount_due_minor)
                    <a data-payment-action class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>
                @endif
            </div>
        </article>
    @endforeach
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-[68rem]">
        <thead>
            <tr>
                <th class="p-4 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.due_date') }}</th>
                <th class="p-4 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.tenant') }}</th>
                <th class="p-4 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.unit') }}</th>
                <th class="p-4 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.contract') }}</th>
                <th class="p-4 text-end text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.amount') }}</th>
                <th class="p-4 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.status') }}</th>
                <th class="p-4 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.paid_date') }}</th>
                <th class="p-4 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('payments.columns.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr class="border-t">
                    <td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></td>
                    <td class="p-4 font-medium"><span class="block max-w-48 truncate">{{ $payment->contract?->tenant?->full_name ?? __('payments.not_available') }}</span></td>
                    <td class="p-4 text-center whitespace-nowrap"><bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('payments.not_available') }}</bdi></td>
                    <td class="p-4 whitespace-nowrap"><bdi dir="ltr">{{ $payment->contract?->contract_number ?? __('payments.not_available') }}</bdi></td>
                    <td class="p-4 text-end whitespace-nowrap"><bdi dir="ltr">{{ number_format($payment->amount_paid, 2) }} / {{ number_format($payment->amount_due, 2) }}</bdi></td>
                    <td class="p-4 text-center whitespace-nowrap"><span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('payments.statuses.'.$payment->display_status_key) }}</span>@if($payment->status === 'cancelled')<span class="mt-1 block text-xs text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>@endif</td>
                    <td class="p-4 text-center whitespace-nowrap"><bdi dir="ltr">{{ $payment->payment_date?->toDateString() ?? __('payments.not_available') }}</bdi></td>
                    <td class="p-4 text-center whitespace-nowrap"><div class="flex justify-center gap-2">@if($payment->amount_paid_minor > 0)<a data-payment-action class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>@endif @if($payment->status === 'cancelled')<span class="text-sm text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>@elseif($payment->amount_paid_minor < $payment->amount_due_minor)<a data-payment-action class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>@endif</div></td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>

{{ $payments->links() }}
@endsection
