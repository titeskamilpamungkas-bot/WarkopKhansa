<?php

/**
 * Router: mencocokkan URL dengan rute, memeriksa CSRF, lalu memeriksa akses role
 * sebelum controller dijalankan.
 */
class Router
{
    private static string $path = '';

    /** Path rute yang sedang diproses, misal "barang" atau "pengaturan/akun". */
    public static function currentPath(): string
    {
        return self::$path;
    }

    public static function dispatch(): void
    {
        $metode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($metode === 'HEAD') {
            $metode = 'GET';
        }

        self::$path = trim((string) ($_GET['r'] ?? ''), '/');

        $rute = require ROOT_PATH . '/config/routes.php';
        $daftar = $rute[$metode] ?? [];

        if (!isset($daftar[self::$path])) {
            abort(404);
        }

        [$handler, $akses] = $daftar[self::$path];

        // Semua form POST wajib membawa token CSRF yang valid
        if ($metode === 'POST' && !Csrf::valid($_POST['_token'] ?? null)) {
            abort(419, 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
        }

        self::periksaAkses($akses);

        [$kelas, $aksi] = explode('@', $handler, 2);
        $controller = new $kelas();
        $controller->$aksi();
    }

    /**
     * Validasi akses:
     *  - 'public' : semua boleh
     *  - 'guest'  : hanya yang belum login
     *  - 'auth'   : harus login
     *  - array    : harus login dan role-nya ada di array
     */
    private static function periksaAkses(mixed $akses): void
    {
        if ($akses === 'public') {
            return;
        }

        if ($akses === 'guest') {
            $user = Auth::user();
            if ($user !== null) {
                redirect(Role::beranda($user['role']));
            }
            return;
        }

        $user = Auth::user();
        if ($user === null) {
            Session::flash('warning', 'Silakan login terlebih dahulu.');
            redirect('login');
        }

        if ($akses === 'auth') {
            return;
        }

        if (!Role::izinkan($user['role'], (array) $akses)) {
            abort(403, 'Role Anda (' . Role::label($user['role']) . ') tidak memiliki izin membuka halaman ini.');
        }
    }
}
