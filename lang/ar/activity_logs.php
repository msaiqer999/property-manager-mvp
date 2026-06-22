<?php

return [
    'title' => 'سجل النشاط',

    'actions' => [
        'building' => [
            'created' => 'تم إنشاء مبنى',
            'updated' => 'تم تحديث مبنى',
            'deleted' => 'تم حذف مبنى',
        ],
        'tenant' => [
            'created' => 'تم إنشاء مستأجر',
            'updated' => 'تم تحديث مستأجر',
            'deleted' => 'تم حذف مستأجر',
            'archived' => 'تمت أرشفة المستأجر',
        ],
        'contract' => [
            'created' => 'تم إنشاء عقد',
            'updated' => 'تم تحديث عقد',
            'terminated' => 'تم إنهاء عقد',
        ],
        'expense' => [
            'created' => 'تم إنشاء مصروف',
            'updated' => 'تم تحديث مصروف',
            'voided' => 'تم إبطال سجل مالي',
        ],
        'payment' => [
            'recorded' => 'تم تسجيل دفعة',
        ],
        'unit' => [
            'created' => 'تم إنشاء وحدة',
            'updated' => 'تم تحديث وحدة',
            'status_changed' => 'تم تغيير حالة وحدة',
            'deleted' => 'تم حذف وحدة',
        ],
        'user' => [
            'role_changed' => 'تم تغيير دور مستخدم',
            'deactivated' => 'تم تعطيل مستخدم',
            'reactivated' => 'تمت إعادة تفعيل مستخدم',
        ],
    ],
];
