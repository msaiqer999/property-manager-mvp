@extends('layouts.app')
@section('content')
@php
    $invoiceUnavailable = session('status') === __('expenses.invoice_missing');
@endphp
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('expenses.expense') }}</h1>
        <p class="mt-1 text-sm text-brand-muted">{{ __('expenses.show.status') }}:
            @if($expense->voided_at)
                <x-status-badge status="voided" :label="__('expenses.lifecycle.voided')" />
            @else
                <x-status-badge status="active" :label="__('expenses.lifecycle.active')" />
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($expense->invoice_image && ! $invoiceUnavailable)
            <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('expenses.invoice', $expense) }}">{{ __('expenses.download_invoice') }}</a>
        @endif
        @can('update', $expense)
            @if(! $expense->voided_at)
                <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('expenses.edit', $expense) }}">{{ __('expenses.edit_action') }}</a>
            @endif
        @endcan
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
    <div class="space-y-4">
        <section class="rounded border bg-brand-surface p-4 shadow-sm">
            <h2 class="text-base font-semibold text-brand-text">{{ __('expenses.show.details') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.building') }}: {{ $expense->building->name }}</p>
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.unit') }}: <span dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</span></p>
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.category') }}: {{ __('expenses.categories.'.$expense->category) }}</p>
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.amount') }}: <span dir="ltr">{{ number_format($expense->amount, 2) }}</span></p>
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.date') }}: <span dir="ltr">{{ $expense->expense_date->toDateString() }}</span></p>
                <p class="rounded bg-brand-background p-3 text-sm">{{ __('expenses.show.status') }}: <span class="font-medium text-brand-text">{{ $expense->voided_at ? __('expenses.lifecycle.voided') : __('expenses.lifecycle.active') }}</span></p>
                <p class="rounded bg-brand-background p-3 text-sm sm:col-span-2">{{ __('expenses.show.notes') }}: {{ $expense->notes ?: __('expenses.not_available') }}</p>
            </div>
        </section>

        @if($expense->invoice_image)
            <section class="rounded border bg-brand-surface p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-brand-text">{{ __('expenses.show.invoice_attachment') }}</h2>
                        <p class="mt-1 text-sm text-brand-muted">
                            {{ $invoiceUnavailable ? __('expenses.invoice_unavailable_hint') : __('expenses.show.invoice_available') }}
                        </p>
                    </div>
                    @if(! $invoiceUnavailable)
                        <a class="tap-target inline-flex items-center justify-center rounded border px-3 text-sm font-medium text-brand-text" href="{{ route('expenses.invoice', $expense) }}">{{ __('expenses.download_invoice') }}</a>
                    @endif
                </div>
            </section>
        @endif

        @if($expense->voided_at)
            <section class="rounded border bg-brand-surface p-4 shadow-sm">
                <h2 class="text-base font-semibold text-brand-text">{{ __('expenses.lifecycle.voided') }}</h2>
                <p>{{ __('expenses.lifecycle.voided_at') }}: <span dir="ltr">{{ $expense->voided_at->toDateTimeString() }}</span></p>
                <p>{{ __('expenses.lifecycle.voided_by') }}: {{ $expense->voidedBy?->name ?? __('expenses.not_available') }}</p>
                <p>{{ __('expenses.lifecycle.void_reason') }}: {{ $expense->void_reason }}</p>
            </section>
        @endif
    </div>

    @can('voidExpense', $expense)
        @if(! $expense->voided_at)
            <form method="post" action="{{ route('expenses.void', $expense) }}" class="rounded border bg-brand-surface p-4">
                @csrf
                @method('patch')
                <label class="block text-sm font-medium">{{ __('expenses.lifecycle.void_reason') }}
                    <textarea name="void_reason" rows="5" required class="mt-1 w-full rounded border p-2">{{ old('void_reason') }}</textarea>
                </label>
                <button class="tap-target mt-3 w-full rounded bg-state-danger px-4 text-sm font-medium text-white" onclick="return confirm('{{ __('expenses.lifecycle.confirm_void') }}')">{{ __('expenses.lifecycle.void') }}</button>
            </form>
        @endif
    @endcan
</div>
@endsection
