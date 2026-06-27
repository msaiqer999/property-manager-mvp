<?php

return [
    'title' => 'Buildings',
    'add' => 'Add building',
    'edit' => 'Edit building',

    'fields' => [
        'name' => 'Name',
        'location' => 'Location',
        'description' => 'Description',
        'action' => 'Action',
    ],

    'sections' => [
        'units' => 'Units',
    ],

    'lifecycle' => [
        'cannot_archive_with_history' => 'A building containing units or financial history cannot be archived.',
    ],
];
