<?php
/*
 * Konfigurasi utama aplikasi WARKOP KHANSA.
 */

define('APP_NAME', 'WARKOP KHANSA');
define('DEBUG', true);            // ubah menjadi false saat sudah online
define('PRETTY_URL', true);       // ubah menjadi false jika mod_rewrite belum aktif (URL memakai ?r=)
define('TIMEZONE', 'Asia/Jakarta');

// Koneksi database (XAMPP default)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'warkop_khansa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Alamat dasar otomatis, contoh: /warkop-khansa
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'));
