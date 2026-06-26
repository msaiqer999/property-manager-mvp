@php
    $pdfLocale = in_array(app()->getLocale(), ['en', 'ar'], true) ? app()->getLocale() : 'en';
    $isRtl = $pdfLocale === 'ar';
    $contract->loadMissing(['organization', 'tenant', 'unit.building', 'payments']);
    $tenant = $contract->tenant;
    $unit = $contract->unit;
    $building = $unit->building;
    $payments = $contract->payments->sortBy('due_date')->values();
    $na = __('contracts.pdf.not_available', [], $pdfLocale);
    $t = fn (string $key, array $replace = []) => __("contracts.{$key}", $replace, $pdfLocale);
    $pt = fn (string $key, array $replace = []) => __("payments.{$key}", $replace, $pdfLocale);
@endphp
<!doctype html>
<html lang="{{ $pdfLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $t('pdf.title') }}</title>
    <style>
        @page {
            margin: 8mm 9mm;
        }

        body {
            font-family: {{ $isRtl ? 'xbriyaz, dejavusans' : 'dejavusans' }}, sans-serif;
            color: #111827;
            font-size: {{ $isRtl ? '11.2px' : '10.8px' }};
            line-height: {{ $isRtl ? '1.32' : '1.38' }};
            background: #ffffff;
        }

        .hero {
            background: #0f172a;
            color: #ffffff;
            padding: 9px 12px;
            margin-bottom: 6px;
        }

        .hero-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
        }

        .hero-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .hero-meta {
            width: 34%;
            color: #dbeafe;
            font-size: 9px;
            text-align: {{ $isRtl ? 'left' : 'right' }};
        }

        h1 {
            margin: 0;
            color: #ffffff;
            font-size: 23px;
            line-height: 1.05;
        }

        .subtitle {
            color: #dbeafe;
            font-size: 9.5px;
            margin-top: 2px;
        }

        .title-alt {
            margin-top: 1px;
            color: #e5e7eb;
            font-size: 12px;
            font-weight: 700;
        }

        .muted {
            color: #64748b;
            font-size: 9px;
        }

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            margin: 0 0 5px;
            table-layout: fixed;
        }

        .summary td {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 4px 5px;
            vertical-align: top;
        }

        .summary-label {
            color: #64748b;
            display: block;
            font-size: 8.3px;
            margin-bottom: 1px;
        }

        .summary-value {
            color: #0f172a;
            display: block;
            font-size: 9.8px;
            font-weight: 700;
        }

        .section {
            border: 1px solid #d8dee8;
            margin-top: 5px;
            padding: 0 0 3px;
        }

        h2 {
            margin: 0 0 3px;
            background: #f1f5f9;
            border-bottom: 1px solid #d8dee8;
            padding: 3px 6px;
            color: #0f172a;
            font-size: 10.8px;
        }

        .info-table,
        .schedule,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            table-layout: fixed;
        }

        .info-table th,
        .info-table td,
        .schedule th,
        .schedule td,
        .signature-table th,
        .signature-table td {
            border: 1px solid #d1d5db;
            padding: 3px 5px;
            vertical-align: top;
        }

        .info-table th {
            width: 30%;
            background: #f8fafc;
            color: #374151;
            font-size: 9.5px;
            text-align: inherit;
        }

        .schedule {
            margin: 0;
        }

        .schedule th {
            background: #1e293b;
            color: #ffffff;
            border-color: #1e293b;
            width: auto;
            font-size: 8.8px;
        }

        .schedule td {
            font-size: 8.8px;
        }

        .schedule tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .terms {
            margin: 0 7px 0;
            padding-{{ $isRtl ? 'right' : 'left' }}: 14px;
        }

        .terms li {
            margin-bottom: 2px;
        }

        .terms-note {
            border-top: 1px solid #e2e8f0;
            color: #475569;
            font-size: 8.8px;
            margin: 4px 7px 0;
            padding-top: 3px;
        }

        .notes-text {
            margin: 0 7px;
        }

        .signature-table {
            border-collapse: separate;
            border-spacing: 5px 0;
            margin-top: 0;
        }

        .signature-table td {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 8px;
            width: 50%;
            vertical-align: top;
        }

        .signature-table td {
            height: 96px;
        }

        .signature-label {
            color: #475569;
            display: block;
            font-size: 8.8px;
            line-height: 1.5;
            margin: 8px 0 10px;
            padding-bottom: 5px;
        }

        .signature-blank {
            border-bottom: 1px solid #334155;
            height: 8px;
            margin-bottom: 8px;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
            white-space: nowrap;
        }

        .amount {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="hero">
        <table class="hero-table">
            <tr>
                <td>
                    <div style="font-size: 11px; font-weight: 700;">{{ $contract->organization?->name ?? $na }}</div>
                    <h1>{{ $t('pdf.title') }}</h1>
                    <div class="title-alt">{{ $t('pdf.title_alt') }}</div>
                </td>
                <td class="hero-meta">
                    <div>{{ $t('columns.contract_number') }}</div>
                    <div style="font-size: 12px; font-weight: 700;"><bdi class="ltr">{{ $contract->contract_number }}</bdi></div>
                    <div style="margin-top: 4px;">{{ $t('pdf.generated_at') }}</div>
                    <div><bdi class="ltr">{{ now()->format('Y-m-d H:i') }}</bdi></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="summary-label">{{ $t('columns.contract_number') }}</span>
                <span class="summary-value"><bdi class="ltr">{{ $contract->contract_number }}</bdi></span>
            </td>
            <td>
                <span class="summary-label">{{ $t('columns.status') }}</span>
                <span class="summary-value">{{ $t('statuses.'.$contract->status) }}</span>
            </td>
            <td>
                <span class="summary-label">{{ $t('columns.rent_per_period') }}</span>
                <span class="summary-value"><bdi class="ltr">{{ number_format((float) $contract->rent_amount, 2) }}</bdi></span>
            </td>
            <td>
                <span class="summary-label">{{ $t('columns.start_date') }}</span>
                <span class="summary-value"><bdi class="ltr">{{ $contract->start_date->toDateString() }}</bdi></span>
            </td>
            <td>
                <span class="summary-label">{{ $t('columns.end_date') }}</span>
                <span class="summary-value"><bdi class="ltr">{{ $contract->end_date->toDateString() }}</bdi></span>
            </td>
            <td>
                <span class="summary-label">{{ $t('columns.frequency') }}</span>
                <span class="summary-value">{{ $t('frequencies.'.$contract->payment_frequency) }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>{{ $t('pdf.owner_details') }}</h2>
        <table class="info-table">
            <tr>
                <th>{{ $t('pdf.organization_name') }}</th>
                <td>{{ $contract->organization?->name ?? $na }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ $t('pdf.tenant_details') }}</h2>
        <table class="info-table">
            <tr>
                <th>{{ $t('columns.tenant') }}</th>
                <td>{{ $tenant->full_name }}</td>
                <th>{{ $t('pdf.tenant_phone') }}</th>
                <td><bdi class="ltr">{{ $tenant->phone ?: $na }}</bdi></td>
            </tr>
            <tr>
                <th>{{ $t('pdf.tenant_email') }}</th>
                <td><bdi class="ltr">{{ $tenant->email ?: $na }}</bdi></td>
                <th>{{ $t('pdf.tenant_id_number') }}</th>
                <td><bdi class="ltr">{{ $tenant->id_number ?: $na }}</bdi></td>
            </tr>
            <tr>
                <th>{{ $t('pdf.tenant_nationality') }}</th>
                <td colspan="3">{{ $tenant->nationality ?: $na }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ $t('pdf.property_details') }}</h2>
        <table class="info-table">
            <tr>
                <th>{{ $t('pdf.building_name') }}</th>
                <td>{{ $building->name }}</td>
                <th>{{ $t('pdf.building_location') }}</th>
                <td>{{ $building->location ?: $na }}</td>
            </tr>
            <tr>
                <th>{{ $t('pdf.unit_number') }}</th>
                <td><bdi class="ltr">{{ $unit->unit_number }}</bdi></td>
                <th>{{ $t('pdf.unit_type') }}</th>
                <td>{{ $unit->type ? __('units.types.'.$unit->type, [], $pdfLocale) : $na }}</td>
            </tr>
            <tr>
                <th>{{ $t('pdf.unit_size') }}</th>
                <td><bdi class="ltr">{{ $unit->size ?: $na }}</bdi></td>
                <th>{{ $t('pdf.unit_rooms') }}</th>
                <td><bdi class="ltr">{{ $unit->rooms ?: $na }}</bdi></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ $t('pdf.financial_details') }}</h2>
        <table class="info-table">
            <tr>
                <th>{{ $t('columns.rent_per_period') }}</th>
                <td class="amount"><bdi class="ltr">{{ number_format((float) $contract->rent_amount, 2) }}</bdi></td>
                <th>{{ $t('columns.frequency') }}</th>
                <td>{{ $t('frequencies.'.$contract->payment_frequency) }}</td>
            </tr>
            <tr>
                <th>{{ $t('pdf.deposit') }}</th>
                <td class="amount"><bdi class="ltr">{{ number_format((float) $contract->deposit_amount, 2) }}</bdi></td>
                <th>{{ $t('pdf.payment_schedule') }}</th>
                <td>{{ $t('frequencies.'.$contract->payment_frequency) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>{{ $t('pdf.payment_schedule') }}</h2>
        <table class="schedule">
            <thead>
                <tr>
                    <th>{{ $t('pdf.payment_number') }}</th>
                    <th>{{ $t('pdf.payment_due_date') }}</th>
                    <th>{{ $t('pdf.payment_amount_due') }}</th>
                    <th>{{ $t('pdf.payment_status') }}</th>
                    <th>{{ $t('pdf.payment_date') }}</th>
                    <th>{{ $t('pdf.payment_amount_paid') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td><bdi class="ltr">{{ $loop->iteration }}</bdi></td>
                        <td><bdi class="ltr">{{ $payment->due_date->toDateString() }}</bdi></td>
                        <td class="amount"><bdi class="ltr">{{ number_format((float) $payment->amount_due, 2) }}</bdi></td>
                        <td>{{ $pt('statuses.'.$payment->status) }}</td>
                        <td><bdi class="ltr">{{ $payment->payment_date?->toDateString() ?? $na }}</bdi></td>
                        <td class="amount"><bdi class="ltr">{{ number_format((float) $payment->amount_paid, 2) }}</bdi></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">{{ $na }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>{{ $t('pdf.basic_terms') }}</h2>
        <ol class="terms">
            <li>{{ $t('pdf.terms.rent_schedule') }}</li>
            <li>{{ $t('pdf.terms.unit_care') }}</li>
            <li>{{ $t('pdf.terms.maintenance_notice') }}</li>
            <li>{{ $t('pdf.terms.system_records') }}</li>
            <li>{{ $t('pdf.terms.additional_terms') }}</li>
        </ol>
    </div>

    @if($contract->notes)
        <div class="section">
            <h2>{{ $t('pdf.notes') }}</h2>
            <p class="notes-text">{{ $contract->notes }}</p>
        </div>
    @endif

    <div class="section">
        <h2>{{ $t('pdf.signatures') }}</h2>
        <table class="signature-table">
            <tr>
                <td>
                    <strong>{{ $t('pdf.lessor_signature') }}</strong>
                    <div class="signature-label">{{ $t('pdf.signature') }}</div>
                    <div class="signature-blank"></div>
                    <div class="signature-label">{{ $t('pdf.name') }}</div>
                    <div class="signature-blank"></div>
                    <div class="signature-label">{{ $t('pdf.date') }}</div>
                    <div class="signature-blank"></div>
                </td>
                <td>
                    <strong>{{ $t('pdf.tenant_signature') }}</strong>
                    <div class="signature-label">{{ $t('pdf.signature') }}</div>
                    <div class="signature-blank"></div>
                    <div class="signature-label">{{ $t('pdf.name') }}</div>
                    <div class="signature-blank"></div>
                    <div class="signature-label">{{ $t('pdf.date') }}</div>
                    <div class="signature-blank"></div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
