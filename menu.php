<?php
/*
 * Susunan menu sidebar.
 * Menu hanya ditampilkan jika role pengguna termasuk dalam 'roles'.
 */

$semua = ['admin', 'kasir'];

return [
    ['label' => 'Dashboard',     'icon' => 'fa-solid fa-gauge-high',       'route' => 'dashboard',  'roles' => $semua],
    ['label' => 'Transaksi',     'icon' => 'fa-solid fa-cash-register',    'route' => 'transaksi',  'roles' => $semua],
    ['label' => 'Barang & Stok', 'icon' => 'fa-solid fa-boxes-stacked',    'route' => 'barang',     'roles' => $semua],
    ['label' => 'Member',        'icon' => 'fa-solid fa-id-card',          'route' => 'member',     'roles' => $semua],
    ['label' => 'Laporan',       'icon' => 'fa-solid fa-chart-column',     'route' => 'laporan',    'roles' => $semua],
    ['label' => 'Pengaturan',    'icon' => 'fa-solid fa-gear',             'route' => 'pengaturan', 'roles' => $semua],
];
