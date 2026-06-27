<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ \App\Support\SupportedLocales::direction(app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('payments.pdf.title') }}</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #111827;
            font-size: 13.5px;
            line-height: 1.7;
            background: #ffffff;
        }

        .receipt {
            width: 100%;
        }

        .header {
            border-bottom: 3px solid #0f172a;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }

        h1 {
            margin: 0;
            color: #0f172a;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.25;
        }

        .generated-at {
            margin: 6px 0 0;
            color: #4b5563;
            font-size: 12px;
        }

        .summary {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 16px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .summary table,
        .details {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            padding: 3px 0;
            vertical-align: top;
        }

        .summary-label {
            width: 24%;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .summary-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
        }

        .details {
            margin-top: 8px;
            border: 1px solid #cbd5e1;
            table-layout: fixed;
        }

        .details th,
        .details td {
            border-bottom: 1px solid #e2e8f0;
            padding: 11px 12px;
            vertical-align: middle;
        }

        .details th {
            width: 34%;
            background: #f1f5f9;
            color: #334155;
            font-size: 12.5px;
            font-weight: 700;
            text-align: inherit;
        }

        .details td {
            color: #111827;
            font-size: 13.5px;
        }

        .amount {
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
        }

        .status {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 3px 10px;
            background: #f8fafc;
            font-weight: 700;
        }

        .note {
            margin-top: 14px;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 10px 12px;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 12.5px;
            font-weight: 700;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>{{ __('payments.pdf.title') }}</h1>
            <p class="generated-at">{{ __('payments.pdf.generated_at') }} <bdi class="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></p>
        </div>

        <div class="summary">
            <table>
                <tr>
                    <td class="summary-label">{{ __('payments.pdf.receipt_number') }}</td>
                    <td class="summary-value"><bdi class="ltr">{{ $payment->id }}</bdi></td>
                    <td class="summary-label">{{ __('payments.columns.status') }}</td>
                    <td class="summary-value">{{ __('payments.statuses.'.$payment->receipt_status_key) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">{{ __('payments.columns.tenant') }}</td>
                    <td class="summary-value">{{ $payment->contract->tenant->full_name }}</td>
                    <td class="summary-label">{{ __('payments.columns.contract') }}</td>
                    <td class="summary-value"><bdi class="ltr">{{ $payment->contract->contract_number }}</bdi></td>
                </tr>
            </table>
        </div>

        <table class="details">
            <tr>
                <th>{{ __('payments.pdf.receipt_number') }}</th>
                <td><bdi class="ltr">{{ $payment->id }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.tenant') }}</th>
                <td>{{ $payment->contract->tenant->full_name }}</td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.contract') }}</th>
                <td><bdi class="ltr">{{ $payment->contract->contract_number }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.unit') }}</th>
                <td><bdi class="ltr">{{ $payment->contract->unit->unit_number }}</bdi> - {{ $payment->contract->unit->building->name }}</td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.due_date') }}</th>
                <td><bdi class="ltr">{{ $payment->due_date->toDateString() }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.paid_date') }}</th>
                <td><bdi class="ltr">{{ $payment->payment_date?->toDateString() ?? __('payments.not_available') }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.show.amount_due') }}</th>
                <td class="amount"><bdi class="ltr">{{ number_format((float) $payment->amount_due, 2) }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.show.paid') }}</th>
                <td class="amount"><bdi class="ltr">{{ number_format((float) $payment->amount_paid, 2) }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.show.remaining') }}</th>
                <td class="amount"><bdi class="ltr">{{ number_format((float) $payment->remaining_amount, 2) }}</bdi></td>
            </tr>
            <tr>
                <th>{{ __('payments.form.method') }}</th>
                <td>{{ $payment->payment_method ? __('payments.methods.'.$payment->payment_method) : __('payments.not_available') }}</td>
            </tr>
            <tr>
                <th>{{ __('payments.columns.status') }}</th>
                <td><span class="status">{{ __('payments.statuses.'.$payment->receipt_status_key) }}</span></td>
            </tr>
        </table>

        @if($payment->receipt_status_key === 'partial')
            <div class="note">{{ __('payments.receipt.partial_note') }}</div>
        @endif
    </div>
</body>
</html>
