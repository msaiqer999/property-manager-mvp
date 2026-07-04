<?php

return [
    'title' => 'भुगतान',
    'payment' => 'भुगतान',
    'description' => 'भुगतान अनुबंधों से बनते हैं और किराया प्राप्त होने पर रिकॉर्ड किए जा सकते हैं।',
    'all_statuses' => 'सभी स्थितियां',
    'filter' => 'फ़िल्टर',
    'overdue' => 'बकाया',
    'view_receipt' => 'रसीद देखें',
    'record_payment' => 'भुगतान रिकॉर्ड करें',
    'download_proof' => 'प्रमाण डाउनलोड',
    'download_receipt_pdf' => 'रसीद PDF डाउनलोड',
    'not_available' => 'लागू नहीं',

    'columns' => [
        'due_date' => 'देय तारीख',
        'tenant' => 'किरायेदार',
        'unit' => 'यूनिट',
        'contract' => 'अनुबंध',
        'amount' => 'राशि',
        'status' => 'स्थिति',
        'paid_date' => 'भुगतान तारीख',
        'action' => 'कार्य',
    ],

    'statuses' => [
        'pending' => 'लंबित',
        'paid' => 'भुगतान किया गया',
        'partial' => 'आंशिक भुगतान',
        'partial_overdue' => 'आंशिक भुगतान, बाकी राशि बकाया है',
        'overdue' => 'बकाया',
        'cancelled' => 'रद्द',
    ],

    'methods' => [
        'cash' => 'नकद',
        'bank_transfer' => 'बैंक ट्रांसफर',
        'cheque' => 'चेक',
        'other' => 'अन्य',
    ],

    'form' => [
        'summary' => 'भुगतान सारांश',
        'due' => 'देय',
        'amount_paid' => 'भुगतान की गई राशि',
        'payment_date' => 'भुगतान तारीख',
        'method' => 'तरीका',
        'proof_image' => 'प्रमाण छवि',
        'notes' => 'नोट्स',
        'save' => 'सेव',
    ],

    'show' => [
        'due' => 'देय',
        'amount_due' => 'देय राशि',
        'paid' => 'भुगतान',
        'remaining' => 'बाकी राशि',
        'status' => 'स्थिति',
    ],

    'pdf' => [
        'title' => 'भुगतान रसीद',
        'generated_at' => 'बनाया गया',
        'receipt_number' => 'रसीद संख्या',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'अनुबंध समाप्त होने के कारण रद्द',
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
        'partial_note' => 'यह रसीद केवल प्राप्त राशि की पुष्टि करती है। बाकी राशि अभी भी बकाया है।',
    ],

    'validation' => [
        'paid_amount_cannot_decrease' => 'रिकॉर्ड की गई भुगतान राशि कम नहीं की जा सकती।',
        'cannot_record_cancelled' => 'रद्द भुगतान रिकॉर्ड या बदला नहीं जा सकता।',
        'receipt_unavailable_without_recorded_money' => 'इस भुगतान के लिए कोई राशि रिकॉर्ड नहीं है, इसलिए रसीद उपलब्ध नहीं है।',
    ],
];
