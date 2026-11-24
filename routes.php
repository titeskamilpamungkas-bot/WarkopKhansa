<?php
/*
 * Daftar rute aplikasi.
 *
 * Kolom akses:
 *   'public' : siapa saja boleh membuka
 *   'guest'  : hanya yang BELUM login (contoh: halaman login)
 *   'auth'   : harus login, role apa pun yang valid
 *   array    : harus login DAN role-nya ada di daftar, contoh ['admin']
 */

$semua = ['admin', 'kasir'];
$admin = ['admin'];

return [
    'GET' => [
        ''                   => ['AuthController@root', 'public'],
        'login'              => ['AuthController@showLogin', 'guest'],
        'dashboard'          => ['DashboardController@index', $semua],
        'transaksi'          => ['TransaksiController@index', $semua],
        'struk'              => ['TransaksiController@struk', $semua],
        'barang'             => ['BarangController@index', $semua],
        'member'             => ['MemberController@index', $semua],
        'laporan'            => ['LaporanController@index', $semua],
        'pengaturan'         => ['PengaturanController@index', $semua],
    ],
    'POST' => [
        'login'              => ['AuthController@login', 'guest'],
        'logout'             => ['AuthController@logout', 'auth'],
        'transaksi/proses'   => ['TransaksiController@proses', $semua],
        'barang/tambah'      => ['BarangController@tambah', $admin],
        'barang/edit'        => ['BarangController@edit', $admin],
        'barang/hapus'       => ['BarangController@hapus', $admin],
        'barang/kategori'    => ['BarangController@tambahKategori', $admin],
        'member/tambah'      => ['MemberController@tambah', $semua],
        'member/edit'        => ['MemberController@edit', $semua],
        'member/hapus'       => ['MemberController@hapus', $admin],
        'pengaturan/akun'    => ['PengaturanController@ubahAkun', $semua],
        'pengaturan/user/tambah' => ['PengaturanController@tambahUser', $admin],
        'pengaturan/user/status' => ['PengaturanController@ubahStatusUser', $admin],
    ],
];
