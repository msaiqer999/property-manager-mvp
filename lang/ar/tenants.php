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
        'active' => 'نشط',
        'all' => 'الكل',
        'already_archived' => 'تمت أرشفة هذا المستأجر مسبقاً.',
        'archive' => 'أرشفة المستأجر',
        'archive_reason' => 'سبب الأرشفة',
        'archived' => 'مؤرشف',
        'archived_at' => 'تاريخ الأرشفة',
        'archived_by' => 'تمت الأرشفة بواسطة',
        'cannot_archive_with_current_contract' => 'لا يمكن أرشفة مستأجر لديه عقد نشط حالي أو مستقبلي.',
        'cannot_delete_archived' => 'لا يمكن حذف مستأجر مؤرشف.',
        'cannot_delete_with_contracts' => 'لا يمكن حذف مستأجر مرتبط بعقود.',
        'cannot_edit_archived' => 'لا يمكن تعديل مستأجر مؤرشف.',
        'cannot_renew_archived' => 'لا يمكن تجديد مستأجر مؤرشف في مدة عقد جديدة.',
        'cannot_select_archived' => 'لا يمكن اختيار مستأجر مؤرشف لعقد جديد.',
        'confirm_archive' => 'هل تريد أرشفة هذا المستأجر؟ ستبقى العقود والدفعات والتقارير وسجل النشاط متاحة.',
    ],

    'attributes' => [
        'archive_reason' => 'سبب الأرشفة',
    ],
];
