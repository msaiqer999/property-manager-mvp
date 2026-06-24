<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('payments.pdf.title') }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #111827; font-size: 12px; line-height: 1.6; }
        h1 { margin: 0 0 16px; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: inherit; width: 32%; }
        .muted { color: #4b5563; }
        .ltr { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ __('payments.pdf.title') }}</h1>
    <p class="muted">{{ __('payments.pdf.generated_at') }} <bdi dir="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></p>

    <table>
        <tr>
            <th>{{ __('payments.pdf.receipt_number') }}</th>
            <td><bdi dir="ltr">{{ $payment->id }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.tenant') }}</th>
            <td>{{ $payment->contract->tenant->full_name }}</td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.contract') }}</th>
            <td><bdi dir="ltr">{{ $payment->contract->contract_number }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.unit') }}</th>
            <td><bdi dir="ltr">{{ $payment->contract->unit->unit_number }}</bdi> - {{ $payment->contract->unit->building->name }}</td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.due_date') }}</th>
            <td><bdi dir="ltr">{{ $payment->due_date->toDateString() }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.paid_date') }}</th>
            <td><bdi dir="ltr">{{ $payment->payment_date?->toDateString() ?? __('payments.not_available') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.show.amount_due') }}</th>
            <td><bdi dir="ltr">{{ number_format((float) $payment->amount_due, 2) }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.show.paid') }}</th>
            <td><bdi dir="ltr">{{ number_format((float) $payment->amount_paid, 2) }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('payments.form.method') }}</th>
            <td>{{ $payment->payment_method ? __('payments.methods.'.$payment->payment_method) : __('payments.not_available') }}</td>
        </tr>
        <tr>
            <th>{{ __('payments.columns.status') }}</th>
            <td>{{ __('payments.statuses.'.$payment->status) }}</td>
        </tr>
    </table>
</body>
</html>
