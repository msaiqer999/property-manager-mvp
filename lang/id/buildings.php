<?php

return [
    'title' => 'Properti',
    'add' => 'Tambah properti',
    'edit' => 'Edit properti',

    'fields' => [
        'name' => 'Nama',
        'location' => 'Lokasi',
        'description' => 'Deskripsi',
        'action' => 'Aksi',
    ],

    'sections' => [
        'units' => 'Unit',
    ],

    'empty_units_guidance' => 'Properti ini sudah siap. Tambahkan beberapa unit sekaligus, atau tambahkan satu unit jika hanya perlu satu.',

    'lifecycle' => [
        'cannot_archive_with_history' => 'Properti yang memiliki unit atau riwayat keuangan tidak dapat diarsipkan.',
    ],
];
