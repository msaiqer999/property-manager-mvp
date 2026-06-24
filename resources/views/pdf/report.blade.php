<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.types.'.$type) }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #111827; font-size: 11px; line-height: 1.55; }
        h1 { margin: 0 0 14px; font-size: 21px; }
        h2 { margin: 20px 0 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; vertical-align: top; }
        th { background: #f3f4f6; text-align: inherit; }
        .summary td:first-child { font-weight: bold; width: 35%; }
        .muted { color: #4b5563; }
        .ltr { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ __('reports.types.'.$type) }}</h1>
    <p class="muted">{{ __('reports.pdf.generated_at') }} <bdi dir="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></p>

    <table class="summary">
        <tr>
            <td>{{ __('reports.summary.income') }}</td>
            <td><bdi dir="ltr">{{ number_format((float) $income, 2) }}</bdi></td>
        </tr>
        <tr>
            <td>{{ __('reports.summary.expenses') }}</td>
            <td><bdi dir="ltr">{{ number_format((float) $expensesTotal, 2) }}</bdi></td>
        </tr>
        <tr>
            <td>{{ __('reports.summary.net_profit') }}</td>
            <td><bdi dir="ltr">{{ number_format((float) $netProfit, 2) }}</bdi></td>
        </tr>
    </table>

    <h2>{{ __('reports.pdf.rows') }}</h2>
    @if($rows->isEmpty())
        <p>{{ __('reports.pdf.no_data') }}</p>
    @elseif($type === 'building-income')
        <table>
            <tr>
                <th>{{ __('reports.columns.building') }}</th>
                <th>{{ __('reports.columns.income') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->income, 2) }}</bdi></td>
                </tr>
            @endforeach
        </table>
    @elseif($type === 'unit-statement')
        <table>
            <tr>
                <th>{{ __('reports.columns.unit') }}</th>
                <th>{{ __('reports.columns.building') }}</th>
                <th>{{ __('reports.columns.contracts') }}</th>
                <th>{{ __('reports.columns.amount_due') }}</th>
                <th>{{ __('reports.columns.amount_paid') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td><bdi dir="ltr">{{ $row->unit_number }}</bdi></td>
                    <td>{{ $row->building->name }}</td>
                    <td><bdi dir="ltr">{{ $row->contracts->count() }}</bdi></td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->contracts->sum(fn ($contract) => $contract->payments->sum('amount_due')), 2) }}</bdi></td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->contracts->sum(fn ($contract) => $contract->payments->sum('amount_paid')), 2) }}</bdi></td>
                </tr>
            @endforeach
        </table>
    @elseif($type === 'expenses')
        <table>
            <tr>
                <th>{{ __('reports.columns.date') }}</th>
                <th>{{ __('reports.columns.building') }}</th>
                <th>{{ __('reports.columns.unit') }}</th>
                <th>{{ __('reports.columns.category') }}</th>
                <th>{{ __('reports.columns.amount') }}</th>
                <th>{{ __('reports.columns.status') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td><bdi dir="ltr">{{ $row->expense_date->toDateString() }}</bdi></td>
                    <td>{{ $row->building?->name ?? __('reports.pdf.not_available') }}</td>
                    <td><bdi dir="ltr">{{ $row->unit?->unit_number ?? __('reports.pdf.not_available') }}</bdi></td>
                    <td>{{ __('expenses.categories.'.$row->category) }}</td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->amount, 2) }}</bdi></td>
                    <td>{{ $row->voided_at ? __('expenses.lifecycle.voided') : __('expenses.lifecycle.active') }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <table>
            <tr>
                <th>{{ __('reports.columns.due_date') }}</th>
                <th>{{ __('reports.columns.tenant') }}</th>
                <th>{{ __('reports.columns.unit') }}</th>
                <th>{{ __('reports.columns.contract') }}</th>
                <th>{{ __('reports.columns.amount_due') }}</th>
                <th>{{ __('reports.columns.amount_paid') }}</th>
                <th>{{ __('reports.columns.remaining_amount') }}</th>
                <th>{{ __('reports.columns.method') }}</th>
                <th>{{ __('reports.columns.status') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td><bdi dir="ltr">{{ $row->due_date->toDateString() }}</bdi></td>
                    <td>{{ $row->contract?->tenant?->full_name ?? __('reports.pdf.not_available') }}</td>
                    <td><bdi dir="ltr">{{ $row->contract?->unit?->unit_number ?? __('reports.pdf.not_available') }}</bdi> - {{ $row->contract?->unit?->building?->name ?? __('reports.pdf.not_available') }}</td>
                    <td><bdi dir="ltr">{{ $row->contract?->contract_number ?? __('reports.pdf.not_available') }}</bdi></td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->amount_due, 2) }}</bdi></td>
                    <td><bdi dir="ltr">{{ number_format((float) $row->amount_paid, 2) }}</bdi></td>
                    <td><bdi dir="ltr">{{ number_format((float) ($row->remaining_amount ?? ($row->amount_due - $row->amount_paid)), 2) }}</bdi></td>
                    <td>{{ $row->payment_method ? __('payments.methods.'.$row->payment_method) : __('reports.pdf.not_available') }}</td>
                    <td>{{ __('payments.statuses.'.$row->status) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
