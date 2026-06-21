<?php

return [
    'title' => 'المصروفات',
    'expense' => 'المصروف',
    'add' => 'إضافة مصروف',
    'edit' => 'تعديل المصروف',
    'view' => 'عرض',
    'edit_action' => 'تعديل',
    'not_available' => 'غير متاح',
    'empty' => 'لا توجد مصروفات.',

    'categories' => [
        'maintenance' => 'صيانة',
        'electricity' => 'كهرباء',
        'water' => 'مياه',
        'cleaning' => 'تنظيف',
        'security' => 'أمن',
        'management' => 'إدارة',
        'other' => 'أخرى',
    ],

    'form' => [
        'building' => 'المبنى',
        'unit' => 'الوحدة',
        'category' => 'الفئة',
        'amount' => 'المبلغ',
        'date' => 'التاريخ',
        'invoice' => 'الفاتورة',
        'notes' => 'ملاحظات',
        'save' => 'حفظ',
    ],

    'show' => [
        'building' => 'المبنى',
        'unit' => 'الوحدة',
        'category' => 'الفئة',
        'amount' => 'المبلغ',
        'date' => 'التاريخ',
        'notes' => 'ملاحظات',
        'invoice' => 'الفاتورة',
        'status' => 'الحالة',
        'action' => 'الإجراء',
    ],

    'lifecycle' => [
        'active' => 'النشطة',
        'all' => 'الكل',
        'already_voided' => 'تم إبطال هذا السجل المالي مسبقًا.',
        'cannot_delete_financial_record' => 'لا يمكن حذف سجلات المصروفات المالية.',
        'cannot_edit_voided' => 'لا يمكن تعديل سجل مالي مبطل.',
        'confirm_void' => 'هل تريد إبطال هذا السجل المالي؟ سيبقى المبلغ والتاريخ والفاتورة والملاحظات دون تغيير.',
        'void' => 'إبطال السجل المالي',
        'void_reason' => 'سبب الإبطال',
        'voided' => 'مبطل',
        'voided_at' => 'تاريخ الإبطال',
        'voided_by' => 'أبطل بواسطة',
        'voided_filter' => 'المبطلة',
    ],

    'filters' => [
        'all_buildings' => 'جميع المباني',
        'all_units' => 'جميع الوحدات',
        'all_categories' => 'جميع الفئات',
    ],

    'attributes' => [
        'void_reason' => 'سبب الإبطال',
    ],
];
