<!doctype html>
<html>
<body>
    <h1>{{ ucwords(str_replace('-', ' ', $type)) }}</h1>
    <p>Income: {{ number_format($income, 2) }}</p>
    <p>Expenses: {{ number_format($expensesTotal, 2) }}</p>
    <p>Net profit: {{ number_format($netProfit, 2) }}</p>
    <h2>Rows</h2>
    <table width="100%" border="1" cellspacing="0" cellpadding="4">
        <tr><th>Item</th><th>Details</th><th>Amount</th><th>Status</th></tr>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row->name ?? $row->unit_number ?? $row->category ?? ($row->due_date instanceof \Carbon\CarbonInterface ? $row->due_date->toDateString() : ($row->due_date ?? '')) }}</td>
                <td>{{ data_get($row, 'building.name') ?? data_get($row, 'contract.tenant.full_name') ?? '' }}</td>
                <td>{{ number_format($row->income ?? $row->amount ?? $row->amount_due ?? 0, 2) }}</td>
                <td>{{ $row->status ?? '' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
