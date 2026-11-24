<?php

/**
 * Kategori produk (Rokok, Kopi, Minuman, Makanan, Gas, Galon, ...).
 */
class Kategori extends Model
{
    public static function semua(): array
    {
        return self::db()->query(
            'SELECT id, nama_kategori, deskripsi FROM kategori ORDER BY nama_kategori ASC'
        )->fetchAll();
    }

    public static function namaTerpakai(string $nama): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM kategori WHERE nama_kategori = :nama');
        $stmt->execute([':nama' => $nama]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $nama, ?string $deskripsi): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO kategori (nama_kategori, deskripsi) VALUES (:nama, :deskripsi)'
        );
        $stmt->execute([':nama' => $nama, ':deskripsi' => $deskripsi]);

        return (int) self::db()->lastInsertId();
    }
}
