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
    'download_proof' => 'Download proof',
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
        'cancelled' => 'Cancelled',
    ],

    'methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    'form' => [
        'summary' => 'Payment summary',
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

    'pdf' => [
        'title' => 'Payment Receipt',
        'generated_at' => 'Generated at',
        'receipt_number' => 'Receipt number',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'Cancelled due to contract termination',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'The recorded paid amount cannot be reduced.',
        'cannot_record_cancelled' => 'Cancelled payments cannot be recorded or changed.',
        'receipt_unavailable_without_recorded_money' => 'A receipt is unavailable because no money has been recorded for this payment.',
    ],
];
