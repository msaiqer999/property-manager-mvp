<?php

return [
    'title' => 'رپورٹس',

    'summary' => [
        'income' => 'آمدنی',
        'expenses' => 'اخراجات',
        'net_profit' => 'خالص منافع',
    ],

    'filters' => [
        'building' => 'عمارت',
        'unit' => 'یونٹ',
        'from' => 'شروع تاریخ',
        'to' => 'اختتامی تاریخ',
        'apply' => 'لاگو کریں',
        'all_buildings' => 'تمام عمارتیں',
        'all_units' => 'تمام یونٹس',
    ],

    'actions' => [
        'building_income' => 'عمارت آمدنی PDF',
        'unit_statement' => 'یونٹ اسٹیٹمنٹ PDF',
        'expenses' => 'اخراجات PDF',
        'overdue' => 'تاخیر شدہ ادائیگیاں PDF',
        'net_profit' => 'خالص منافع PDF',
        'monthly_summary' => 'ماہانہ خلاصہ PDF',
    ],

    'types' => [
        'building-income' => 'عمارت آمدنی',
        'unit-statement' => 'یونٹ اسٹیٹمنٹ',
        'expenses' => 'اخراجات',
        'overdue' => 'تاخیر شدہ ادائیگیاں',
        'net-profit' => 'خالص منافع',
        'monthly-summary' => 'ماہانہ خلاصہ',
    ],

    'columns' => [
        'amount' => 'رقم',
        'amount_due' => 'واجب الادا',
        'amount_paid' => 'ادا شدہ',
        'building' => 'عمارت',
        'category' => 'زمرہ',
        'contract' => 'معاہدہ',
        'contracts' => 'معاہدے',
        'date' => 'تاریخ',
        'due_date' => 'واجب تاریخ',
        'expenses' => 'اخراجات',
        'income' => 'آمدنی',
        'method' => 'طریقہ',
        'net_profit' => 'خالص منافع',
        'remaining_amount' => 'باقی',
        'status' => 'حالت',
        'tenant' => 'کرایہ دار',
        'unit' => 'یونٹ',
    ],

    'pdf' => [
        'generated_at' => 'تیار کیا گیا',
        'metadata' => 'رپورٹ فلٹرز',
        'no_data' => 'اس رپورٹ کے لیے کوئی ڈیٹا نہیں۔',
        'not_available' => 'دستیاب نہیں',
        'rows' => 'قطاریں',
        'totals' => 'کل',
    ],
];
