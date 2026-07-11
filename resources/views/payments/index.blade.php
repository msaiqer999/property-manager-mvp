@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h1 class="text-xl font-semibold">{{ __('payments.title') }}</h1>
    <p class="mt-1 text-sm text-brand-muted">{{ __('payments.description') }}</p>
</div>

<form class="mb-4 grid gap-3 rounded border bg-brand-surface p-3 sm:grid-cols-[1fr_auto_auto]">
    <select name="status" class="form-select-safe tap-target min-h-11 rounded border p-2">
        <option value="">{{ __('payments.all_statuses') }}</option>
        @foreach(['pending','paid','partial','overdue','cancelled'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('payments.statuses.'.$status) }}</option>
        @endforeach
    </select>
    <button class="tap-target min-h-11 rounded bg-brand-primary px-4 text-white">{{ __('payments.filter') }}</button>
    <a class="tap-target flex min-h-11 items-center justify-center rounded border bg-brand-surface px-4" href="{{ route('payments.index', ['overdue' => 1]) }}">{{ __('payments.overdue') }}</a>
</form>

@if($payments->isEmpty())
    <section data-empty-state-payments class="rounded border bg-brand-surface p-5 text-center shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold text-brand-text">{{ __('app.empty_states.payments.title') }}</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-brand-muted">{{ __('app.empty_states.payments.body') }}</p>
        @if(auth()->user()?->role?->can('manage-contracts'))
            <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('contracts.create') }}">{{ __('app.empty_states.payments.action') }}</a>
        @else
            <a class="tap-target mt-4 inline-flex min-h-11 items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('dashboard') }}">{{ __('app.dashboard.title') }}</a>
        @endif
    </section>
@else
<div data-mobile-payments-list class="grid gap-3 md:hidden">
    @foreach($payments as $payment)
        @php
            $isOverduePayment = in_array($payment->display_status_key, ['overdue', 'partial_overdue'], true);
        @endphp
        <article data-payment-mobile-card @class([
            'rounded border bg-brand-surface p-4 shadow-sm',
            'border-state-danger/25 bg-state-danger-soft' => $isOverduePayment,
        ])>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="break-words text-base font-semibold">{{ $payment->contract?->tenant?->full_name ?? __('payments.not_available') }}</h2>
                    <p class="mt-1 text-sm text-brand-muted">{{ __('payments.form.building') }}: {{ $payment->contract?->unit?->building?->name ?? __('payments.not_available') }}</p>
                    <p class="mt-1 text-sm text-brand-muted">{{ __('payments.columns.unit') }}: <bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('payments.not_available') }}</bdi></p>
                </div>
                <x-status-badge class="shrink-0" :status="$payment->display_status_key" :label="__('payments.statuses.'.$payment->display_status_key)" />
            </div>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-brand-muted">{{ __('payments.columns.due_date') }}</dt>
                    <dd><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-brand-muted">{{ __('payments.columns.amount') }}</dt>
                    <dd><bdi dir="ltr">{{ number_format($payment->amount_paid, 2) }} / {{ number_format($payment->amount_due, 2) }}</bdi></dd>
                </div>
                @if($isOverduePayment)
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-brand-muted">{{ __('payments.show.remaining') }}</dt>
                        <dd class="font-medium text-state-danger"><bdi dir="ltr">{{ number_format($payment->remaining_amount, 2) }}</bdi></dd>
                    </div>
                @endif
                @if($payment->latestPromise?->promised_date)
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-brand-muted">{{ __('payments.follow_ups.promise_indicator') }}</dt>
                        <dd class="font-medium text-brand-text"><bdi dir="ltr">{{ $payment->latestPromise->promised_date->toDateString() }}</bdi></dd>
                    </div>
                @endif
            </dl>
            <div class="mt-4 grid gap-2">
                @if($isOverduePayment)
                    <a data-payment-action class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded border border-state-danger/25 bg-brand-surface px-4 text-center text-sm font-medium text-state-danger" href="{{ route('payments.show', $payment) }}">{{ __('payments.follow_up') }}</a>
                @endif

                @if($payment->amount_paid_minor > 0)
                    <a data-payment-action class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium text-brand-text" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>
                @endif

                @if($payment->status === 'cancelled')
                    <span class="text-sm text-brand-muted">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                @elseif($payment->amount_paid_minor < $payment->amount_due_minor)
                    @can('recordPayment', $payment)
                    <a data-payment-action class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded bg-brand-primary px-4 text-center text-sm font-medium text-white" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>
                    @endcan
                @endif
            </div>
        </article>
    @endforeach
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-full">
        <thead>
            <tr>
                <th class="w-[15%] p-3 text-start text-xs font-semibold uppercase tracking-wide text-brand-muted">{{ __('payments.columns.due_date') }} / {{ __('payments.columns.paid_date') }}</th>
                <th class="w-[26%] p-3 text-start text-xs font-semibold uppercase tracking-wide text-brand-muted">{{ __('payments.columns.tenant') }} / {{ __('payments.columns.unit') }}</th>
                <th class="w-[16%] p-3 text-start text-xs font-semibold uppercase tracking-wide text-brand-muted">{{ __('payments.columns.contract') }} / {{ __('payments.columns.status') }}</th>
                <th class="w-[20%] p-3 text-end text-xs font-semibold uppercase tracking-wide text-brand-muted">{{ __('payments.columns.amount') }} / {{ __('payments.show.remaining') }}</th>
                <th class="w-[23%] p-3 text-center text-xs font-semibold uppercase tracking-wide text-brand-muted">{{ __('payments.columns.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                @php
                    $isOverduePayment = in_array($payment->display_status_key, ['overdue', 'partial_overdue'], true);
                @endphp
                <tr @class([
                    'border-t',
                    'bg-state-danger-soft' => $isOverduePayment,
                ])>
                    <td class="p-3 align-top">
                        <bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi>
                        <span class="mt-1 block text-xs text-brand-muted">{{ __('payments.columns.paid_date') }}: <bdi dir="ltr">{{ $payment->payment_date?->toDateString() ?? __('payments.not_available') }}</bdi></span>
                    </td>
                    <td class="p-3 align-top font-medium">
                        <span class="block break-words">{{ $payment->contract?->tenant?->full_name ?? __('payments.not_available') }}</span>
                        <span class="mt-1 block break-words text-xs font-normal text-brand-muted">{{ $payment->contract?->unit?->building?->name ?? __('payments.not_available') }}</span>
                        <span class="mt-1 block text-xs font-normal text-brand-muted">{{ __('payments.columns.unit') }}: <bdi dir="ltr">{{ $payment->contract?->unit?->unit_number ?? __('payments.not_available') }}</bdi></span>
                    </td>
                    <td class="p-3 align-top">
                        <bdi dir="ltr">{{ $payment->contract?->contract_number ?? __('payments.not_available') }}</bdi>
                        <span class="mt-2 block text-xs text-brand-muted">{{ __('payments.columns.status') }}</span>
                        <x-status-badge class="mt-1" :status="$payment->display_status_key" :label="__('payments.statuses.'.$payment->display_status_key)" />
                        @if($payment->status === 'cancelled')
                            <span class="mt-1 block text-xs text-brand-muted">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                        @endif
                    </td>
                    <td class="p-3 text-end align-top">
                        <bdi dir="ltr">{{ number_format($payment->amount_paid, 2) }} / {{ number_format($payment->amount_due, 2) }}</bdi>
                        @if($isOverduePayment)
                            <span class="mt-1 block text-xs text-state-danger">{{ __('payments.show.remaining') }}: <bdi dir="ltr">{{ number_format($payment->remaining_amount, 2) }}</bdi></span>
                        @endif
                        @if($payment->latestPromise?->promised_date)
                            <span class="mt-1 block text-xs text-brand-muted">{{ __('payments.follow_ups.promise_indicator') }}: <bdi dir="ltr">{{ $payment->latestPromise->promised_date->toDateString() }}</bdi></span>
                        @endif
                    </td>
                    <td class="p-3 align-top">
                        <div class="flex flex-wrap justify-center gap-2">
                            @if($isOverduePayment)
                                <a data-payment-action class="tap-target inline-flex min-h-10 items-center rounded border border-state-danger/25 px-3 text-sm text-state-danger" href="{{ route('payments.show', $payment) }}">{{ __('payments.follow_up') }}</a>
                            @endif
                            @if($payment->amount_paid_minor > 0)
                                <a data-payment-action class="tap-target inline-flex min-h-10 items-center rounded border px-3 text-sm text-brand-text" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>
                            @endif
                            @if($payment->status === 'cancelled')
                                <span class="text-sm text-brand-muted">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                            @elseif($payment->amount_paid_minor < $payment->amount_due_minor)
                                @can('recordPayment', $payment)
                                <a data-payment-action class="tap-target inline-flex min-h-10 items-center rounded border px-3 text-sm text-brand-text" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>

{{ $payments->links() }}
@endif
@endsection
