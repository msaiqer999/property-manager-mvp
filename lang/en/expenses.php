<?php

return [
    'title' => 'Expenses',
    'expense' => 'Expense',
    'add' => 'Add expense',
    'edit' => 'Edit expense',
    'view' => 'View',
    'edit_action' => 'Edit',
    'download_invoice' => 'Download invoice',
    'not_available' => 'N/A',
    'empty' => 'No expenses found.',

    'categories' => [
        'maintenance' => 'Maintenance',
        'electricity' => 'Electricity',
        'water' => 'Water',
        'cleaning' => 'Cleaning',
        'security' => 'Security',
        'management' => 'Management',
        'other' => 'Other',
    ],

    'form' => [
        'building' => 'Building',
        'unit' => 'Unit',
        'category' => 'Category',
        'amount' => 'Amount',
        'date' => 'Date',
        'invoice' => 'Invoice',
        'notes' => 'Notes',
        'save' => 'Save',
    ],

    'show' => [
        'building' => 'Building',
        'unit' => 'Unit',
        'category' => 'Category',
        'amount' => 'Amount',
        'date' => 'Date',
        'notes' => 'Notes',
        'invoice' => 'Invoice',
        'status' => 'Status',
        'action' => 'Action',
    ],

    'lifecycle' => [
        'active' => 'Active',
        'all' => 'All',
        'already_voided' => 'This financial expense record has already been voided.',
        'cannot_delete_financial_record' => 'Financial expense records cannot be deleted.',
        'cannot_edit_voided' => 'Voided financial expense records cannot be edited.',
        'confirm_void' => 'Void this financial expense record? The amount, date, invoice, and notes will remain unchanged.',
        'void' => 'Void financial record',
        'void_reason' => 'Void reason',
        'voided' => 'Voided',
        'voided_at' => 'Voided at',
        'voided_by' => 'Voided by',
        'voided_filter' => 'Voided',
    ],

    'filters' => [
        'all_buildings' => 'All buildings',
        'all_units' => 'All units',
        'all_categories' => 'All categories',
    ],

    'attributes' => [
        'void_reason' => 'void reason',
    ],
];
