<?php

return [
    'title' => 'المستخدمون',
    'invite' => 'إضافة مستخدم',
    'edit' => 'تعديل المستخدم',

    'fields' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'role' => 'الدور',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
    ],

    'roles' => [
        'owner' => 'مالك',
        'manager' => 'مدير',
        'accountant' => 'محاسب',
        'caretaker' => 'حارس',
    ],

    'actions' => [
        'deactivate' => 'تعطيل',
        'reactivate' => 'إعادة تفعيل',
    ],
];
