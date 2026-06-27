<?php

return [
    'title' => 'रिपोर्ट',

    'summary' => [
        'income' => 'आय',
        'expenses' => 'खर्च',
        'net_profit' => 'शुद्ध लाभ',
    ],

    'filters' => [
        'building' => 'भवन',
        'unit' => 'यूनिट',
        'from' => 'आरंभ तिथि',
        'to' => 'अंतिम तिथि',
        'apply' => 'लागू करें',
        'all_buildings' => 'सभी भवन',
        'all_units' => 'सभी यूनिट',
    ],

    'actions' => [
        'building_income' => 'भवन आय PDF',
        'unit_statement' => 'यूनिट स्टेटमेंट PDF',
        'expenses' => 'खर्च PDF',
        'overdue' => 'बकाया भुगतान PDF',
        'net_profit' => 'शुद्ध लाभ PDF',
        'monthly_summary' => 'मासिक सारांश PDF',
    ],

    'types' => [
        'building-income' => 'भवन आय',
        'unit-statement' => 'यूनिट स्टेटमेंट',
        'expenses' => 'खर्च',
        'overdue' => 'बकाया भुगतान',
        'net-profit' => 'शुद्ध लाभ',
        'monthly-summary' => 'मासिक सारांश',
    ],

    'columns' => [
        'amount' => 'राशि',
        'amount_due' => 'देय राशि',
        'amount_paid' => 'भुगतान राशि',
        'building' => 'भवन',
        'category' => 'श्रेणी',
        'contract' => 'अनुबंध',
        'contracts' => 'अनुबंध',
        'date' => 'तिथि',
        'due_date' => 'देय तिथि',
        'expenses' => 'खर्च',
        'income' => 'आय',
        'method' => 'तरीका',
        'net_profit' => 'शुद्ध लाभ',
        'remaining_amount' => 'शेष',
        'status' => 'स्थिति',
        'tenant' => 'किरायेदार',
        'unit' => 'यूनिट',
    ],

    'pdf' => [
        'generated_at' => 'बनाया गया',
        'metadata' => 'रिपोर्ट फ़िल्टर',
        'no_data' => 'इस रिपोर्ट के लिए कोई डेटा उपलब्ध नहीं है।',
        'not_available' => 'उपलब्ध नहीं',
        'rows' => 'पंक्तियाँ',
        'totals' => 'कुल',
    ],
];
