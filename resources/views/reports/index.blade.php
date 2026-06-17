@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">Reports</h1>
<div class="grid gap-3 sm:grid-cols-3">
@foreach(['Income' => $income, 'Expenses' => $expensesTotal, 'Net profit' => $netProfit] as $label => $value)
<div class="rounded border bg-white p-4 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ $label }}</p><p class="mt-1 break-words text-2xl font-semibold">{{ number_format($value, 2) }}</p></div>
@endforeach
</div>
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
@foreach([
    'building-income' => 'Export building income PDF',
    'unit-statement' => 'Download unit statement PDF',
    'expenses' => 'Export expenses PDF',
    'overdue' => 'Export overdue payments PDF',
    'net-profit' => 'Export net profit PDF',
    'monthly-summary' => 'Export monthly summary PDF',
] as $type => $label)
<a class="tap-target flex items-center rounded border bg-white p-4 font-medium shadow-sm" href="{{ route('reports.pdf', $type) }}">{{ $label }}</a>
@endforeach
</div>
@endsection
