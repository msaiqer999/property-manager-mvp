<?php

return [
    'title' => 'Laporan',

    'summary' => [
        'income' => 'Pendapatan',
        'expenses' => 'Pengeluaran',
        'net_profit' => 'Laba bersih',
    ],

    'filters' => [
        'building' => 'Properti',
        'unit' => 'Unit',
        'from' => 'Dari tanggal',
        'to' => 'Sampai tanggal',
        'tenant' => 'Penyewa',
        'apply' => 'Terapkan',
        'all_buildings' => 'Semua properti',
        'all_units' => 'Semua unit',
        'all_tenants' => 'Semua penyewa',
    ],

    'actions' => [
        'building_income' => 'Ekspor PDF pendapatan properti',
        'unit_statement' => 'Unduh PDF mutasi unit',
        'expenses' => 'Ekspor PDF pengeluaran',
        'overdue' => 'Ekspor PDF pembayaran tertunggak',
        'net_profit' => 'Ekspor PDF laba bersih',
        'monthly_summary' => 'Ekspor PDF ringkasan bulanan',
    ],

    'types' => [
        'building-income' => 'Pendapatan properti',
        'unit-statement' => 'Mutasi penyewa / unit',
        'expenses' => 'Pengeluaran',
        'overdue' => 'Pembayaran tertunggak',
        'net-profit' => 'Laba bersih',
        'monthly-summary' => 'Ringkasan bulanan',
    ],

    'columns' => [
        'amount' => 'Jumlah',
        'amount_due' => 'Tagihan',
        'amount_paid' => 'Dibayar',
        'building' => 'Properti',
        'category' => 'Kategori',
        'contract' => 'Kontrak',
        'contracts' => 'Kontrak',
        'date' => 'Tanggal',
        'due_date' => 'Jatuh tempo',
        'expenses' => 'Pengeluaran',
        'income' => 'Pendapatan',
        'method' => 'Metode',
        'net_profit' => 'Laba bersih',
        'remaining_amount' => 'Sisa',
        'remaining_balance' => 'Sisa tagihan',
        'overdue_remaining' => 'Sisa tertunggak',
        'paid_date' => 'Tanggal bayar',
        'receipt' => 'Kuitansi',
        'status' => 'Status',
        'tenant' => 'Penyewa',
        'unit' => 'Unit',
    ],

    'statement' => [
        'title' => 'Mutasi penyewa / unit',
        'subtitle' => 'Tinjau tagihan, pembayaran, sisa, dan kuitansi yang tersedia.',
        'empty' => 'Tidak ada jadwal pembayaran untuk filter mutasi yang dipilih.',
        'view_statement' => 'Lihat mutasi',
        'view_receipt' => 'Lihat kuitansi',
    ],

    'pdf' => [
        'generated_at' => 'Dibuat pada',
        'metadata' => 'Filter laporan',
        'no_data' => 'Tidak ada data untuk laporan ini.',
        'not_available' => 'N/A',
        'rows' => 'Baris',
        'totals' => 'Total',
    ],
];
