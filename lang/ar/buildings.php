<?php

return [
    'title' => 'المباني',
    'add' => 'إضافة مبنى',
    'edit' => 'تعديل المبنى',

    'fields' => [
        'name' => 'اسم المبنى',
        'location' => 'الموقع',
        'description' => 'الوصف',
        'action' => 'الإجراء',
    ],

    'sections' => [
        'units' => 'الوحدات',
    ],

    'lifecycle' => [
        'cannot_archive_with_history' => 'لا يمكن أرشفة مبنى يحتوي على وحدات أو سجل مالي.',
    ],
];
