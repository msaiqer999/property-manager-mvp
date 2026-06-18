@extends('layouts.app')
@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">Expenses</h1><a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('expenses.create') }}">Add expense</a></div>
<x-table min-width="min-w-[44rem]"><tbody>@foreach($expenses as $expense)<tr class="border-t"><td class="p-4 whitespace-nowrap">{{ $expense->expense_date->toDateString() }}</td><td class="p-4 font-medium">{{ $expense->building->name }}</td><td class="p-4 capitalize whitespace-nowrap">{{ $expense->category }}</td><td class="p-4 whitespace-nowrap">{{ number_format($expense->amount, 2) }}</td><td class="p-4 whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('expenses.show', $expense) }}">View</a></td></tr>@endforeach</tbody></x-table>
{{ $expenses->links() }}
@endsection
