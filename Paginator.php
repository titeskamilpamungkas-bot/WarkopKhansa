<?php

/**
 * Pagination berbasis LIMIT/OFFSET dengan parameter ?hal= di URL.
 */
class Paginator
{
    /**
     * @param string $select   bagian SELECT, misal "SELECT m.* "
     * @param string $fromWhere bagian FROM ... WHERE ... (tanpa ORDER BY)
     * @param array  $params   parameter bernama, misal [':cari' => '%kopi%']
     * @param string $order    klausa ORDER BY, misal "m.nama ASC"
     */
    public static function paginate(
        PDO $db,
        string $select,
        string $fromWhere,
        array $params,
        string $order,
        int $perHalaman = 10
    ): array {
        $stmtTotal = $db->prepare('SELECT COUNT(*) AS total ' . $fromWhere);
        $stmtTotal->execute($params);
        $totalData = (int) $stmtTotal->fetchColumn();

        $totalHalaman = max(1, (int) ceil($totalData / $perHalaman));
        $halaman = min(max(1, (int) ($_GET['hal'] ?? 1)), $totalHalaman);
        $offset = ($halaman - 1) * $perHalaman;

        $stmt = $db->prepare($select . ' ' . $fromWhere . ' ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset');
        foreach ($params as $kunci => $nilai) {
            $stmt->bindValue($kunci, $nilai);
        }
        $stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'meta' => [
                'halaman'       => $halaman,
                'total_halaman' => $totalHalaman,
                'total_data'    => $totalData,
                'per_halaman'   => $perHalaman,
                'offset'        => $offset,
            ],
        ];
    }

    /** HTML navigasi halaman. Parameter lain di URL (cari, kategori, tanggal) ikut terbawa. */
    public static function html(array $meta): string
    {
        $total = $meta['total_data'];
        $mulai = $total === 0 ? 0 : $meta['offset'] + 1;
        $selesai = min($meta['offset'] + $meta['per_halaman'], $total);
        $cur = $meta['halaman'];
        $akhir = $meta['total_halaman'];

        $html = '<div class="pagination"><span>Menampilkan ' . $mulai . '–' . $selesai . ' dari ' . $total . ' data</span>';
        $html .= '<div class="pagination-links">';
        $html .= self::link($cur - 1, '<i class="fa-solid fa-chevron-left"></i>', $cur <= 1);

        for ($h = 1; $h <= $akhir; $h++) {
            if ($h === 1 || $h === $akhir || abs($h - $cur) <= 1) {
                $html .= self::link($h, (string) $h, false, $h === $cur);
            } elseif ($h === 2 || $h === $akhir - 1) {
                $html .= '<span class="page-link disabled">…</span>';
            }
        }

        $html .= self::link($cur + 1, '<i class="fa-solid fa-chevron-right"></i>', $cur >= $akhir);
        $html .= '</div></div>';

        return $html;
    }

    private static function link(int $hal, string $label, bool $nonaktif, bool $aktif = false): string
    {
        $kelas = 'page-link' . ($aktif ? ' active' : '') . ($nonaktif ? ' disabled' : '');
        if ($nonaktif) {
            return '<span class="' . $kelas . '">' . $label . '</span>';
        }

        $query = $_GET;
        unset($query['r']);
        $query['hal'] = $hal;

        $url = url(Router::currentPath() . '?' . http_build_query($query));

        return '<a class="' . $kelas . '" href="' . e($url) . '">' . $label . '</a>';
    }
}
