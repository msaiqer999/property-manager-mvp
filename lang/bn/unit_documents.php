<?php

return [
    'title' => 'Unit Documents',
    'description' => 'Keep private archive documents for this unit. These files are separate from payment receipts and expense invoices.',
    'upload_title' => 'Upload document',
    'empty' => 'No documents uploaded yet.',
    'download' => 'Download',
    'fields' => [
        'title' => 'Document title',
        'category' => 'Document type',
        'document' => 'File',
        'notes' => 'Notes',
        'uploaded_by' => 'Uploaded by',
        'uploaded_at' => 'Uploaded date',
    ],
    'categories' => [
        'tenant_id_copy' => 'Tenant ID copy',
        'contract_attachment' => 'Contract attachment',
        'ownership_document' => 'Ownership / property document',
        'utility_bill' => 'Utility bill',
        'maintenance_photo' => 'Maintenance photo',
        'external_invoice_archive' => 'External invoice archive',
        'inspection_photo' => 'Inspection photo',
        'handover_document' => 'Handover document',
        'other' => 'Other archive file',
    ],
    'actions' => [
        'upload' => 'Upload document',
    ],
    'messages' => [
        'uploaded' => 'Document uploaded successfully.',
    ],
    'help' => [
        'allowed_types' => 'Allowed files: PDF, JPG, PNG, or WEBP up to 5 MB.',
    ],
];
