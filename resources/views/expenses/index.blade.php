@extends('layouts.app')
@section('content')
<div class="mb-4 grid gap-3 sm:flex sm:items-center sm:justify-between"><h1 class="text-xl font-semibold">{{ __('expenses.title') }}</h1><a class="tap-target flex items-center justify-center rounded bg-slate-900 px-4 text-sm font-medium text-white" href="{{ route('expenses.create') }}">{{ __('expenses.add') }}</a></div>
<x-table min-width="min-w-[44rem]"><tbody>@foreach($expenses as $expense)<tr class="border-t"><td class="p-4 whitespace-nowrap"><span dir="ltr">{{ $expense->expense_date->toDateString() }}</span></td><td class="p-4 font-medium">{{ $expense->building->name }}</td><td class="p-4 capitalize whitespace-nowrap">{{ __('expenses.categories.'.$expense->category) }}</td><td class="p-4 whitespace-nowrap"><span dir="ltr">{{ number_format($expense->amount, 2) }}</span></td><td class="p-4 whitespace-nowrap"><a class="tap-target inline-flex items-center rounded border px-3 text-slate-700" href="{{ route('expenses.show', $expense) }}">{{ __('expenses.view') }}</a></td></tr>@endforeach</tbody></x-table>
{{ $expenses->links() }}
@endsection
