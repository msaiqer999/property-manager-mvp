@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('contracts.contract', ['number' => $contract->contract_number]) }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.show_description') }}</p>
    </div>
    <div class="grid gap-2 sm:flex sm:flex-wrap">
        @can('create', \App\Models\Contract::class)
            @if($contract->isRenewalEligible())
                <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('contracts.create', ['renew_from' => $contract->id]) }}">{{ __('contracts.show.prepare_renewal') }}</a>
            @endif
        @endcan
        <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.pdf', $contract) }}">{{ __('contracts.show.download_pdf') }}</a>
        @if($contract->status !== 'terminated')
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.edit', $contract) }}">{{ __('contracts.show.edit') }}</a>
        @endif
    </div>
</div>

@if(session('status') && auth()->user()?->role?->can('view-payments'))
    <div data-contract-created-guidance class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        <div class="grid gap-3 sm:flex sm:items-center sm:justify-between">
            <p>{{ __('contracts.show.created_guidance') }}</p>
            <a class="tap-target inline-flex min-h-11 items-center justify-center rounded bg-emerald-800 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.index') }}">{{ __('contracts.show.view_payments') }}</a>
        </div>
    </div>
@endif

@if($contract->daysUntilExpiry() !== null && $contract->daysUntilExpiry() <= 90)
    <div class="mb-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
        <p class="font-medium">{{ __('contracts.show.expiry_warning') }}</p>
        <p>
            @if($contract->daysUntilExpiry() === 0)
                {{ __('contracts.show.expires_today_on', ['date' => $contract->end_date->toDateString()]) }}
            @elseif($contract->daysUntilExpiry() === 1)
                {{ __('contracts.show.expires_in_one_day_on', ['date' => $contract->end_date->toDateString()]) }}
            @else
                {{ __('contracts.show.expires_in_days_on', ['count' => $contract->daysUntilExpiry(), 'date' => $contract->end_date->toDateString()]) }}
            @endif
        </p>
    </div>
@endif

<div data-contract-show-card class="grid gap-3 rounded border bg-white p-4 shadow-sm sm:grid-cols-2">
    <p><span class="font-medium">{{ __('contracts.show.tenant') }}</span> {{ $contract->tenant->full_name }}</p>
    <p><span class="font-medium">{{ __('contracts.show.unit') }}</span> <bdi dir="ltr">{{ $contract->unit->unit_number }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.contract_number') }}</span> <bdi dir="ltr">{{ $contract->contract_number }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.start_date') }}</span> <bdi dir="ltr">{{ $contract->start_date->toDateString() }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.end_date') }}</span> <bdi dir="ltr">{{ $contract->end_date->toDateString() }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.rent_per_period') }}</span> <bdi dir="ltr">{{ number_format($contract->rent_amount, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.deposit') }}</span> <bdi dir="ltr">{{ number_format($contract->deposit_amount ?? 0, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.frequency') }}</span> {{ __('contracts.frequencies.'.$contract->payment_frequency) }}</p>
    <p><span class="font-medium">{{ __('contracts.show.status') }}</span> {{ __('contracts.statuses.'.$contract->status) }}</p>
</div>

@if($contract->status === 'terminated')
    <div class="mt-4 grid gap-3 rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 sm:grid-cols-2">
        <p><span class="font-medium">{{ __('contracts.lifecycle.termination_reason') }}:</span> {{ $contract->termination_reason ?? __('payments.not_available') }}</p>
        <p><span class="font-medium">{{ __('contracts.lifecycle.termination_effective_date') }}:</span> <bdi dir="ltr">{{ $contract->termination_effective_date?->toDateString() ?? __('payments.not_available') }}</bdi></p>
        <p><span class="font-medium">{{ __('contracts.lifecycle.terminated_at') }}:</span> <bdi dir="ltr">{{ $contract->terminated_at?->toDateTimeString() ?? __('payments.not_available') }}</bdi></p>
        <p><span class="font-medium">{{ __('contracts.lifecycle.terminated_by') }}:</span> {{ $contract->terminatedBy?->name ?? __('payments.not_available') }}</p>
    </div>
@elseif(auth()->user()?->can('terminate', $contract) && $contract->status === 'active' && $contract->end_date->toDateString() >= now()->toDateString())
    <form method="post" action="{{ route('contracts.terminate', $contract) }}" class="mt-4 space-y-3 rounded border border-amber-200 bg-amber-50 p-4">
        @csrf
        @method('patch')
        <label class="block text-sm font-medium">{{ __('contracts.lifecycle.termination_reason') }}
            <textarea name="termination_reason" rows="3" required class="mt-1 w-full rounded border p-2">{{ old('termination_reason') }}</textarea>
            @error('termination_reason')<span class="mt-1 block text-sm text-red-700">{{ $message }}</span>@enderror
        </label>
        <p class="text-sm text-amber-900">{{ __('contracts.lifecycle.confirm_terminate') }}</p>
        <button class="tap-target rounded bg-amber-700 px-4 text-sm text-white">{{ __('contracts.lifecycle.terminate') }}</button>
    </form>
@endif

<h2 class="mb-2 mt-6 font-semibold">{{ __('contracts.show.payment_schedule') }}</h2>
<div data-contract-payments-mobile-list class="grid gap-3 md:hidden">
    @foreach($contract->payments as $payment)
        <article data-contract-payment-mobile-card class="rounded border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">{{ __('payments.columns.due_date') }}</p>
                    <p class="font-semibold"><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></p>
                </div>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('payments.statuses.'.$payment->status) }}</span>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ __('payments.columns.amount') }}: <bdi dir="ltr">{{ number_format($payment->amount_due, 2) }}</bdi></p>
            @if($payment->status === 'cancelled')
                @if($payment->amount_paid > 0)
                    <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('payments.receipt', $payment) }}">{{ __('payments.view_receipt') }}</a>
                @else
                    <span class="mt-3 block text-sm text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                @endif
            @elseif($payment->status === 'paid')
                <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>
            @elseif(in_array($payment->status, ['pending', 'partial', 'overdue'], true))
                <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded bg-slate-900 px-4 text-center text-sm font-medium text-white" href="{{ route('payments.edit', $payment) }}">{{ __('contracts.show.record_payment') }}</a>
            @else
                <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('payments.show', $payment) }}">{{ __('app.actions.view') }}</a>
            @endif
        </article>
    @endforeach
</div>

<div class="hidden md:block">
<x-table min-width="min-w-[36rem]">
    <tbody>
        @foreach($contract->payments as $payment)
            <tr class="border-t">
                <td class="bidi-isolate p-3 whitespace-nowrap" dir="ltr">{{ $payment->due_date->toDateString() }}</td>
                <td class="bidi-isolate p-3 whitespace-nowrap" dir="ltr">{{ number_format($payment->amount_due, 2) }}</td>
                <td class="p-3 whitespace-nowrap">
                    {{ __('payments.statuses.'.$payment->status) }}
                    @if($payment->status === 'cancelled')
                        <span class="block text-xs text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                    @endif
                </td>
                <td class="p-3 whitespace-nowrap">
                    @if($payment->status === 'cancelled')
                        @if($payment->amount_paid > 0)
                            <a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.receipt', $payment) }}">{{ __('payments.view_receipt') }}</a>
                        @else
                            <span class="text-sm text-slate-500">{{ __('payments.lifecycle.cancelled_due_to_contract_termination') }}</span>
                        @endif
                    @elseif($payment->status === 'paid')
                        <a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.show', $payment) }}">{{ __('payments.view_receipt') }}</a>
                    @elseif(in_array($payment->status, ['pending', 'partial', 'overdue'], true))
                        <a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.edit', $payment) }}">{{ __('contracts.show.record_payment') }}</a>
                    @else
                        <a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.show', $payment) }}">{{ __('app.actions.view') }}</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</x-table>
</div>
@endsection
