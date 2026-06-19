@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between gap-3"><h1 class="text-xl font-semibold">{{ __('expenses.expense') }}</h1><a class="tap-target inline-flex items-center rounded border px-3 text-sm" href="{{ route('expenses.edit', $expense) }}">{{ __('expenses.edit_action') }}</a></div>
<div class="rounded border bg-white p-4"><p>{{ __('expenses.show.building') }}: {{ $expense->building->name }}</p><p>{{ __('expenses.show.unit') }}: <span dir="ltr">{{ $expense->unit?->unit_number ?? __('expenses.not_available') }}</span></p><p>{{ __('expenses.show.category') }}: {{ __('expenses.categories.'.$expense->category) }}</p><p>{{ __('expenses.show.amount') }}: <span dir="ltr">{{ number_format($expense->amount, 2) }}</span></p><p>{{ __('expenses.show.date') }}: <span dir="ltr">{{ $expense->expense_date->toDateString() }}</span></p><p>{{ __('expenses.show.notes') }}: {{ $expense->notes }}</p></div>
@endsection
