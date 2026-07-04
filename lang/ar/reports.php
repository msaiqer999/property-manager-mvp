<?php

return [
    'title' => 'التقارير',

    'summary' => [
        'income' => 'الدخل',
        'expenses' => 'المصروفات',
        'net_profit' => 'صافي الربح',
    ],

    'filters' => [
        'building' => 'المبنى',
        'unit' => 'الوحدة',
        'from' => 'من تاريخ',
        'to' => 'إلى تاريخ',
        'tenant' => 'المستأجر',
        'apply' => 'تطبيق',
        'all_buildings' => 'كل المباني',
        'all_units' => 'كل الوحدات',
        'all_tenants' => 'كل المستأجرين',
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
        'unit-statement' => 'كشف حساب المستأجر / الوحدة',
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
        'expenses' => 'المصروفات',
        'income' => 'الدخل',
        'method' => 'طريقة الدفع',
        'net_profit' => 'صافي الربح',
        'remaining_amount' => 'المتبقي',
        'remaining_balance' => 'الرصيد المتبقي',
        'overdue_remaining' => 'المتأخر المتبقي',
        'paid_date' => 'تاريخ الدفع',
        'receipt' => 'الإيصال',
        'status' => 'الحالة',
        'tenant' => 'المستأجر',
        'unit' => 'الوحدة',
    ],

    'statement' => [
        'title' => 'كشف حساب المستأجر / الوحدة',
        'subtitle' => 'راجع المستحق، والمدفوع، والمتبقي، والإيصالات المتاحة.',
        'empty' => 'لا توجد دفعات مجدولة لفلاتر كشف الحساب المحددة.',
        'view_statement' => 'عرض كشف الحساب',
        'view_receipt' => 'عرض الإيصال',
    ],

    'pdf' => [
        'generated_at' => 'تم الإنشاء في',
        'metadata' => 'فلاتر التقرير',
        'no_data' => 'لا توجد بيانات متاحة لهذا التقرير.',
        'not_available' => 'غير متاح',
        'rows' => 'الصفوف',
        'totals' => 'الإجماليات',
    ],
];
