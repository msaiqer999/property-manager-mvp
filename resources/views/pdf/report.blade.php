@php
    $direction = \App\Support\SupportedLocales::direction(app()->getLocale());
    $isRtl = $direction === 'rtl';
    $na = __('reports.pdf.not_available');
    $reportCurrency = $reportCurrency ?? null;
    $formatReportMoney = fn ($value) => trim(($reportCurrency ? $reportCurrency.' ' : '').number_format((float) $value, 2));
    $statementRowsForPdf = $type === 'unit-statement' ? ($statementRows ?? collect()) : collect();
    $statementContractNumbers = ($type === 'unit-statement')
        ? $statementRowsForPdf->pluck('contract.contract_number')->filter()->unique()->values()
        : collect();
    $statementContractLabel = $statementContractNumbers->count() === 1
        ? $statementContractNumbers->first()
        : $na;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.types.'.$type) }}</title>
    <style>
        @page { margin: 9mm; }

        body {
            background: #ffffff;
            color: #111827;
            font-family: {{ $isRtl ? 'xbriyaz, dejavusans' : 'dejavusans' }}, sans-serif;
            font-size: {{ $isRtl ? '11px' : '10.6px' }};
            line-height: 1.42;
        }

        .hero {
            background: #0f172a;
            color: #ffffff;
            margin-bottom: 8px;
            padding: 10px 12px;
        }

        .hero-table {
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
            width: 100%;
        }

        .hero-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .hero-meta {
            color: #dbeafe;
            font-size: 9px;
            text-align: {{ $isRtl ? 'left' : 'right' }};
            width: 34%;
        }

        h1 {
            color: #ffffff;
            font-size: 22px;
            line-height: 1.1;
            margin: 0;
        }

        h2 {
            background: #f1f5f9;
            border: 1px solid #d8dee8;
            border-bottom: 0;
            color: #0f172a;
            font-size: 11px;
            margin: 8px 0 0;
            padding: 5px 7px;
        }

        table {
            border-collapse: collapse;
            margin-top: 0;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
        }

        th {
            background: #1e293b;
            border-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            text-align: inherit;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .summary {
            border-collapse: separate;
            border-spacing: 4px;
            margin: 0 0 6px;
            table-layout: fixed;
        }

        .summary td {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
        }

        .summary-label {
            color: #64748b;
            display: block;
            font-size: 8.5px;
            margin-bottom: 1px;
        }

        .summary-value {
            color: #0f172a;
            display: block;
            font-size: 10px;
            font-weight: 700;
        }

        .empty {
            border: 1px solid #d8dee8;
            color: #475569;
            margin: 0;
            padding: 9px;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .text-end {
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="hero">
        <table class="hero-table">
            <tr>
                <td>
                    <h1>{{ __('reports.types.'.$type) }}</h1>
                </td>
                <td class="hero-meta">
                    {{ __('reports.pdf.generated_at') }}<br>
                    <bdi class="ltr">{{ now()->format('Y-m-d H:i') }}</bdi>
                </td>
            </tr>
        </table>
    </div>

    @if($type === 'unit-statement')
        <table class="summary">
            <tr>
                @foreach(['amount_due', 'amount_paid', 'remaining_balance', 'overdue_remaining'] as $key)
                    <td>
                        <span class="summary-label">{{ __('reports.columns.'.$key) }}</span>
                        <span class="summary-value"><bdi class="ltr">{{ $formatReportMoney($totals[$key] ?? 0) }}</bdi></span>
                    </td>
                @endforeach
            </tr>
        </table>
    @else
        <table class="summary">
            <tr>
                <td>
                    <span class="summary-label">{{ __('reports.summary.income') }}</span>
                    <span class="summary-value"><bdi class="ltr">{{ $formatReportMoney($income) }}</bdi></span>
                </td>
                <td>
                    <span class="summary-label">{{ __('reports.summary.expenses') }}</span>
                    <span class="summary-value"><bdi class="ltr">{{ $formatReportMoney($expensesTotal) }}</bdi></span>
                </td>
                <td>
                    <span class="summary-label">{{ __('reports.summary.net_profit') }}</span>
                    <span class="summary-value"><bdi class="ltr">{{ $formatReportMoney($netProfit) }}</bdi></span>
                </td>
            </tr>
        </table>
    @endif

    <h2>{{ __('reports.pdf.metadata') }}</h2>
    <table>
        <tr>
            <th>{{ __('reports.filters.building') }}</th>
            <td>{{ $filters['building_label'] }}</td>
            <th>{{ __('reports.filters.unit') }}</th>
            <td><bdi class="ltr">{{ $filters['unit_label'] }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('reports.filters.tenant') }}</th>
            <td>{{ $filters['tenant_label'] }}</td>
            <th>{{ __('tenants.fields.phone') }}</th>
            <td><bdi class="ltr">{{ $filters['tenant_phone'] ?: $na }}</bdi></td>
        </tr>
        @if($type === 'unit-statement')
        <tr>
            <th>{{ __('reports.columns.contract') }}</th>
            <td><bdi class="ltr">{{ $statementContractLabel }}</bdi></td>
            <th>{{ __('reports.filters.unit') }}</th>
            <td><bdi class="ltr">{{ $filters['unit_label'] }}</bdi></td>
        </tr>
        @endif
        <tr>
            <th>{{ __('reports.filters.from') }}</th>
            <td><bdi class="ltr">{{ $filters['from_date'] }}</bdi></td>
            <th>{{ __('reports.filters.to') }}</th>
            <td><bdi class="ltr">{{ $filters['to_date'] }}</bdi></td>
        </tr>
    </table>

    <h2>{{ __('reports.pdf.totals') }}</h2>
    <table>
        <tr>
            @foreach($totals as $key => $value)
                <th class="text-end">{{ __('reports.columns.'.$key) }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($totals as $value)
                <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($value) }}</bdi></td>
            @endforeach
        </tr>
    </table>

    <h2>{{ __('reports.pdf.rows') }}</h2>
    @if(($type === 'unit-statement' ? $statementRowsForPdf : $rows)->isEmpty())
        <p class="empty">{{ __('reports.pdf.no_data') }}</p>
    @elseif($type === 'building-income')
        <table>
            <tr>
                <th>{{ __('reports.columns.building') }}</th>
                <th class="text-end">{{ __('reports.columns.income') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->income) }}</bdi></td>
                </tr>
            @endforeach
        </table>
    @elseif($type === 'unit-statement')
        <table>
            <tr>
                <th class="text-center">{{ __('reports.columns.due_date') }}</th>
                <th>{{ __('reports.columns.tenant') }}</th>
                <th>{{ __('reports.columns.building') }}</th>
                <th class="text-center">{{ __('reports.columns.unit') }}</th>
                <th class="text-center">{{ __('reports.columns.contract') }}</th>
                <th class="text-end">{{ __('reports.columns.amount_due') }}</th>
                <th class="text-end">{{ __('reports.columns.amount_paid') }}</th>
                <th class="text-end">{{ __('reports.columns.remaining_amount') }}</th>
                <th class="text-center">{{ __('reports.columns.paid_date') }}</th>
                <th class="text-center">{{ __('reports.columns.status') }}</th>
            </tr>
            @foreach($statementRowsForPdf as $row)
                <tr>
                    <td class="text-center"><bdi class="ltr">{{ $row->due_date->toDateString() }}</bdi></td>
                    <td>{{ $row->contract?->tenant?->full_name ?? $na }}</td>
                    <td>{{ $row->contract?->unit?->building?->name ?? $na }}</td>
                    <td class="text-center"><bdi class="ltr">{{ $row->contract?->unit?->unit_number ?? $na }}</bdi></td>
                    <td class="text-center"><bdi class="ltr">{{ $row->contract?->contract_number ?? $na }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->amount_due) }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->amount_paid) }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->remaining_amount) }}</bdi></td>
                    <td class="text-center"><bdi class="ltr">{{ $row->payment_date?->toDateString() ?? $na }}</bdi></td>
                    <td class="text-center">{{ __('payments.statuses.'.$row->display_status_key) }}</td>
                </tr>
            @endforeach
        </table>
    @elseif($type === 'expenses')
        <table>
            <tr>
                <th class="text-center">{{ __('reports.columns.date') }}</th>
                <th>{{ __('reports.columns.building') }}</th>
                <th class="text-center">{{ __('reports.columns.unit') }}</th>
                <th>{{ __('reports.columns.category') }}</th>
                <th class="text-end">{{ __('reports.columns.amount') }}</th>
                <th class="text-center">{{ __('reports.columns.status') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td class="text-center"><bdi class="ltr">{{ $row->expense_date->toDateString() }}</bdi></td>
                    <td>{{ $row->building?->name ?? $na }}</td>
                    <td class="text-center"><bdi class="ltr">{{ $row->unit?->unit_number ?? $na }}</bdi></td>
                    <td>{{ __('expenses.categories.'.$row->category) }}</td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->amount) }}</bdi></td>
                    <td class="text-center">{{ $row->voided_at ? __('expenses.lifecycle.voided') : __('expenses.lifecycle.active') }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <table>
            <tr>
                <th class="text-center">{{ __('reports.columns.due_date') }}</th>
                <th>{{ __('reports.columns.tenant') }}</th>
                <th class="text-center">{{ __('reports.columns.unit') }}</th>
                <th class="text-center">{{ __('reports.columns.contract') }}</th>
                <th class="text-end">{{ __('reports.columns.amount_due') }}</th>
                <th class="text-end">{{ __('reports.columns.amount_paid') }}</th>
                <th class="text-end">{{ __('reports.columns.remaining_amount') }}</th>
                <th>{{ __('reports.columns.method') }}</th>
                <th class="text-center">{{ __('reports.columns.status') }}</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td class="text-center"><bdi class="ltr">{{ $row->due_date->toDateString() }}</bdi></td>
                    <td>{{ $row->contract?->tenant?->full_name ?? $na }}</td>
                    <td class="text-center"><bdi class="ltr">{{ $row->contract?->unit?->unit_number ?? $na }}</bdi><br>{{ $row->contract?->unit?->building?->name ?? $na }}</td>
                    <td class="text-center"><bdi class="ltr">{{ $row->contract?->contract_number ?? $na }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->amount_due) }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->amount_paid) }}</bdi></td>
                    <td class="text-end"><bdi class="ltr">{{ $formatReportMoney($row->remaining_amount ?? ($row->amount_due - $row->amount_paid)) }}</bdi></td>
                    <td>{{ $row->payment_method ? __('payments.methods.'.$row->payment_method) : $na }}</td>
                    <td class="text-center">{{ __('payments.statuses.'.($row->display_status_key ?? $row->status)) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
