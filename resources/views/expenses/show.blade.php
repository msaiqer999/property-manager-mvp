@extends('layouts.app')
@section('content')
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold">{{ __('expenses.expense') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ __('expenses.show.status') }}:
            @if($expense->voided_at)
                <span class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ __('expenses.lifecycle.voided') }}</span>
            @else
                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">{{ __('expenses.lifecycle.active') }}</span>
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($expense->invoice_image)
            <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('expenses.invoice.download', $expense) }}">{{ __('expenses.download_invoice') }}</a>
        @endif
        @can('update', $expense)
            @if(! $expense->voided_at)
                <a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('expenses.edit', $expense) }}">{{ __('expenses.edit_action') }}</a>
            @endif
        @endcan
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
    <div class="rounded border bg-white p-4">
        <p>{{ __('expenses.show.building') }}: {{ $expense->building->name }}</p>
        <p>{{ __('expenses.show.unit') }}: <span dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</span></p>
        <p>{{ __('expenses.show.category') }}: {{ __('expenses.categories.'.$expense->category) }}</p>
        <p>{{ __('expenses.show.amount') }}: <span dir="ltr">{{ number_format($expense->amount, 2) }}</span></p>
        <p>{{ __('expenses.show.date') }}: <span dir="ltr">{{ $expense->expense_date->toDateString() }}</span></p>
        <p>{{ __('expenses.show.notes') }}: {{ $expense->notes }}</p>
        @if($expense->voided_at)
            <div class="mt-4 border-t pt-4">
                <p>{{ __('expenses.lifecycle.voided_at') }}: <span dir="ltr">{{ $expense->voided_at->toDateTimeString() }}</span></p>
                <p>{{ __('expenses.lifecycle.voided_by') }}: {{ $expense->voidedBy?->name ?? __('expenses.not_available') }}</p>
                <p>{{ __('expenses.lifecycle.void_reason') }}: {{ $expense->void_reason }}</p>
            </div>
        @endif
    </div>

    @can('voidExpense', $expense)
        @if(! $expense->voided_at)
            <form method="post" action="{{ route('expenses.void', $expense) }}" class="rounded border bg-white p-4">
                @csrf
                @method('patch')
                <label class="block text-sm font-medium">{{ __('expenses.lifecycle.void_reason') }}
                    <textarea name="void_reason" rows="5" required class="mt-1 w-full rounded border p-2">{{ old('void_reason') }}</textarea>
                </label>
                <button class="tap-target mt-3 w-full rounded bg-amber-700 px-4 text-sm font-medium text-white" onclick="return confirm('{{ __('expenses.lifecycle.confirm_void') }}')">{{ __('expenses.lifecycle.void') }}</button>
            </form>
        @endif
    @endcan
</div>
@endsection
