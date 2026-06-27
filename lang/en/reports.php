<?php

return [
    'title' => 'Reports',

    'summary' => [
        'income' => 'Income',
        'expenses' => 'Expenses',
        'net_profit' => 'Net profit',
    ],

    'filters' => [
        'building' => 'Building',
        'unit' => 'Unit',
        'from' => 'From date',
        'to' => 'To date',
        'apply' => 'Apply',
        'all_buildings' => 'All buildings',
        'all_units' => 'All units',
    ],

    'actions' => [
        'building_income' => 'Export building income PDF',
        'unit_statement' => 'Download unit statement PDF',
        'expenses' => 'Export expenses PDF',
        'overdue' => 'Export overdue payments PDF',
        'net_profit' => 'Export net profit PDF',
        'monthly_summary' => 'Export monthly summary PDF',
    ],

    'types' => [
        'building-income' => 'Building income',
        'unit-statement' => 'Unit statement',
        'expenses' => 'Expenses',
        'overdue' => 'Overdue payments',
        'net-profit' => 'Net profit',
        'monthly-summary' => 'Monthly summary',
    ],

    'columns' => [
        'amount' => 'Amount',
        'amount_due' => 'Amount due',
        'amount_paid' => 'Amount paid',
        'building' => 'Building',
        'category' => 'Category',
        'contract' => 'Contract',
        'contracts' => 'Contracts',
        'date' => 'Date',
        'due_date' => 'Due date',
        'expenses' => 'Expenses',
        'income' => 'Income',
        'method' => 'Method',
        'net_profit' => 'Net profit',
        'remaining_amount' => 'Remaining',
        'status' => 'Status',
        'tenant' => 'Tenant',
        'unit' => 'Unit',
    ],

    'pdf' => [
        'generated_at' => 'Generated at',
        'metadata' => 'Report filters',
        'no_data' => 'No data available for this report.',
        'not_available' => 'N/A',
        'rows' => 'Rows',
        'totals' => 'Totals',
    ],
];
