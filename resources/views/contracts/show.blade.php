@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">Contract {{ $contract->contract_number }}</h1>
        <p class="mt-1 text-sm text-slate-600">This contract connects a tenant to a unit and defines the rent schedule.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('create', \App\Models\Contract::class)
            @if($contract->isRenewalEligible())
                <a class="tap-target inline-flex items-center rounded bg-slate-900 px-3 text-sm text-white" href="{{ route('contracts.create', ['renew_from' => $contract->id]) }}">Prepare renewal</a>
            @endif
        @endcan
        <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('contracts.pdf', $contract) }}">Download contract PDF</a>
        <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('contracts.edit', $contract) }}">Edit contract</a>
    </div>
</div>
@if($contract->expiryWarningText())<div class="mb-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"><p class="font-medium">Contract expiry warning</p><p>{{ $contract->expiryWarningText() }} on {{ $contract->end_date->toDateString() }}.</p></div>@endif
<div class="grid gap-3 rounded border bg-white p-4 sm:grid-cols-2"><p><span class="font-medium">Tenant:</span> {{ $contract->tenant->full_name }}</p><p><span class="font-medium">Unit:</span> {{ $contract->unit->unit_number }}</p><p><span class="font-medium">Contract number:</span> {{ $contract->contract_number }}</p><p><span class="font-medium">Start date:</span> {{ $contract->start_date->toDateString() }}</p><p><span class="font-medium">End date:</span> {{ $contract->end_date->toDateString() }}</p><p><span class="font-medium">Rent per payment period:</span> {{ number_format($contract->rent_amount, 2) }}</p><p><span class="font-medium">Deposit:</span> {{ number_format($contract->deposit_amount ?? 0, 2) }}</p><p><span class="font-medium">Frequency:</span> {{ $contract->payment_frequency }}</p><p><span class="font-medium">Status:</span> {{ $contract->status }}</p></div>
<h2 class="mb-2 mt-6 font-semibold">Payment schedule</h2>
<x-table min-width="min-w-[36rem]"><tbody>@foreach($contract->payments as $payment)<tr class="border-t"><td class="p-3 whitespace-nowrap">{{ $payment->due_date->toDateString() }}</td><td class="p-3 whitespace-nowrap">{{ number_format($payment->amount_due, 2) }}</td><td class="p-3 whitespace-nowrap">{{ $payment->status }}</td><td class="p-3 whitespace-nowrap"><a class="tap-target inline-flex items-center text-blue-700" href="{{ route('payments.edit', $payment) }}">Record</a></td></tr>@endforeach</tbody></x-table>
@endsection
