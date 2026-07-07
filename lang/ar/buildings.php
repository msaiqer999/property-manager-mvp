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

    'empty_units_guidance' => 'هذا المبنى جاهز. أضف عدة وحدات دفعة واحدة، أو أضف وحدة واحدة إذا كنت تحتاج ذلك فقط.',

    'lifecycle' => [
        'cannot_archive_with_history' => 'لا يمكن أرشفة مبنى يحتوي على وحدات أو سجل مالي.',
    ],
];
