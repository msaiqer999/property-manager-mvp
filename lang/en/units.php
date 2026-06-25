<?php

return [
    'title' => 'Units',
    'add' => 'Add unit',
    'edit' => 'Edit unit',

    'filters' => [
        'all_buildings' => 'All buildings',
        'all_statuses' => 'All statuses',
    ],

    'fields' => [
        'unit' => 'Unit',
        'building' => 'Building',
        'status' => 'Status',
        'rent' => 'Rent',
        'unit_number' => 'Unit number',
        'type' => 'Type',
        'size' => 'Size',
        'rooms' => 'Rooms',
        'notes' => 'Notes',
    ],

    'labels' => [
        'building' => 'Building:',
        'status' => 'Status:',
        'type' => 'Type:',
        'rent' => 'Rent:',
    ],

    'statuses' => [
        'vacant' => 'Vacant',
        'rented' => 'Rented',
        'maintenance' => 'Maintenance',
    ],

    'types' => [
        'apartment' => 'Apartment',
        'shop' => 'Shop',
        'office' => 'Office',
        'warehouse' => 'Warehouse',
        'villa' => 'Villa',
        'chalet' => 'Chalet',
        'other' => 'Other',
    ],

    'bulk' => [
        'add_multiple' => 'Add multiple units',
        'title' => 'Add multiple units',
        'description' => 'Generate a unit list for :building, review every row, then create them.',
        'preview_title' => 'Preview units',
        'preview_description' => 'Edit any row before creating units in :building.',
        'generate_preview' => 'Generate preview',
        'create_units' => 'Create units',
        'prefix' => 'Optional prefix',
        'start_number' => 'Start number',
        'end_number' => 'End number',
        'default_type' => 'Default type',
        'default_rent' => 'Default rent',
        'default_rooms' => 'Default rooms',
        'default_size' => 'Default size',
        'default_status' => 'Default status',
        'default_notes' => 'Default notes',
        'created_success' => ':count units created successfully.',
        'preview_expired' => 'The preview has expired. Please generate the preview again.',
        'validation' => [
            'duplicate_in_request' => 'Duplicate unit number in the preview: :unit.',
            'duplicate_existing' => 'Unit number already exists in this building: :unit.',
            'range_too_large' => 'You can create up to 200 units at a time.',
        ],
        'attributes' => [
            'prefix' => 'prefix',
            'start_number' => 'start number',
            'end_number' => 'end number',
            'type' => 'type',
            'rent_amount' => 'rent amount',
            'rooms' => 'rooms',
            'size' => 'size',
            'status' => 'status',
            'notes' => 'notes',
            'units' => 'units',
            'units.*.unit_number' => 'unit number',
            'units.*.type' => 'type',
            'units.*.rent_amount' => 'rent amount',
            'units.*.rooms' => 'rooms',
            'units.*.size' => 'size',
            'units.*.status' => 'status',
            'units.*.notes' => 'notes',
        ],
    ],

    'lifecycle' => [
        'cannot_archive_with_history' => 'A unit linked to contracts or expenses cannot be archived.',
    ],
];
