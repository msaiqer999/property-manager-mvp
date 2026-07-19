<?php

return [
    'title' => 'Penyewa',
    'add' => 'Tambah penyewa',
    'edit' => 'Edit penyewa',
    'search_placeholder' => 'Cari penyewa',

    'actions' => [
        'save_and_create_contract' => 'Simpan dan buat kontrak',
    ],

    'fields' => [
        'full_name' => 'Nama lengkap',
        'phone' => 'Telepon',
        'email' => 'Email',
        'id_number' => 'Nomor ID',
        'nationality' => 'Kewarganegaraan',
        'notes' => 'Catatan',
    ],

    'sections' => [
        'contracts' => 'Kontrak',
    ],

    'lifecycle' => [
        'active' => 'Aktif',
        'all' => 'Semua',
        'already_archived' => 'Penyewa ini sudah diarsipkan.',
        'archive' => 'Arsipkan penyewa',
        'archive_reason' => 'Alasan arsip',
        'archived' => 'Diarsipkan',
        'archived_at' => 'Diarsipkan pada',
        'archived_by' => 'Diarsipkan oleh',
        'cannot_archive_with_current_contract' => 'Penyewa dengan kontrak aktif atau mendatang tidak dapat diarsipkan.',
        'cannot_delete_archived' => 'Penyewa yang diarsipkan tidak dapat dihapus.',
        'cannot_delete_with_contracts' => 'Penyewa yang terhubung ke kontrak tidak dapat dihapus.',
        'cannot_edit_archived' => 'Penyewa yang diarsipkan tidak dapat diedit.',
        'cannot_renew_archived' => 'Penyewa yang diarsipkan tidak dapat diperpanjang ke masa kontrak baru.',
        'cannot_select_archived' => 'Penyewa yang diarsipkan tidak dapat dipilih untuk kontrak baru.',
        'confirm_archive' => 'Arsipkan penyewa ini? Kontrak, pembayaran, laporan, dan riwayat aktivitas tetap tersedia.',
    ],

    'attributes' => [
        'archive_reason' => 'alasan arsip',
    ],
];
