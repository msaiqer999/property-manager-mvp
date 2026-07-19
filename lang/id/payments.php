<?php

return [
    'title' => 'Pembayaran',
    'payment' => 'Pembayaran',
    'description' => 'Pembayaran dibuat dari kontrak dan dapat dicatat saat uang sewa diterima.',
    'all_statuses' => 'Semua status',
    'filter' => 'Filter',
    'overdue' => 'Tertunggak',
    'follow_up' => 'Tindak lanjut',
    'view_receipt' => 'Lihat kuitansi',
    'record_payment' => 'Catat pembayaran',
    'download_proof' => 'Unduh bukti',
    'download_receipt_pdf' => 'Unduh PDF kuitansi',
    'not_available' => 'N/A',
    'recorded_success' => 'Pembayaran berhasil dicatat. Anda dapat mengunduh kuitansi.',

    'columns' => [
        'due_date' => 'Jatuh tempo',
        'tenant' => 'Penyewa',
        'unit' => 'Unit',
        'contract' => 'Kontrak',
        'amount' => 'Jumlah',
        'status' => 'Status',
        'paid_date' => 'Tanggal bayar',
        'action' => 'Aksi',
    ],

    'statuses' => [
        'pending' => 'Menunggu',
        'paid' => 'Lunas',
        'partial' => 'Dibayar sebagian',
        'partial_overdue' => 'Dibayar sebagian, sisa tertunggak',
        'overdue' => 'Tertunggak',
        'cancelled' => 'Dibatalkan',
    ],

    'methods' => [
        'cash' => 'Tunai',
        'bank_transfer' => 'Transfer bank',
        'cheque' => 'Cek',
        'other' => 'Lainnya',
    ],

    'show' => [
        'due' => 'Jatuh tempo',
        'amount_due' => 'Tagihan',
        'paid' => 'Dibayar',
        'remaining' => 'Sisa tagihan',
        'status' => 'Status',
    ],

    'lifecycle' => [
        'cancelled_due_to_contract_termination' => 'Dibatalkan karena kontrak dihentikan',
    ],
];
