<?php

return [
    'title' => 'ادائیگیاں',
    'payment' => 'ادائیگی',
    'description' => 'ادائیگیاں معاہدوں سے بنتی ہیں اور کرایہ وصول ہونے پر ریکارڈ کی جا سکتی ہیں۔',
    'all_statuses' => 'تمام حالتیں',
    'filter' => 'فلٹر',
    'overdue' => 'تاخیر شدہ',
    'view_receipt' => 'رسید دیکھیں',
    'record_payment' => 'ادائیگی ریکارڈ کریں',
    'download_proof' => 'ثبوت ڈاؤن لوڈ',
    'download_receipt_pdf' => 'رسید PDF ڈاؤن لوڈ',
    'not_available' => 'دستیاب نہیں',

    'columns' => [
        'due_date' => 'واجب الادا تاریخ',
        'tenant' => 'کرایہ دار',
        'unit' => 'یونٹ',
        'contract' => 'معاہدہ',
        'amount' => 'رقم',
        'status' => 'حالت',
        'paid_date' => 'ادائیگی کی تاریخ',
        'action' => 'کارروائی',
    ],

    'statuses' => [
        'pending' => 'زیر التوا',
        'paid' => 'ادا شدہ',
        'partial' => 'جزوی ادائیگی',
        'partial_overdue' => 'جزوی ادائیگی، باقی رقم واجب الادا ہے',
        'overdue' => 'تاخیر شدہ',
        'cancelled' => 'منسوخ',
    ],

    'methods' => [
        'cash' => 'نقد',
        'bank_transfer' => 'بینک ٹرانسفر',
        'cheque' => 'چیک',
        'other' => 'دیگر',
    ],

    'form' => [
        'summary' => 'ادائیگی کا خلاصہ',
        'due' => 'واجب الادا',
        'amount_paid' => 'ادا شدہ رقم',
        'payment_date' => 'ادائیگی کی تاریخ',
        'method' => 'طریقہ',
        'proof_image' => 'ثبوت کی تصویر',
        'notes' => 'نوٹس',
        'save' => 'محفوظ کریں',
    ],

    'show' => [
        'due' => 'واجب الادا',
        'amount_due' => 'واجب الادا رقم',
        'paid' => 'ادا شدہ',
        'remaining' => 'باقی رقم',
        'status' => 'حالت',
    ],

    'pdf' => [
        'title' => 'ادائیگی کی رسید',
        'generated_at' => 'بنائی گئی',
        'receipt_number' => 'رسید نمبر',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'معاہدہ ختم ہونے کی وجہ سے منسوخ',
    ],

    'follow_ups' => [
        'title' => 'Follow-up history',
        'description' => 'Track manual reminders, tenant replies, and payment promises for this payment.',
        'empty' => 'No follow-up entries have been recorded yet.',
        'type' => 'Follow-up type',
        'note' => 'Follow-up note',
        'promised_date' => 'Promised payment date',
        'promised_amount' => 'Promised amount',
        'created_by' => 'Created by',
        'save' => 'Save follow-up',
        'saved' => 'Follow-up saved.',
        'log_reminder' => 'Log reminder',
        'promise_indicator' => 'Promise',
        'types' => [
            'note' => 'Note',
            'reminder_logged' => 'Reminder logged',
            'promise_to_pay' => 'Promise to pay',
        ],
    ],

    'receipt' => [
        'partial_note' => 'یہ رسید صرف وصول شدہ رقم کی تصدیق کرتی ہے۔ باقی رقم ابھی واجب الادا ہے۔',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'ریکارڈ شدہ ادا شدہ رقم کم نہیں کی جا سکتی۔',
        'cannot_record_cancelled' => 'منسوخ ادائیگی ریکارڈ یا تبدیل نہیں کی جا سکتی۔',
        'receipt_unavailable_without_recorded_money' => 'اس ادائیگی کے لیے کوئی رقم ریکارڈ نہیں ہوئی، اس لیے رسید دستیاب نہیں۔',
    ],
];
