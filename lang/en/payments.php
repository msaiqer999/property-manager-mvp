<?php

return [
    'title' => 'Payments',
    'payment' => 'Payment',
    'description' => 'Payments are generated from contracts and can be recorded when rent is collected.',
    'all_statuses' => 'All statuses',
    'filter' => 'Filter',
    'overdue' => 'Overdue',
    'view_receipt' => 'View receipt',
    'record_payment' => 'Record payment',
    'download_receipt_pdf' => 'Download receipt PDF',
    'not_available' => 'N/A',

    'columns' => [
        'due_date' => 'Due date',
        'tenant' => 'Tenant',
        'unit' => 'Unit',
        'contract' => 'Contract',
        'amount' => 'Amount',
        'status' => 'Status',
        'paid_date' => 'Paid date',
        'action' => 'Action',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'partial' => 'Partial',
        'overdue' => 'Overdue',
    ],

    'methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    'form' => [
        'due' => 'Due',
        'amount_paid' => 'Amount paid',
        'payment_date' => 'Payment date',
        'method' => 'Method',
        'proof_image' => 'Proof image',
        'notes' => 'Notes',
        'save' => 'Save',
    ],

    'show' => [
        'due' => 'Due',
        'amount_due' => 'Amount due',
        'paid' => 'Paid',
        'status' => 'Status',
    ],
];
