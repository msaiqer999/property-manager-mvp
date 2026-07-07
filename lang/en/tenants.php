<?php

return [
    'title' => 'Tenants',
    'add' => 'Add tenant',
    'edit' => 'Edit tenant',
    'search_placeholder' => 'Search tenants',

    'actions' => [
        'save_and_create_contract' => 'Save and create contract',
    ],

    'fields' => [
        'full_name' => 'Full name',
        'phone' => 'Phone',
        'email' => 'Email',
        'id_number' => 'ID number',
        'nationality' => 'Nationality',
        'notes' => 'Notes',
    ],

    'sections' => [
        'contracts' => 'Contracts',
    ],

    'lifecycle' => [
        'active' => 'Active',
        'all' => 'All',
        'already_archived' => 'This tenant has already been archived.',
        'archive' => 'Archive tenant',
        'archive_reason' => 'Archive reason',
        'archived' => 'Archived',
        'archived_at' => 'Archived at',
        'archived_by' => 'Archived by',
        'cannot_archive_with_current_contract' => 'A tenant with a current or future active contract cannot be archived.',
        'cannot_delete_archived' => 'Archived tenants cannot be deleted.',
        'cannot_delete_with_contracts' => 'A tenant linked to contracts cannot be deleted.',
        'cannot_edit_archived' => 'Archived tenants cannot be edited.',
        'cannot_renew_archived' => 'Archived tenants cannot be renewed into a new contract term.',
        'cannot_select_archived' => 'Archived tenants cannot be selected for a new contract.',
        'confirm_archive' => 'Archive this tenant? Existing contracts, payments, reports, and activity history will remain available.',
    ],

    'attributes' => [
        'archive_reason' => 'archive reason',
    ],
];
