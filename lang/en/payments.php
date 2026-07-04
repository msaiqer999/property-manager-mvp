<?php

return [
    'title' => 'Payments',
    'payment' => 'Payment',
    'description' => 'Payments are generated from contracts and can be recorded when rent is collected.',
    'all_statuses' => 'All statuses',
    'filter' => 'Filter',
    'overdue' => 'Overdue',
    'follow_up' => 'Follow up',
    'view_receipt' => 'View receipt',
    'record_payment' => 'Record payment',
    'download_proof' => 'Download proof',
    'download_receipt_pdf' => 'Download receipt PDF',
    'not_available' => 'N/A',
    'recorded_success' => 'Payment recorded successfully. You can download the receipt.',

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
        'partial' => 'Partially paid',
        'partial_overdue' => 'Partially paid, balance overdue',
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
        'building' => 'Building',
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
        'remaining' => 'Remaining amount',
        'status' => 'Status',
    ],

    'overdue_summary' => [
        'title' => 'Overdue payment summary',
        'description' => 'Use this context to follow up, then record the payment once it is received.',
        'days_overdue' => '{0} Due today|{1} :count day overdue|[2,*] :count days overdue',
        'phone' => 'Tenant phone',
    ],

    'reminder' => [
        'title' => 'Reminder message',
        'description' => 'Copy this message and send it manually using your preferred channel.',
        'copy' => 'Copy reminder',
        'message' => 'Hello :tenant_name, this is a reminder that rent for unit :unit_number was due on :due_date. The remaining amount is :remaining_amount. Please arrange payment when possible. Thank you.',
    ],

    'follow_ups' => [
        'title' => 'Follow-up history',
        'description' => 'Track manual reminders, tenant replies, and payment promises for this payment.',
        'empty' => 'No follow-up entries have been recorded yet.',
        'type' => 'Follow-up type',
        'note' => 'Follow-up note',
        'promised_date' => 'Promised payment date',
        'promised_amount' => 'Promised amount',
        'created_by' => 'Created by',
        'save' => 'Save follow-up',
        'saved' => 'Follow-up saved.',
        'log_reminder' => 'Log reminder',
        'promise_indicator' => 'Promise',
        'types' => [
            'note' => 'Note',
            'reminder_logged' => 'Reminder logged',
            'promise_to_pay' => 'Promise to pay',
        ],
    ],

    'pdf' => [
        'title' => 'Payment Receipt',
        'generated_at' => 'Generated at',
        'receipt_number' => 'Receipt number',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'Cancelled due to contract termination',
    ],

    'receipt' => [
        'partial_note' => 'This receipt confirms the received amount only. A remaining balance is still due.',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'The recorded paid amount cannot be reduced.',
        'cannot_record_cancelled' => 'Cancelled payments cannot be recorded or changed.',
        'receipt_unavailable_without_recorded_money' => 'A receipt is unavailable because no money has been recorded for this payment.',
    ],
];
