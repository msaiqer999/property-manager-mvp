<?php

return [
    'title' => 'পেমেন্ট',
    'payment' => 'পেমেন্ট',
    'description' => 'চুক্তি থেকে পেমেন্ট তৈরি হয় এবং ভাড়া পাওয়ার পর রেকর্ড করা যায়।',
    'all_statuses' => 'সব অবস্থা',
    'filter' => 'ফিল্টার',
    'overdue' => 'বকেয়া',
    'view_receipt' => 'রসিদ দেখুন',
    'record_payment' => 'পেমেন্ট রেকর্ড করুন',
    'download_proof' => 'প্রমাণ ডাউনলোড',
    'download_receipt_pdf' => 'রসিদ PDF ডাউনলোড',
    'not_available' => 'প্রযোজ্য নয়',

    'columns' => [
        'due_date' => 'নির্ধারিত তারিখ',
        'tenant' => 'ভাড়াটিয়া',
        'unit' => 'ইউনিট',
        'contract' => 'চুক্তি',
        'amount' => 'পরিমাণ',
        'status' => 'অবস্থা',
        'paid_date' => 'পেমেন্ট তারিখ',
        'action' => 'কাজ',
    ],

    'statuses' => [
        'pending' => 'অপেক্ষমাণ',
        'paid' => 'পরিশোধিত',
        'partial' => 'আংশিক পরিশোধিত',
        'partial_overdue' => 'আংশিক পরিশোধিত, বাকি টাকা বকেয়া',
        'overdue' => 'বকেয়া',
        'cancelled' => 'বাতিল',
    ],

    'methods' => [
        'cash' => 'নগদ',
        'bank_transfer' => 'ব্যাংক ট্রান্সফার',
        'cheque' => 'চেক',
        'other' => 'অন্যান্য',
    ],

    'form' => [
        'summary' => 'পেমেন্ট সারাংশ',
        'due' => 'নির্ধারিত',
        'amount_paid' => 'পরিশোধিত পরিমাণ',
        'payment_date' => 'পেমেন্ট তারিখ',
        'method' => 'পদ্ধতি',
        'proof_image' => 'প্রমাণের ছবি',
        'notes' => 'নোট',
        'save' => 'সংরক্ষণ',
    ],

    'show' => [
        'due' => 'নির্ধারিত',
        'amount_due' => 'বকেয়া পরিমাণ',
        'paid' => 'পরিশোধিত',
        'remaining' => 'বাকি পরিমাণ',
        'status' => 'অবস্থা',
    ],

    'pdf' => [
        'title' => 'পেমেন্ট রসিদ',
        'generated_at' => 'তৈরির সময়',
        'receipt_number' => 'রসিদ নম্বর',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'চুক্তি শেষ হওয়ার কারণে বাতিল',
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
        'partial_note' => 'এই রসিদটি শুধু প্রাপ্ত টাকার প্রমাণ। বাকি টাকা এখনো বকেয়া আছে।',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'রেকর্ড করা পরিশোধিত পরিমাণ কমানো যাবে না।',
        'cannot_record_cancelled' => 'বাতিল পেমেন্ট রেকর্ড বা পরিবর্তন করা যাবে না।',
        'receipt_unavailable_without_recorded_money' => 'এই পেমেন্টে কোনো টাকা রেকর্ড না হওয়ায় রসিদ পাওয়া যাচ্ছে না।',
    ],
];
