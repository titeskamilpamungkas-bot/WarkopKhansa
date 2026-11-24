<?php

/**
 * Proteksi CSRF: setiap form POST wajib membawa token yang cocok dengan session.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    /** HTML input tersembunyi untuk dimasukkan ke dalam setiap form POST. */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function valid(mixed $token): bool
    {
        return is_string($token)
            && $token !== ''
            && isset($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }
}
