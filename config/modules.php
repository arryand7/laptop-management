<?php

return [
    'list' => [
        [
            'key' => 'dashboard',
            'name' => 'Dashboard',
            'description' => 'Mengakses dashboard utama',
            'default_roles' => ['admin', 'staff', 'student'],
        ],
        [
            'key' => 'admin.students',
            'name' => 'Manajemen Siswa',
            'description' => 'Melihat dan mengelola data siswa',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.laptops',
            'name' => 'Manajemen Laptop',
            'description' => 'Mengelola data laptop',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.users',
            'name' => 'Manajemen User',
            'description' => 'Mengelola akun admin dan staff',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.settings',
            'name' => 'Pengaturan Aplikasi',
            'description' => 'Mengubah pengaturan global aplikasi',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.violations',
            'name' => 'Pelanggaran',
            'description' => 'Mengelola catatan pelanggaran',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.sanctions',
            'name' => 'Sanksi',
            'description' => 'Mengelola sanksi siswa',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.reports',
            'name' => 'Laporan',
            'description' => 'Mengakses laporan dan ekspor data',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'admin.laptop-requests',
            'name' => 'Permintaan Perubahan Laptop',
            'description' => 'Meninjau perubahan data laptop siswa',
            'default_roles' => ['admin'],
        ],
        [
            'key' => 'chatbot',
            'name' => 'Chatbot Peminjaman',
            'description' => 'Perintah singkat pinjam/kembalikan melalui chatbot',
            'default_roles' => ['admin', 'staff'],
        ],
        [
            'key' => 'staff.borrow',
            'name' => 'Form Peminjaman',
            'description' => 'Mengakses formulir peminjaman',
            'default_roles' => ['staff', 'admin'],
        ],
        [
            'key' => 'staff.return',
            'name' => 'Form Pengembalian',
            'description' => 'Mengakses formulir pengembalian',
            'default_roles' => ['staff', 'admin'],
        ],
        [
            'key' => 'student.history',
            'name' => 'Riwayat Peminjaman',
            'description' => 'Melihat riwayat peminjaman siswa',
            'default_roles' => ['student'],
        ],
        [
            'key' => 'student.laptops',
            'name' => 'Laptop Saya',
            'description' => 'Mengajukan perubahan data laptop pribadi',
            'default_roles' => ['student'],
        ],
    ],
];
