<?php

/**
 * Akun pengguna (kasir dan admin/pemilik).
 */
class User extends Model
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function semua(): array
    {
        return self::db()->query(
            'SELECT id, username, nama, role, status, created_at FROM users ORDER BY role ASC, nama ASC'
        )->fetchAll();
    }

    public static function usernameTerpakai(string $username): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $username, string $passwordHash, string $nama, string $role): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO users (username, password_hash, nama, role, status)
             VALUES (:username, :hash, :nama, :role, \'aktif\')'
        );
        $stmt->execute([
            ':username' => $username,
            ':hash'     => $passwordHash,
            ':nama'     => $nama,
            ':role'     => $role,
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function ubahStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public static function ubahNama(int $id, string $nama): void
    {
        $stmt = self::db()->prepare('UPDATE users SET nama = :nama WHERE id = :id');
        $stmt->execute([':nama' => $nama, ':id' => $id]);
    }

    public static function ubahPassword(int $id, string $passwordHash): void
    {
        $stmt = self::db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }
}
