<?php

/**
 * Validasi role.
 *
 * Sistem membaca daftar role dari config/roles.php dan mencocokkannya
 * dengan kolom users.role di database. Role dipilih oleh database,
 * bukan oleh pengguna di halaman login.
 */
class Role
{
    private static ?array $daftar = null;

    /** @return array<string, array{label: string, beranda: string}> */
    public static function semua(): array
    {
        if (self::$daftar === null) {
            self::$daftar = require ROOT_PATH . '/config/roles.php';
        }

        return self::$daftar;
    }

    /** Apakah string role ini dikenali sistem? */
    public static function isValid(mixed $role): bool
    {
        return is_string($role) && array_key_exists($role, self::semua());
    }

    public static function label(?string $role): string
    {
        return self::isValid($role) ? self::semua()[$role]['label'] : 'Tidak dikenal';
    }

    public static function beranda(string $role): string
    {
        return self::isValid($role) ? self::semua()[$role]['beranda'] : 'login';
    }

    /** Apakah role valid dan termasuk dalam daftar yang diizinkan? */
    public static function izinkan(mixed $role, array $daftarIzin): bool
    {
        return self::isValid($role) && in_array($role, $daftarIzin, true);
    }
}
