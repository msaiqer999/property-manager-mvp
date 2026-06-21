<?php

return [
    'title' => 'المستأجرون',
    'add' => 'إضافة مستأجر',
    'edit' => 'تعديل المستأجر',
    'search_placeholder' => 'بحث في المستأجرين',

    'fields' => [
        'full_name' => 'اسم المستأجر',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'id_number' => 'رقم الهوية',
        'nationality' => 'الجنسية',
        'notes' => 'ملاحظات',
    ],

    'sections' => [
        'contracts' => 'العقود',
    ],

    'lifecycle' => [
        'cannot_delete_with_contracts' => 'لا يمكن حذف مستأجر مرتبط بعقود.',
    ],
];
