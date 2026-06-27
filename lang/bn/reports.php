<?php

return [
    'title' => 'রিপোর্ট',

    'summary' => [
        'income' => 'আয়',
        'expenses' => 'খরচ',
        'net_profit' => 'নিট লাভ',
    ],

    'filters' => [
        'building' => 'ভবন',
        'unit' => 'ইউনিট',
        'from' => 'শুরুর তারিখ',
        'to' => 'শেষ তারিখ',
        'apply' => 'প্রয়োগ করুন',
        'all_buildings' => 'সব ভবন',
        'all_units' => 'সব ইউনিট',
    ],

    'actions' => [
        'building_income' => 'ভবন আয়ের PDF',
        'unit_statement' => 'ইউনিট স্টেটমেন্ট PDF',
        'expenses' => 'খরচের PDF',
        'overdue' => 'বকেয়া পেমেন্ট PDF',
        'net_profit' => 'নিট লাভ PDF',
        'monthly_summary' => 'মাসিক সারাংশ PDF',
    ],

    'types' => [
        'building-income' => 'ভবন আয়',
        'unit-statement' => 'ইউনিট স্টেটমেন্ট',
        'expenses' => 'খরচ',
        'overdue' => 'বকেয়া পেমেন্ট',
        'net-profit' => 'নিট লাভ',
        'monthly-summary' => 'মাসিক সারাংশ',
    ],

    'columns' => [
        'amount' => 'পরিমাণ',
        'amount_due' => 'প্রাপ্য',
        'amount_paid' => 'পরিশোধিত',
        'building' => 'ভবন',
        'category' => 'বিভাগ',
        'contract' => 'চুক্তি',
        'contracts' => 'চুক্তি',
        'date' => 'তারিখ',
        'due_date' => 'প্রাপ্য তারিখ',
        'expenses' => 'খরচ',
        'income' => 'আয়',
        'method' => 'পদ্ধতি',
        'net_profit' => 'নিট লাভ',
        'remaining_amount' => 'বাকি',
        'status' => 'অবস্থা',
        'tenant' => 'ভাড়াটিয়া',
        'unit' => 'ইউনিট',
    ],

    'pdf' => [
        'generated_at' => 'তৈরি হয়েছে',
        'metadata' => 'রিপোর্ট ফিল্টার',
        'no_data' => 'এই রিপোর্টে কোনো তথ্য নেই।',
        'not_available' => 'প্রযোজ্য নয়',
        'rows' => 'সারি',
        'totals' => 'মোট',
    ],
];
