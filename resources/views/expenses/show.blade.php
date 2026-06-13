@extends('layouts.app')
@section('content')
<div class="mb-4 flex items-center justify-between"><h1 class="text-xl font-semibold">Expense</h1><a class="rounded border px-3 py-2 text-sm" href="{{ route('expenses.edit', $expense) }}">Edit</a></div>
<div class="rounded border bg-white p-4"><p>{{ $expense->building->name }}</p><p>{{ $expense->category }}</p><p>{{ number_format($expense->amount, 2) }}</p><p>{{ $expense->notes }}</p></div>
@endsection
