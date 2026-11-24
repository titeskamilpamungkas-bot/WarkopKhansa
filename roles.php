<?php
/*
 * Daftar role yang dikenali sistem.
 * Role akun diambil dari kolom users.role di database.
 * Role yang tidak ada di daftar ini TIDAK akan diizinkan masuk.
 * Untuk menambah role baru: tambahkan di sini dan di kolom ENUM users.role.
 */

return [
    'admin' => [
        'label'   => 'Admin / Pemilik',
        'beranda' => 'dashboard',
    ],
    'kasir' => [
        'label'   => 'Kasir',
        'beranda' => 'dashboard',
    ],
];
