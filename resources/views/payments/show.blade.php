@extends('layouts.app')

@section('content')
@php
    $tenant = $payment->contract?->tenant;
    $unit = $payment->contract?->unit;
    $building = $unit?->building;
    $isOverduePayment = in_array($payment->display_status_key, ['overdue', 'partial_overdue'], true);
    $daysOverdue = $isOverduePayment ? max(0, $payment->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false)) : 0;
    $reminderMessage = __('payments.reminder.message', [
        'tenant_name' => $tenant?->full_name ?? __('payments.not_available'),
        'unit_number' => $unit?->unit_number ?? __('payments.not_available'),
        'due_date' => $payment->due_date->toDateString(),
        'remaining_amount' => number_format($payment->remaining_amount, 2),
    ]);
@endphp

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold">{{ __('payments.payment') }}</h1>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @if($payment->proof_image)
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('payments.proof.download', $payment) }}">{{ __('payments.download_proof') }}</a>
        @endif
        @if($payment->amount_paid > 0)
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.receipt', $payment) }}">{{ __('payments.download_receipt_pdf') }}</a>
        @endif
        @if($payment->status !== 'cancelled' && $payment->amount_paid_minor < $payment->amount_due_minor)
            @can('recordPayment', $payment)
            <a data-payment-action class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.edit', $payment) }}">{{ __('payments.record_payment') }}</a>
            @endcan
        @endif
    </div>
</div>

@if($isOverduePayment)
    <section data-overdue-payment-summary class="mb-4 rounded border border-rose-200 bg-rose-50 p-4 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">{{ __('payments.follow_up') }}</p>
                <h2 class="mt-1 text-lg font-semibold text-rose-950">{{ __('payments.overdue_summary.title') }}</h2>
                <p class="mt-1 text-sm text-rose-800">{{ __('payments.overdue_summary.description') }}</p>
            </div>
            <span class="inline-flex w-fit rounded bg-white px-3 py-1 text-sm font-medium text-rose-700">
                {{ trans_choice('payments.overdue_summary.days_overdue', $daysOverdue, ['count' => $daysOverdue]) }}
            </span>
        </div>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-rose-700">{{ __('payments.columns.tenant') }}</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $tenant?->full_name ?? __('payments.not_available') }}</dd>
            </div>
            @if($tenant?->phone)
                <div>
                    <dt class="text-rose-700">{{ __('payments.overdue_summary.phone') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ $tenant->phone }}</bdi></dd>
                </div>
            @endif
            <div>
                <dt class="text-rose-700">{{ __('payments.form.building') }}</dt>
                <dd class="mt-1 font-medium text-slate-900">{{ $building?->name ?? __('payments.not_available') }}</dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.columns.unit') }}</dt>
                <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ $unit?->unit_number ?? __('payments.not_available') }}</bdi></dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.columns.contract') }}</dt>
                <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ $payment->contract?->contract_number ?? __('payments.not_available') }}</bdi></dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.show.due') }}</dt>
                <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.show.amount_due') }}</dt>
                <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ number_format($payment->amount_due, 2) }}</bdi></dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.show.paid') }}</dt>
                <dd class="mt-1 font-medium text-slate-900"><bdi dir="ltr">{{ number_format($payment->amount_paid, 2) }}</bdi></dd>
            </div>
            <div>
                <dt class="text-rose-700">{{ __('payments.show.remaining') }}</dt>
                <dd class="mt-1 font-semibold text-rose-700"><bdi dir="ltr">{{ number_format($payment->remaining_amount, 2) }}</bdi></dd>
            </div>
        </dl>
    </section>

    <section data-payment-reminder class="mb-4 rounded border bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold">{{ __('payments.reminder.title') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('payments.reminder.description') }}</p>
            </div>
            <button data-copy-reminder type="button" class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-sm font-medium text-slate-700">{{ __('payments.reminder.copy') }}</button>
        </div>
        <textarea data-reminder-message readonly rows="4" class="mt-3 w-full rounded border bg-slate-50 p-3 text-sm leading-6 text-slate-800">{{ $reminderMessage }}</textarea>
        @can('recordPayment', $payment)
            <form method="post" action="{{ route('payment-follow-ups.store', $payment) }}" class="mt-3">
                @csrf
                <input type="hidden" name="type" value="reminder_logged">
                <input type="hidden" name="note" value="{{ $reminderMessage }}">
                <button type="submit" class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 sm:w-auto">{{ __('payments.follow_ups.log_reminder') }}</button>
            </form>
        @endcan
    </section>
@endif

@if($isOverduePayment || $payment->followUps->isNotEmpty())
    <section data-payment-follow-ups class="mb-4 rounded border bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-base font-semibold">{{ __('payments.follow_ups.title') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ __('payments.follow_ups.description') }}</p>
            </div>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($payment->followUps->sortByDesc('created_at') as $followUp)
                <article data-payment-follow-up-entry class="rounded border border-slate-200 bg-slate-50 p-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-medium">{{ __('payments.follow_ups.types.'.$followUp->type) }}</p>
                            @if($followUp->note)
                                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $followUp->note }}</p>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500"><bdi dir="ltr">{{ $followUp->created_at->format('Y-m-d H:i') }}</bdi></p>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                        @if($followUp->promised_date)
                            <div>
                                <dt class="text-slate-500">{{ __('payments.follow_ups.promised_date') }}</dt>
                                <dd><bdi dir="ltr">{{ $followUp->promised_date->toDateString() }}</bdi></dd>
                            </div>
                        @endif
                        @if($followUp->promised_amount !== null)
                            <div>
                                <dt class="text-slate-500">{{ __('payments.follow_ups.promised_amount') }}</dt>
                                <dd><bdi dir="ltr">{{ number_format((float) $followUp->promised_amount, 2) }}</bdi></dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-slate-500">{{ __('payments.follow_ups.created_by') }}</dt>
                            <dd>{{ $followUp->user?->name ?? __('payments.not_available') }}</dd>
                        </div>
                    </dl>
                </article>
            @empty
                <p class="rounded border border-dashed p-3 text-sm text-slate-500">{{ __('payments.follow_ups.empty') }}</p>
            @endforelse
        </div>

        @can('recordPayment', $payment)
            <form method="post" action="{{ route('payment-follow-ups.store', $payment) }}" class="mt-4 grid gap-3 rounded border border-slate-200 bg-slate-50 p-3">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="block text-sm font-medium">
                        {{ __('payments.follow_ups.type') }}
                        <select name="type" class="form-select-safe tap-target mt-1 w-full rounded border bg-white p-2">
                            @foreach(['note', 'reminder_logged', 'promise_to_pay'] as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ __('payments.follow_ups.types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">
                        {{ __('payments.follow_ups.promised_date') }}
                        <input name="promised_date" type="date" value="{{ old('promised_date') }}" class="tap-target mt-1 w-full rounded border bg-white p-2">
                    </label>
                    <label class="block text-sm font-medium">
                        {{ __('payments.follow_ups.promised_amount') }}
                        <input name="promised_amount" type="number" step="0.01" min="0" value="{{ old('promised_amount') }}" class="tap-target mt-1 w-full rounded border bg-white p-2" dir="ltr">
                    </label>
                </div>
                <label class="block text-sm font-medium">
                    {{ __('payments.follow_ups.note') }}
                    <textarea name="note" rows="3" class="mt-1 w-full rounded border bg-white p-3 text-sm">{{ old('note') }}</textarea>
                </label>
                @if($errors->any())
                    <div class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="submit" class="tap-target inline-flex min-h-11 w-full items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white sm:w-auto">{{ __('payments.follow_ups.save') }}</button>
            </form>
        @endcan
    </section>
@endif

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
            <dt class="text-slate-500">{{ __('payments.show.remaining') }}</dt>
            <dd class="mt-1 font-medium"><span dir="ltr">{{ number_format($payment->remaining_amount, 2) }}</span></dd>
        </div>
        <div>
            <dt class="text-slate-500">{{ __('payments.show.status') }}</dt>
            <dd class="mt-1"><span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ __('payments.statuses.'.$payment->display_status_key) }}</span></dd>
        </div>
    </dl>
    @if($payment->status === 'cancelled')
        <p class="mt-3 text-sm text-slate-600">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</p>
    @endif
</div>

@if($isOverduePayment)
    <script>
        (() => {
            const button = document.querySelector('[data-copy-reminder]');
            const message = document.querySelector('[data-reminder-message]');
            if (! button || ! message) return;

            button.addEventListener('click', async () => {
                message.focus();
                message.select();

                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(message.value);
                    } else {
                        document.execCommand('copy');
                    }
                } catch (error) {
                    document.execCommand('copy');
                }
            });
        })();
    </script>
@endif
@endsection
