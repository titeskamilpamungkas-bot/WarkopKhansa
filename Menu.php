<?php

/**
 * Barang / menu dan stoknya.
 */
class Menu extends Model
{
    /** Daftar barang dengan pencarian, filter kategori, dan pagination. */
    public static function paginate(string $cari, int $kategoriId): array
    {
        $where = ['1 = 1'];
        $params = [];

        if ($cari !== '') {
            $where[] = '(m.nama LIKE :cari OR m.kode LIKE :cari)';
            $params[':cari'] = '%' . $cari . '%';
        }

        if ($kategoriId > 0) {
            $where[] = 'm.kategori_id = :kategori';
            $params[':kategori'] = $kategoriId;
        }

        $fromWhere = 'FROM menu m JOIN kategori k ON k.id = m.kategori_id WHERE ' . implode(' AND ', $where);

        return Paginator::paginate(
            self::db(),
            'SELECT m.*, k.nama_kategori',
            $fromWhere,
            $params,
            'k.nama_kategori ASC, m.nama ASC',
            10
        );
    }

    /** Menu aktif untuk halaman kasir, diurutkan per kategori. */
    public static function aktifUntukKasir(): array
    {
        return self::db()->query(
            'SELECT m.id, m.kode, m.nama, m.harga_jual, m.stok, m.stok_minimum, m.satuan,
                    m.kategori_id, k.nama_kategori
             FROM menu m
             JOIN kategori k ON k.id = m.kategori_id
             WHERE m.status = \'aktif\'
             ORDER BY k.nama_kategori ASC, m.nama ASC'
        )->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM menu WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function kodeTerpakai(string $kode, ?int $kecualiId = null): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) FROM menu WHERE kode = :kode AND id <> :id'
        );
        $stmt->execute([':kode' => $kode, ':id' => $kecualiId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @param array{kode:string,nama:string,kategori_id:int,harga_beli:float,harga_jual:float,stok:int,stok_minimum:int,satuan:string,status:string} $data */
    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO menu (kode, nama, kategori_id, harga_beli, harga_jual, stok, stok_minimum, satuan, status)
             VALUES (:kode, :nama, :kategori_id, :harga_beli, :harga_jual, :stok, :stok_minimum, :satuan, :status)'
        );
        $stmt->execute($data);

        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = self::db()->prepare(
            'UPDATE menu SET kode = :kode, nama = :nama, kategori_id = :kategori_id,
                    harga_beli = :harga_beli, harga_jual = :harga_jual, stok = :stok,
                    stok_minimum = :stok_minimum, satuan = :satuan, status = :status
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    /** Hapus barang. Melempar PDOException (SQLSTATE 23000) jika sudah pernah terjual. */
    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM menu WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** Ringkasan untuk kartu di halaman barang. */
    public static function ringkasan(): array
    {
        $row = self::db()->query(
            'SELECT COUNT(*) AS total,
                    SUM(stok <= 0) AS habis,
                    SUM(stok > 0 AND stok <= stok_minimum) AS menipis
             FROM menu'
        )->fetch();

        return [
            'total'   => (int) $row['total'],
            'habis'   => (int) $row['habis'],
            'menipis' => (int) $row['menipis'],
        ];
    }

    /** Barang yang stoknya habis atau menipis, untuk peringatan di dashboard. */
    public static function stokRendah(int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT nama, stok, stok_minimum, satuan FROM menu
             WHERE stok <= stok_minimum AND status = \'aktif\'
             ORDER BY stok ASC, nama ASC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
