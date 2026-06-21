<?php

return [
    'title' => 'Tenants',
    'add' => 'Add tenant',
    'edit' => 'Edit tenant',
    'search_placeholder' => 'Search tenants',

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
        'cannot_delete_with_contracts' => 'A tenant linked to contracts cannot be deleted.',
    ],
];
