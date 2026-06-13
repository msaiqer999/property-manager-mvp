<!doctype html>
<html>
<body>
    <h1>Rental Contract {{ $contract->contract_number }}</h1>
    <p>Tenant: {{ $contract->tenant->full_name }}</p>
    <p>Unit: {{ $contract->unit->unit_number }} - {{ $contract->unit->building->name }}</p>
    <p>Start: {{ $contract->start_date->toDateString() }}</p>
    <p>End: {{ $contract->end_date->toDateString() }}</p>
    <p>Rent: {{ number_format($contract->rent_amount, 2) }}</p>
    <p>Frequency: {{ str_replace('_', ' ', $contract->payment_frequency) }}</p>
    <p>Deposit: {{ number_format($contract->deposit_amount, 2) }}</p>
    <p>Notes: {{ $contract->notes }}</p>
</body>
</html>
