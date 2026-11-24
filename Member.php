<?php

/**
 * Data member / pelanggan tetap.
 */
class Member extends Model
{
    public static function paginate(string $cari): array
    {
        $where = ['1 = 1'];
        $params = [];

        if ($cari !== '') {
            $where[] = '(nama LIKE :cari OR nomor_member LIKE :cari OR no_hp LIKE :cari)';
            $params[':cari'] = '%' . $cari . '%';
        }

        $fromWhere = 'FROM member WHERE ' . implode(' AND ', $where);

        return Paginator::paginate(self::db(), 'SELECT *', $fromWhere, $params, 'nama ASC', 10);
    }

    /** Member aktif untuk pilihan di halaman kasir. */
    public static function aktifUntukKasir(): array
    {
        return self::db()->query(
            'SELECT id, nomor_member, nama, poin FROM member WHERE status = \'aktif\' ORDER BY nama ASC'
        )->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM member WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** Membuat member baru dengan nomor otomatis, misal MBR-0007. */
    public static function create(string $nama, ?string $noHp, ?string $alamat): string
    {
        $db = self::db();
        $terakhir = (int) $db->query('SELECT COALESCE(MAX(id), 0) FROM member')->fetchColumn();
        $nomor = nomorBaru('MBR', $terakhir, 4);

        $stmt = $db->prepare(
            'INSERT INTO member (nomor_member, nama, no_hp, alamat, status, tanggal_daftar)
             VALUES (:nomor, :nama, :no_hp, :alamat, \'aktif\', CURDATE())'
        );
        $stmt->execute([
            ':nomor'  => $nomor,
            ':nama'   => $nama,
            ':no_hp'  => $noHp,
            ':alamat' => $alamat,
        ]);

        return $nomor;
    }

    public static function update(int $id, string $nama, ?string $noHp, ?string $alamat, string $status): void
    {
        $stmt = self::db()->prepare(
            'UPDATE member SET nama = :nama, no_hp = :no_hp, alamat = :alamat, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            ':nama'   => $nama,
            ':no_hp'  => $noHp,
            ':alamat' => $alamat,
            ':status' => $status,
            ':id'     => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM member WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function ringkasan(): array
    {
        $row = self::db()->query(
            'SELECT COUNT(*) AS total,
                    SUM(status = \'aktif\') AS aktif,
                    SUM(YEAR(tanggal_daftar) = YEAR(CURDATE()) AND MONTH(tanggal_daftar) = MONTH(CURDATE())) AS baru_bulan_ini
             FROM member'
        )->fetch();

        return [
            'total'         => (int) $row['total'],
            'aktif'         => (int) $row['aktif'],
            'baru_bulan_ini' => (int) $row['baru_bulan_ini'],
        ];
    }

    public static function tambahPoin(int $id, int $poin): void
    {
        $stmt = self::db()->prepare('UPDATE member SET poin = poin + :poin WHERE id = :id');
        $stmt->execute([':poin' => $poin, ':id' => $id]);
    }
}
