<!doctype html>
<html>
<body>
    <h1>Payment Receipt #{{ $payment->id }}</h1>
    <p>Tenant: {{ $payment->contract->tenant->full_name }}</p>
    <p>Contract: {{ $payment->contract->contract_number }}</p>
    <p>Due date: {{ $payment->due_date->toDateString() }}</p>
    <p>Payment date: {{ optional($payment->payment_date)->toDateString() }}</p>
    <p>Amount paid: {{ number_format($payment->amount_paid, 2) }}</p>
    <p>Method: {{ $payment->payment_method }}</p>
</body>
</html>
