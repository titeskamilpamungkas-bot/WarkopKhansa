<?php

/**
 * Pembungkus session PHP: data login, flash message, dan pengamanan cookie.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('warkop_khansa_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $kunci, mixed $default = null): mixed
    {
        return $_SESSION[$kunci] ?? $default;
    }

    public static function set(string $kunci, mixed $nilai): void
    {
        $_SESSION[$kunci] = $nilai;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }

        session_destroy();
    }

    public static function flash(string $tipe, string $pesan): void
    {
        $_SESSION['_flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
    }

    /** Ambil semua flash message lalu hapus dari session (hanya tampil sekali). */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $flash;
    }
}
