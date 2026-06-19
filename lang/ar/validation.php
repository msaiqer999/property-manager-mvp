<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صالحاً.',
    'confirmed' => 'تأكيد حقل :attribute غير متطابق.',
    'min' => [
        'string' => 'يجب ألا يقل حقل :attribute عن :min أحرف.',
        'numeric' => 'يجب ألا يقل حقل :attribute عن :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يزيد حقل :attribute عن :max أحرف.',
        'numeric' => 'يجب ألا يزيد حقل :attribute عن :max.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
    ],
    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'name' => 'الاسم',
        'organization_name' => 'المنشأة',
    ],
];
