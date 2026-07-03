<?php

return [
    'title' => 'الدفعات',
    'payment' => 'الدفعة',
    'description' => 'يتم إنشاء الدفعات من العقود ويمكن تسجيلها عند تحصيل الإيجار.',
    'all_statuses' => 'كل الحالات',
    'filter' => 'تصفية',
    'overdue' => 'متأخر',
    'view_receipt' => 'عرض الإيصال',
    'record_payment' => 'تسجيل دفعة',
    'download_proof' => 'تنزيل إثبات الدفع',
    'download_receipt_pdf' => 'تنزيل إيصال PDF',
    'not_available' => 'غير متاح',
    'recorded_success' => 'تم تسجيل الدفعة بنجاح. يمكنك تنزيل الإيصال.',

    'columns' => [
        'due_date' => 'تاريخ الاستحقاق',
        'tenant' => 'المستأجر',
        'unit' => 'الوحدة',
        'contract' => 'العقد',
        'amount' => 'المبلغ',
        'status' => 'الحالة',
        'paid_date' => 'تاريخ الدفع',
        'action' => 'الإجراء',
    ],

    'statuses' => [
        'pending' => 'قيد الانتظار',
        'paid' => 'مدفوع',
        'partial' => 'مدفوع جزئيًا',
        'partial_overdue' => 'مدفوع جزئيًا، والمتبقي متأخر',
        'overdue' => 'متأخر',
        'cancelled' => 'ملغاة',
    ],

    'methods' => [
        'cash' => 'نقدًا',
        'bank_transfer' => 'تحويل بنكي',
        'cheque' => 'شيك',
        'other' => 'أخرى',
    ],

    'form' => [
        'summary' => 'ملخص الدفعة',
        'building' => 'المبنى',
        'due' => 'مستحق في',
        'amount_paid' => 'المبلغ المدفوع',
        'payment_date' => 'تاريخ الدفع',
        'method' => 'طريقة الدفع',
        'proof_image' => 'صورة الإثبات',
        'notes' => 'ملاحظات',
        'save' => 'حفظ',
    ],

    'show' => [
        'due' => 'مستحق',
        'amount_due' => 'المبلغ المستحق',
        'paid' => 'المدفوع',
        'remaining' => 'المبلغ المتبقي',
        'status' => 'الحالة',
    ],

    'pdf' => [
        'title' => 'إيصال دفع',
        'generated_at' => 'تم الإنشاء في',
        'receipt_number' => 'رقم الإيصال',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'ملغاة بسبب إنهاء العقد',
    ],

    'receipt' => [
        'partial_note' => 'هذا الإيصال يثبت المبلغ المستلم فقط، ولا يعني إغلاق كامل الدفعة.',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'لا يمكن تخفيض المبلغ المدفوع بعد تسجيله.',
        'cannot_record_cancelled' => 'لا يمكن تسجيل أو تغيير دفعة ملغاة.',
        'receipt_unavailable_without_recorded_money' => 'لا يتوفر إيصال لأنه لم يتم تسجيل أي مبلغ لهذه الدفعة.',
    ],
];
