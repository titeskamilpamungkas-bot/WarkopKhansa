<?php

/**
 * Koneksi database tunggal (PDO) untuk seluruh aplikasi.
 */
class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
                . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // Samakan zona waktu MySQL dengan WIB agar CURDATE() dan NOW() sesuai
                self::$pdo->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e) {
                abort(500, DEBUG ? 'Koneksi database gagal: ' . $e->getMessage() : 'Koneksi database gagal.');
            }
        }

        return self::$pdo;
    }
}
