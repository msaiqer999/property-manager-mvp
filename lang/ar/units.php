<?php

return [
    'title' => 'الوحدات',
    'add' => 'إضافة وحدة',
    'edit' => 'تعديل الوحدة',

    'filters' => [
        'all_buildings' => 'جميع المباني',
        'all_statuses' => 'جميع الحالات',
    ],

    'fields' => [
        'unit' => 'الوحدة',
        'building' => 'المبنى',
        'status' => 'الحالة',
        'rent' => 'قيمة الإيجار',
        'unit_number' => 'رقم الوحدة',
        'type' => 'النوع',
        'size' => 'المساحة',
        'rooms' => 'عدد الغرف',
        'notes' => 'ملاحظات',
        'action' => 'الإجراء',
    ],

    'labels' => [
        'building' => 'المبنى:',
        'status' => 'الحالة:',
        'type' => 'النوع:',
        'rent' => 'قيمة الإيجار:',
    ],

    'statuses' => [
        'vacant' => 'شاغرة',
        'rented' => 'مؤجرة',
        'maintenance' => 'قيد الصيانة',
    ],

    'types' => [
        'apartment' => 'شقة',
        'shop' => 'محل',
        'office' => 'مكتب',
        'warehouse' => 'مستودع',
        'villa' => 'فيلا',
        'chalet' => 'شاليه',
        'other' => 'أخرى',
    ],

    'bulk' => [
        'add_multiple' => 'إضافة وحدات متعددة',
        'title' => 'إضافة وحدات متعددة',
        'description' => 'أنشئ قائمة وحدات لمبنى :building، ثم راجع كل صف قبل الحفظ.',
        'preview_title' => 'معاينة الوحدات',
        'preview_description' => 'عدّل أي صف قبل إنشاء الوحدات في مبنى :building.',
        'generate_preview' => 'توليد المعاينة',
        'create_units' => 'إنشاء الوحدات',
        'prefix' => 'بادئة اختيارية',
        'start_number' => 'رقم البداية',
        'end_number' => 'رقم النهاية',
        'default_type' => 'النوع الافتراضي',
        'default_rent' => 'الإيجار الافتراضي',
        'default_rooms' => 'عدد الغرف الافتراضي',
        'default_size' => 'المساحة الافتراضية',
        'default_status' => 'الحالة الافتراضية',
        'default_notes' => 'ملاحظات افتراضية',
        'created_success' => 'تم إنشاء :count وحدة بنجاح.',
        'preview_expired' => 'انتهت صلاحية المعاينة، يرجى توليد المعاينة مرة أخرى.',
        'validation' => [
            'duplicate_in_request' => 'رقم وحدة مكرر داخل الطلب: :unit.',
            'duplicate_existing' => 'رقم وحدة موجود مسبقًا في هذا المبنى: :unit.',
            'range_too_large' => 'يمكن إنشاء 200 وحدة كحد أقصى في كل مرة.',
        ],
        'attributes' => [
            'prefix' => 'البادئة',
            'start_number' => 'رقم البداية',
            'end_number' => 'رقم النهاية',
            'type' => 'النوع',
            'rent_amount' => 'قيمة الإيجار',
            'rooms' => 'عدد الغرف',
            'size' => 'المساحة',
            'status' => 'الحالة',
            'notes' => 'الملاحظات',
            'units' => 'الوحدات',
            'units.*.unit_number' => 'رقم الوحدة',
            'units.*.type' => 'النوع',
            'units.*.rent_amount' => 'قيمة الإيجار',
            'units.*.rooms' => 'عدد الغرف',
            'units.*.size' => 'المساحة',
            'units.*.status' => 'الحالة',
            'units.*.notes' => 'الملاحظات',
        ],
    ],

    'lifecycle' => [
        'cannot_archive_with_history' => 'لا يمكن أرشفة وحدة مرتبطة بعقود أو مصروفات.',
    ],
];
