<?php

return [
    'title' => 'Activity log',

    'actions' => [
        'building' => [
            'created' => 'Building created',
            'updated' => 'Building updated',
            'deleted' => 'Building deleted',
        ],
        'tenant' => [
            'created' => 'Tenant created',
            'updated' => 'Tenant updated',
            'deleted' => 'Tenant deleted',
        ],
        'contract' => [
            'created' => 'Contract created',
            'updated' => 'Contract updated',
        ],
        'expense' => [
            'created' => 'Expense created',
            'updated' => 'Expense updated',
        ],
        'payment' => [
            'recorded' => 'Payment recorded',
        ],
        'unit' => [
            'created' => 'Unit created',
            'updated' => 'Unit updated',
            'status_changed' => 'Unit status changed',
            'deleted' => 'Unit deleted',
        ],
        'user' => [
            'role_changed' => 'User role changed',
            'deactivated' => 'User deactivated',
            'reactivated' => 'User reactivated',
        ],
    ],
];
