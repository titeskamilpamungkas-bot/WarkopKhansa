<?php
declare(strict_types=1);

/*
 * Front controller WARKOP KHANSA.
 * Semua permintaan masuk lewat file ini (lihat .htaccess).
 */

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');

// Autoload sederhana: memuat class dari folder Core, Models, Controllers, Helpers
spl_autoload_register(function (string $kelas): void {
    foreach (['Core', 'Models', 'Controllers', 'Helpers'] as $folder) {
        $file = APP_PATH . '/' . $folder . '/' . $kelas . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require ROOT_PATH . '/config/config.php';
require APP_PATH . '/Helpers/functions.php';

date_default_timezone_set(TIMEZONE);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

Session::start();
Router::dispatch();
