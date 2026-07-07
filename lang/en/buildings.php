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

    'empty_units_guidance' => 'This building is ready. Add multiple units at once, or add a single unit if you only need one.',

    'lifecycle' => [
        'cannot_archive_with_history' => 'A building containing units or financial history cannot be archived.',
    ],
];
