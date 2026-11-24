<?php

/**
 * Transaksi penjualan, detailnya, dan query untuk dashboard serta laporan.
 */
class Transaksi extends Model
{
    /** Satu poin untuk setiap kelipatan Rp 10.000 belanja member. */
    private const RUPIAH_PER_POIN = 10000;

    /**
     * Menyimpan transaksi dalam satu DB transaction.
     * Harga dan stok selalu dibaca dari database, bukan dari browser.
     *
     * @param array<int, int> $items menu_id => jumlah
     * @throws RuntimeException jika validasi bisnis gagal (pesan aman untuk ditampilkan)
     */
    public static function buat(int $userId, ?int $memberId, array $items, float $bayar, string $metode): array
    {
        $db = self::db();
        $db->beginTransaction();

        try {
            if ($memberId !== null) {
                $stmt = $db->prepare('SELECT status FROM member WHERE id = :id FOR UPDATE');
                $stmt->execute([':id' => $memberId]);
                $statusMember = $stmt->fetchColumn();

                if ($statusMember !== 'aktif') {
                    throw new RuntimeException('Member tidak ditemukan atau nonaktif.');
                }
            }

            $ids = array_keys($items);
            $tanda = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT id, nama, harga_jual, stok, status FROM menu WHERE id IN ($tanda) FOR UPDATE");
            $stmt->execute($ids);

            $menu = [];
            foreach ($stmt->fetchAll() as $row) {
                $menu[(int) $row['id']] = $row;
            }

            $total = 0.0;
            $baris = [];
            foreach ($items as $menuId => $jumlah) {
                $m = $menu[$menuId] ?? null;

                if ($m === null || $m['status'] !== 'aktif') {
                    throw new RuntimeException('Ada menu yang tidak tersedia. Muat ulang halaman lalu coba lagi.');
                }
                if ($jumlah > (int) $m['stok']) {
                    throw new RuntimeException('Stok ' . $m['nama'] . ' tidak mencukupi (sisa ' . $m['stok'] . ').');
                }

                $harga = (float) $m['harga_jual'];
                $subtotal = $harga * $jumlah;
                $total += $subtotal;

                $baris[] = [
                    'menu_id'  => $menuId,
                    'nama'     => $m['nama'],
                    'harga'    => $harga,
                    'jumlah'   => $jumlah,
                    'subtotal' => $subtotal,
                ];
            }

            if ($bayar < $total) {
                throw new RuntimeException('Nominal bayar kurang dari total belanja.');
            }

            $kode = generateKodeTransaksi();

            $stmt = $db->prepare(
                'INSERT INTO transaksi
                    (kode_transaksi, user_id, member_id, total_harga, diskon, grand_total, bayar, kembalian, metode_pembayaran, status, tanggal)
                 VALUES
                    (:kode, :user_id, :member_id, :total, 0, :grand, :bayar, :kembalian, :metode, \'selesai\', NOW())'
            );
            $stmt->execute([
                ':kode'      => $kode,
                ':user_id'   => $userId,
                ':member_id' => $memberId,
                ':total'     => round($total, 2),
                ':grand'     => round($total, 2),
                ':bayar'     => round($bayar, 2),
                ':kembalian' => round($bayar - $total, 2),
                ':metode'    => $metode,
            ]);
            $transaksiId = (int) $db->lastInsertId();

            $stmtDetail = $db->prepare(
                'INSERT INTO detail_transaksi (transaksi_id, menu_id, nama_menu, harga_satuan, jumlah, subtotal)
                 VALUES (:transaksi, :menu, :nama, :harga, :jumlah, :subtotal)'
            );
            $stmtStok = $db->prepare('UPDATE menu SET stok = stok - :jumlah WHERE id = :id');

            foreach ($baris as $b) {
                $stmtDetail->execute([
                    ':transaksi' => $transaksiId,
                    ':menu'      => $b['menu_id'],
                    ':nama'      => $b['nama'],
                    ':harga'     => round($b['harga'], 2),
                    ':jumlah'    => $b['jumlah'],
                    ':subtotal'  => round($b['subtotal'], 2),
                ]);
                $stmtStok->execute([':jumlah' => $b['jumlah'], ':id' => $b['menu_id']]);
            }

            if ($memberId !== null) {
                $poin = intdiv((int) $total, self::RUPIAH_PER_POIN);
                if ($poin > 0) {
                    Member::tambahPoin($memberId, $poin);
                }
            }

            $db->commit();

            return ['id' => $transaksiId, 'kode' => $kode];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Header transaksi beserta item-itemnya, untuk struk. */
    public static function findDetail(int $id): ?array
    {
        $db = self::db();

        $stmt = $db->prepare(
            'SELECT t.*, u.nama AS nama_kasir, m.nomor_member, m.nama AS nama_member
             FROM transaksi t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN member m ON m.id = t.member_id
             WHERE t.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $header = $stmt->fetch();

        if ($header === false) {
            return null;
        }

        $stmt = $db->prepare('SELECT * FROM detail_transaksi WHERE transaksi_id = :id ORDER BY id ASC');
        $stmt->execute([':id' => $id]);
        $header['items'] = $stmt->fetchAll();

        return $header;
    }

    /** Jumlah transaksi dan omzet pada satu tanggal (format Y-m-d). */
    public static function statistikTanggal(string $tanggal): array
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(*) AS jumlah, COALESCE(SUM(grand_total), 0) AS omzet
             FROM transaksi WHERE status = \'selesai\' AND DATE(tanggal) = :tanggal'
        );
        $stmt->execute([':tanggal' => $tanggal]);
        $row = $stmt->fetch();

        return ['jumlah' => (int) $row['jumlah'], 'omzet' => (float) $row['omzet']];
    }

    /** Omzet per hari dalam rentang tanggal. Hari tanpa penjualan tidak muncul. */
    public static function omzetPerHari(string $dari, string $sampai): array
    {
        $stmt = self::db()->prepare(
            'SELECT DATE(tanggal) AS tgl, COUNT(*) AS jumlah, SUM(grand_total) AS omzet
             FROM transaksi
             WHERE status = \'selesai\' AND DATE(tanggal) BETWEEN :dari AND :sampai
             GROUP BY DATE(tanggal) ORDER BY tgl ASC'
        );
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);

        return $stmt->fetchAll();
    }

    /** Ringkasan satu periode untuk laporan. */
    public static function ringkasanPeriode(string $dari, string $sampai): array
    {
        $db = self::db();

        $stmt = $db->prepare(
            'SELECT COUNT(*) AS jumlah, COALESCE(SUM(grand_total), 0) AS omzet
             FROM transaksi WHERE status = \'selesai\' AND DATE(tanggal) BETWEEN :dari AND :sampai'
        );
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
        $row = $stmt->fetch();

        $stmt = $db->prepare(
            'SELECT COALESCE(SUM(d.jumlah), 0) FROM detail_transaksi d
             JOIN transaksi t ON t.id = d.transaksi_id
             WHERE t.status = \'selesai\' AND DATE(t.tanggal) BETWEEN :dari AND :sampai'
        );
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
        $item = (int) $stmt->fetchColumn();

        $jumlah = (int) $row['jumlah'];
        $omzet = (float) $row['omzet'];

        return [
            'jumlah' => $jumlah,
            'omzet'  => $omzet,
            'item'   => $item,
            'rata'   => $jumlah > 0 ? $omzet / $jumlah : 0.0,
        ];
    }

    /** Barang paling laku dalam rentang tanggal. */
    public static function produkTerlaris(string $dari, string $sampai, int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT d.nama_menu, SUM(d.jumlah) AS qty, SUM(d.subtotal) AS omzet
             FROM detail_transaksi d
             JOIN transaksi t ON t.id = d.transaksi_id
             WHERE t.status = \'selesai\' AND DATE(t.tanggal) BETWEEN :dari AND :sampai
             GROUP BY d.menu_id, d.nama_menu
             ORDER BY qty DESC, omzet DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':dari', $dari);
        $stmt->bindValue(':sampai', $sampai);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Omzet per kategori dalam rentang tanggal. */
    public static function kategoriTerlaris(string $dari, string $sampai): array
    {
        $stmt = self::db()->prepare(
            'SELECT k.nama_kategori, SUM(d.jumlah) AS qty, SUM(d.subtotal) AS omzet
             FROM detail_transaksi d
             JOIN transaksi t ON t.id = d.transaksi_id
             JOIN menu m ON m.id = d.menu_id
             JOIN kategori k ON k.id = m.kategori_id
             WHERE t.status = \'selesai\' AND DATE(t.tanggal) BETWEEN :dari AND :sampai
             GROUP BY k.id, k.nama_kategori
             ORDER BY omzet DESC'
        );
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);

        return $stmt->fetchAll();
    }

    /** Transaksi terakhir untuk dashboard. */
    public static function terbaru(int $limit = 5): array
    {
        $stmt = self::db()->prepare(
            'SELECT t.id, t.kode_transaksi, t.tanggal, t.grand_total, t.metode_pembayaran, u.nama AS nama_kasir
             FROM transaksi t JOIN users u ON u.id = t.user_id
             WHERE t.status = \'selesai\'
             ORDER BY t.tanggal DESC, t.id DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Riwayat transaksi dalam rentang tanggal, dengan pagination. */
    public static function paginateRiwayat(string $dari, string $sampai): array
    {
        $fromWhere = 'FROM transaksi t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN member m ON m.id = t.member_id
             WHERE t.status = \'selesai\' AND DATE(t.tanggal) BETWEEN :dari AND :sampai';

        return Paginator::paginate(
            self::db(),
            'SELECT t.id, t.kode_transaksi, t.tanggal, t.total_harga, t.grand_total, t.bayar,
                    t.kembalian, t.metode_pembayaran, u.nama AS nama_kasir, m.nama AS nama_member',
            $fromWhere,
            [':dari' => $dari, ':sampai' => $sampai],
            't.tanggal DESC, t.id DESC',
            10
        );
    }
}
