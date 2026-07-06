<?php

return [
    'title' => 'أرشيف الوحدة',
    'description' => 'احتفظ بمستندات أرشيف خاصة بهذه الوحدة. هذه الملفات منفصلة عن إيصالات الدفع وفواتير المصروفات.',
    'upload_title' => 'رفع مستند',
    'empty' => 'لا توجد مستندات مرفوعة بعد.',
    'download' => 'تحميل',
    'fields' => [
        'title' => 'عنوان المستند',
        'category' => 'نوع المستند',
        'document' => 'الملف',
        'notes' => 'ملاحظات',
        'uploaded_by' => 'تم الرفع بواسطة',
        'uploaded_at' => 'تاريخ الرفع',
    ],
    'categories' => [
        'tenant_id_copy' => 'نسخة هوية المستأجر',
        'contract_attachment' => 'مرفق عقد',
        'ownership_document' => 'مستند ملكية / عقار',
        'utility_bill' => 'فاتورة خدمات',
        'maintenance_photo' => 'صورة صيانة',
        'external_invoice_archive' => 'أرشيف فاتورة خارجية',
        'inspection_photo' => 'صورة معاينة',
        'handover_document' => 'مستند تسليم',
        'other' => 'ملف أرشيف آخر',
    ],
    'actions' => [
        'upload' => 'رفع المستند',
    ],
    'messages' => [
        'uploaded' => 'تم رفع المستند بنجاح.',
    ],
    'help' => [
        'allowed_types' => 'الملفات المسموحة: PDF أو JPG أو PNG أو WEBP حتى 5 ميجابايت.',
    ],
];
