@extends('layouts.app')

@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('contracts.contract', ['number' => $contract->contract_number]) }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.show_description') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('create', \App\Models\Contract::class)
            @if($contract->isRenewalEligible())
                <a class="tap-target inline-flex items-center rounded bg-slate-900 px-3 text-sm text-white" href="{{ route('contracts.create', ['renew_from' => $contract->id]) }}">{{ __('contracts.show.prepare_renewal') }}</a>
            @endif
        @endcan
        <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('contracts.pdf', $contract) }}">{{ __('contracts.show.download_pdf') }}</a>
        <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('contracts.edit', $contract) }}">{{ __('contracts.show.edit') }}</a>
    </div>
</div>

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

<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2">
    <p><span class="font-medium">{{ __('contracts.show.tenant') }}</span> {{ $contract->tenant->full_name }}</p>
    <p><span class="font-medium">{{ __('contracts.show.unit') }}</span> <bdi>{{ $contract->unit->unit_number }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.contract_number') }}</span> <bdi>{{ $contract->contract_number }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.start_date') }}</span> <bdi>{{ $contract->start_date->toDateString() }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.end_date') }}</span> <bdi>{{ $contract->end_date->toDateString() }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.rent_per_period') }}</span> <bdi>{{ number_format($contract->rent_amount, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.deposit') }}</span> <bdi>{{ number_format($contract->deposit_amount ?? 0, 2) }}</bdi></p>
    <p><span class="font-medium">{{ __('contracts.show.frequency') }}</span> {{ __('contracts.frequencies.'.$contract->payment_frequency) }}</p>
    <p><span class="font-medium">{{ __('contracts.show.status') }}</span> {{ __('contracts.statuses.'.$contract->status) }}</p>
</div>

<h2 class="mb-2 mt-6 font-semibold">{{ __('contracts.show.payment_schedule') }}</h2>
<x-table min-width="min-w-[36rem]">
    <tbody>
        @foreach($contract->payments as $payment)
            <tr class="border-t">
                <td class="bidi-isolate p-3 whitespace-nowrap" dir="ltr">{{ $payment->due_date->toDateString() }}</td>
                <td class="bidi-isolate p-3 whitespace-nowrap" dir="ltr">{{ number_format($payment->amount_due, 2) }}</td>
                <td class="p-3 whitespace-nowrap">{{ __('contracts.statuses.'.$payment->status) }}</td>
                <td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.edit', $payment) }}">{{ __('contracts.show.record_payment') }}</a></td>
            </tr>
        @endforeach
    </tbody>
</x-table>
@endsection
