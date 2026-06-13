@extends('layouts.app')

@section('content')
<div class="mb-4">
    <p class="text-sm text-slate-500">{{ auth()->user()->organization->name }}</p>
    <h1 class="mt-1 text-2xl font-semibold">Dashboard</h1>
</div>

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        'Monthly income' => $monthlyIncome,
        'Monthly expenses' => $monthlyExpenses,
        'Net profit' => $monthlyIncome - $monthlyExpenses,
        'Overdue amount' => $overdueAmount,
    ] as $label => $value)
        <div class="rounded border bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 break-words text-2xl font-semibold leading-tight">{{ number_format($value, 2) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-3 grid gap-3 sm:grid-cols-2">
    <a href="{{ route('units.index', ['status' => 'vacant']) }}" class="tap-target rounded border bg-white p-4 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Vacant units</p>
        <p class="mt-1 text-2xl font-semibold">{{ $vacantUnits }}</p>
    </a>
    <a href="{{ route('units.index', ['status' => 'rented']) }}" class="tap-target rounded border bg-white p-4 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Rented units</p>
        <p class="mt-1 text-2xl font-semibold">{{ $rentedUnits }}</p>
    </a>
</div>

<div class="mt-6 grid gap-4 lg:grid-cols-3">
    <section class="rounded border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Contracts ending soon</h2>
        @forelse($endingSoon as $contract)
            <div class="border-t py-3 text-sm">
                <p class="font-medium">{{ $contract->contract_number }}</p>
                <p class="text-slate-500">Ends {{ $contract->end_date->toDateString() }}</p>
            </div>
        @empty <p class="text-sm text-slate-500">No upcoming endings.</p> @endforelse
    </section>
    <section class="rounded border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Latest payments</h2>
        @forelse($latestPayments as $payment)
            <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                <span class="font-medium">{{ number_format($payment->amount_paid, 2) }}</span>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $payment->status }}</span>
            </div>
        @empty <p class="text-sm text-slate-500">No payments yet.</p> @endforelse
    </section>
    <section class="rounded border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Latest expenses</h2>
        @forelse($latestExpenses as $expense)
            <div class="flex items-center justify-between gap-3 border-t py-3 text-sm">
                <span class="capitalize">{{ $expense->category }}</span>
                <span class="font-medium">{{ number_format($expense->amount, 2) }}</span>
            </div>
        @empty <p class="text-sm text-slate-500">No expenses yet.</p> @endforelse
    </section>
</div>
@endsection
