@extends('layouts.app')

@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('contracts.title') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('contracts.description') }}</p>
    </div>
    <a class="tap-target flex min-h-11 items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('contracts.create') }}">{{ __('contracts.add') }}</a>
</div>

<form class="mb-4 grid gap-3 rounded border bg-white p-3 sm:grid-cols-[1fr_auto]">
    <select name="status" class="tap-target min-h-11 rounded border p-2">
        <option value="">{{ __('contracts.all_statuses') }}</option>
        @foreach(['active', 'expired', 'terminated'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __('contracts.statuses.'.$status) }}</option>
        @endforeach
    </select>
    <button class="tap-target min-h-11 rounded bg-slate-900 px-4 text-white">{{ __('contracts.filter') }}</button>
</form>

<div data-mobile-contracts-list class="grid gap-3 md:hidden">
    @forelse($contracts as $contract)
        <article data-contract-mobile-card class="rounded border bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <h2 class="font-semibold"><bdi dir="ltr">{{ $contract->contract_number }}</bdi></h2>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('contracts.statuses.'.$contract->status) }}</span>
            </div>
            <dl class="mt-3 grid gap-2 text-sm">
                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">{{ __('contracts.columns.tenant') }}</dt><dd class="break-words">{{ $contract->tenant->full_name }}</dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">{{ __('contracts.columns.unit') }}</dt><dd><bdi dir="ltr">{{ $contract->unit->unit_number }}</bdi></dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">{{ __('contracts.columns.start_date') }}</dt><dd><bdi dir="ltr">{{ $contract->start_date->toDateString() }}</bdi></dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">{{ __('contracts.columns.end_date') }}</dt><dd><bdi dir="ltr">{{ $contract->end_date->toDateString() }}</bdi></dd></div>
                <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">{{ __('contracts.columns.rent_per_period') }}</dt><dd><bdi dir="ltr">{{ number_format($contract->rent_amount, 2) }}</bdi></dd></div>
            </dl>
            @if($contract->daysUntilExpiry() !== null && $contract->daysUntilExpiry() <= 90)
                <p class="mt-3 inline-flex rounded bg-amber-100 px-2 py-1 text-xs text-amber-800">
                    @if($contract->daysUntilExpiry() === 0)
                        {{ __('app.dashboard.expires_today') }}
                    @elseif($contract->daysUntilExpiry() === 1)
                        {{ __('app.dashboard.expires_in_one_day') }}
                    @else
                        {{ __('app.dashboard.expires_in_days', ['count' => $contract->daysUntilExpiry()]) }}
                    @endif
                </p>
            @endif
            <a class="tap-target mt-4 inline-flex min-h-11 w-full items-center justify-center rounded border px-4 text-center text-sm font-medium" href="{{ route('contracts.show', $contract) }}">{{ __('contracts.view') }}</a>
        </article>
    @empty
        <p class="rounded border bg-white p-4 text-center text-slate-500">{{ __('contracts.empty') }}</p>
    @endforelse
</div>

<div class="hidden md:block">
    <x-table min-width="min-w-[72rem]">
        <thead>
            <tr class="text-start">
                <th class="p-4">{{ __('contracts.columns.contract_number') }}</th>
                <th class="p-4">{{ __('contracts.columns.tenant') }}</th>
                <th class="p-4">{{ __('contracts.columns.unit') }}</th>
                <th class="p-4">{{ __('contracts.columns.start_date') }}</th>
                <th class="p-4">{{ __('contracts.columns.end_date') }}</th>
                <th class="p-4">{{ __('contracts.columns.rent_per_period') }}</th>
                <th class="p-4">{{ __('contracts.columns.frequency') }}</th>
                <th class="p-4">{{ __('contracts.columns.status') }}</th>
                <th class="p-4">{{ __('contracts.columns.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contracts as $contract)
                <tr class="border-t">
                    <td class="bidi-isolate p-4 font-medium whitespace-nowrap" dir="ltr">{{ $contract->contract_number }}</td>
                    <td class="p-4">{{ $contract->tenant->full_name }}</td>
                    <td class="bidi-isolate p-4 whitespace-nowrap" dir="ltr">{{ $contract->unit->unit_number }}</td>
                    <td class="bidi-isolate p-4 whitespace-nowrap" dir="ltr">{{ $contract->start_date->toDateString() }}</td>
                    <td class="p-4 whitespace-nowrap">
                        <span class="bidi-isolate" dir="ltr">{{ $contract->end_date->toDateString() }}</span>
                        @if($contract->daysUntilExpiry() !== null && $contract->daysUntilExpiry() <= 90)
                            <span class="mt-1 inline-flex rounded bg-amber-100 px-2 py-1 text-xs text-amber-800">
                                @if($contract->daysUntilExpiry() === 0)
                                    {{ __('app.dashboard.expires_today') }}
                                @elseif($contract->daysUntilExpiry() === 1)
                                    {{ __('app.dashboard.expires_in_one_day') }}
                                @else
                                    {{ __('app.dashboard.expires_in_days', ['count' => $contract->daysUntilExpiry()]) }}
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="bidi-isolate p-4 whitespace-nowrap" dir="ltr">{{ number_format($contract->rent_amount, 2) }}</td>
                    <td class="p-4 whitespace-nowrap">{{ __('contracts.frequencies.'.$contract->payment_frequency) }}</td>
                    <td class="p-4 whitespace-nowrap"><span class="rounded bg-slate-100 px-2 py-1 text-xs">{{ __('contracts.statuses.'.$contract->status) }}</span></td>
                    <td class="p-4 whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('contracts.show', $contract) }}">{{ __('contracts.view') }}</a></td>
                </tr>
            @empty
                <tr class="border-t"><td colspan="9" class="p-4 text-center text-slate-500">{{ __('contracts.empty') }}</td></tr>
            @endforelse
        </tbody>
    </x-table>
</div>

{{ $contracts->links() }}
@endsection
