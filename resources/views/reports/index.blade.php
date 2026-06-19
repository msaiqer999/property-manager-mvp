@extends('layouts.app')
@section('content')
<h1 class="mb-4 text-xl font-semibold">{{ __('reports.title') }}</h1>
<div class="grid gap-3 sm:grid-cols-3">
@foreach(['income' => $income, 'expenses' => $expensesTotal, 'net_profit' => $netProfit] as $label => $value)
<div class="rounded border bg-white p-4 shadow-sm"><p class="text-sm font-medium text-slate-500">{{ __('reports.summary.'.$label) }}</p><p class="mt-1 break-words text-2xl font-semibold" dir="ltr">{{ number_format($value, 2) }}</p></div>
@endforeach
</div>
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
@foreach([
    'building-income' => __('reports.actions.building_income'),
    'unit-statement' => __('reports.actions.unit_statement'),
    'expenses' => __('reports.actions.expenses'),
    'overdue' => __('reports.actions.overdue'),
    'net-profit' => __('reports.actions.net_profit'),
    'monthly-summary' => __('reports.actions.monthly_summary'),
] as $type => $label)
<a class="tap-target flex items-center rounded border bg-white p-4 font-medium shadow-sm" href="{{ route('reports.pdf', $type) }}">{{ $label }}</a>
@endforeach
</div>
@endsection
