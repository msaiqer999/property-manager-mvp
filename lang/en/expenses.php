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
        'description' => 'Record operational expenses with the right building, optional unit, date, amount, and invoice attachment.',
        'building' => 'Building',
        'building_help' => 'Choose the building this expense belongs to.',
        'unit' => 'Unit',
        'unit_help' => 'Optional. Select a unit only when the expense belongs to a specific unit.',
        'optional_unit' => 'No specific unit',
        'category' => 'Category',
        'category_help' => 'Pick the closest operational category for filtering and reports.',
        'amount' => 'Amount',
        'amount_help' => 'Enter the expense amount exactly as it should appear in reports.',
        'date' => 'Date',
        'date_help' => 'Use the invoice or payment date for accurate monthly reports.',
        'invoice' => 'Invoice',
        'invoice_help' => 'Optional image attachment. Storage and download behavior stay private.',
        'notes' => 'Notes',
        'save' => 'Save',
    ],

    'show' => [
        'details' => 'Expense details',
        'building' => 'Building',
        'unit' => 'Unit',
        'category' => 'Category',
        'amount' => 'Amount',
        'date' => 'Date',
        'notes' => 'Notes',
        'invoice' => 'Invoice',
        'invoice_attachment' => 'Invoice attachment',
        'invoice_available' => 'An invoice image is attached to this expense.',
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
