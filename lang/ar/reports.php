<?php

return [
    'title' => 'التقارير',

    'summary' => [
        'income' => 'الدخل',
        'expenses' => 'المصروفات',
        'net_profit' => 'صافي الربح',
    ],

    'actions' => [
        'building_income' => 'تصدير تقرير دخل المباني PDF',
        'unit_statement' => 'تنزيل كشف الوحدة PDF',
        'expenses' => 'تصدير تقرير المصروفات PDF',
        'overdue' => 'تصدير تقرير الدفعات المتأخرة PDF',
        'net_profit' => 'تصدير تقرير صافي الربح PDF',
        'monthly_summary' => 'تصدير الملخص الشهري PDF',
    ],
    'types' => [
        'building-income' => 'دخل المباني',
        'unit-statement' => 'كشف الوحدة',
        'expenses' => 'المصروفات',
        'overdue' => 'الدفعات المتأخرة',
        'net-profit' => 'صافي الربح',
        'monthly-summary' => 'الملخص الشهري',
    ],

    'columns' => [
        'amount' => 'المبلغ',
        'amount_due' => 'المبلغ المستحق',
        'amount_paid' => 'المبلغ المدفوع',
        'building' => 'المبنى',
        'category' => 'الفئة',
        'contract' => 'العقد',
        'contracts' => 'العقود',
        'date' => 'التاريخ',
        'due_date' => 'تاريخ الاستحقاق',
        'income' => 'الدخل',
        'method' => 'طريقة الدفع',
        'remaining_amount' => 'المتبقي',
        'status' => 'الحالة',
        'tenant' => 'المستأجر',
        'unit' => 'الوحدة',
    ],

    'pdf' => [
        'generated_at' => 'تم الإنشاء في',
        'no_data' => 'لا توجد بيانات متاحة لهذا التقرير.',
        'not_available' => 'غير متاح',
        'rows' => 'الصفوف',
    ],
];
