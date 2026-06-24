<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('contracts.pdf.title') }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #111827; font-size: 12px; line-height: 1.6; }
        h1 { margin: 0 0 16px; font-size: 22px; }
        h2 { margin: 22px 0 8px; font-size: 15px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: inherit; width: 32%; }
        .muted { color: #4b5563; }
        .amount, .ltr { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ __('contracts.pdf.title') }}</h1>
    <p class="muted">{{ __('contracts.pdf.generated_at') }} <bdi dir="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></p>

    <h2>{{ __('contracts.pdf.contract_details') }}</h2>
    <table>
        <tr>
            <th>{{ __('contracts.columns.contract_number') }}</th>
            <td><bdi dir="ltr">{{ $contract->contract_number }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.status') }}</th>
            <td>{{ __('contracts.statuses.'.$contract->status) }}</td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.start_date') }}</th>
            <td><bdi dir="ltr">{{ $contract->start_date->toDateString() }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.end_date') }}</th>
            <td><bdi dir="ltr">{{ $contract->end_date->toDateString() }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.rent_per_period') }}</th>
            <td><bdi dir="ltr">{{ number_format((float) $contract->rent_amount, 2) }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.frequency') }}</th>
            <td>{{ __('contracts.frequencies.'.$contract->payment_frequency) }}</td>
        </tr>
        <tr>
            <th>{{ __('contracts.pdf.deposit') }}</th>
            <td><bdi dir="ltr">{{ number_format((float) $contract->deposit_amount, 2) }}</bdi></td>
        </tr>
    </table>

    <h2>{{ __('contracts.pdf.parties') }}</h2>
    <table>
        <tr>
            <th>{{ __('contracts.columns.tenant') }}</th>
            <td>{{ $contract->tenant->full_name }}</td>
        </tr>
        <tr>
            <th>{{ __('contracts.pdf.tenant_email') }}</th>
            <td><bdi dir="ltr">{{ $contract->tenant->email ?? __('contracts.pdf.not_available') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.pdf.tenant_phone') }}</th>
            <td><bdi dir="ltr">{{ $contract->tenant->phone ?? __('contracts.pdf.not_available') }}</bdi></td>
        </tr>
        <tr>
            <th>{{ __('contracts.columns.unit') }}</th>
            <td><bdi dir="ltr">{{ $contract->unit->unit_number }}</bdi> - {{ $contract->unit->building->name }}</td>
        </tr>
    </table>

    <h2>{{ __('contracts.pdf.notes') }}</h2>
    <p>{{ $contract->notes ?: __('contracts.pdf.not_available') }}</p>
</body>
</html>
