<?php

return [
    'title' => 'Users',
    'invite' => 'Invite user',
    'edit' => 'Edit user',

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
    ],

    'roles' => [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'accountant' => 'Accountant',
        'caretaker' => 'Caretaker',
    ],

    'actions' => [
        'deactivate' => 'Deactivate',
        'reactivate' => 'Reactivate',
    ],

    'validation' => [
        'last_active_owner_required' => 'A workspace must have at least one active owner.',
    ],
];
