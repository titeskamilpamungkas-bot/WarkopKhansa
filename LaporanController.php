<?php

/**
 * Laporan penjualan dengan filter tanggal, grafik, dan cetak.
 */
class LaporanController extends Controller
{
    private const MAKS_HARI_GRAFIK = 62;

    public function index(): void
    {
        [$dari, $sampai] = $this->periode();

        $this->view('laporan/index', [
            'title'      => 'Laporan',
            'pageTitle'  => 'Laporan Penjualan',
            'dari'       => $dari,
            'sampai'     => $sampai,
            'presets'    => $this->presets(),
            'ringkasan'  => Transaksi::ringkasanPeriode($dari, $sampai),
            'terlaris'   => Transaksi::produkTerlaris($dari, $sampai, 10),
            'kategori'   => Transaksi::kategoriTerlaris($dari, $sampai),
            'hasil'      => Transaksi::paginateRiwayat($dari, $sampai),
            'grafik'     => $this->grafik($dari, $sampai),
            'periodeTeks' => tanggalIndo($dari) . ' s/d ' . tanggalIndo($sampai),
        ]);
    }

    /** Membaca tanggal dari URL. Jika tidak valid, pakai bulan berjalan. */
    private function periode(): array
    {
        $dari = $this->tanggal($this->get('dari'));
        $sampai = $this->tanggal($this->get('sampai'));

        if ($dari === null || $sampai === null) {
            return [date('Y-m-01'), date('Y-m-d')];
        }
        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        return [$dari, $sampai];
    }

    private function tanggal(string $teks): ?string
    {
        if ($teks === '') {
            return null;
        }

        $obj = DateTime::createFromFormat('Y-m-d', $teks);

        return ($obj && $obj->format('Y-m-d') === $teks) ? $teks : null;
    }

    /** Tautan periode cepat untuk tombol di halaman laporan. */
    private function presets(): array
    {
        $hari = date('Y-m-d');

        return [
            'Hari ini'     => [$hari, $hari],
            '7 hari'       => [date('Y-m-d', strtotime('-6 days')), $hari],
            'Bulan ini'    => [date('Y-m-01'), $hari],
            'Bulan lalu'   => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        ];
    }

    /** Omzet per hari dalam rentang. Hari tanpa penjualan diisi nol. */
    private function grafik(string $dari, string $sampai): array
    {
        $awal = new DateTime($dari);
        $akhir = new DateTime($sampai);
        $jumlahHari = (int) $awal->diff($akhir)->days + 1;

        if ($jumlahHari > self::MAKS_HARI_GRAFIK) {
            $awal = (clone $akhir)->modify('-' . (self::MAKS_HARI_GRAFIK - 1) . ' days');
        }

        $perHari = [];
        foreach (Transaksi::omzetPerHari($awal->format('Y-m-d'), $sampai) as $baris) {
            $perHari[$baris['tgl']] = (float) $baris['omzet'];
        }

        $labels = [];
        $data = [];
        $periode = new DatePeriod($awal, new DateInterval('P1D'), (clone $akhir)->modify('+1 day'));
        foreach ($periode as $tgl) {
            $kunci = $tgl->format('Y-m-d');
            $labels[] = $tgl->format('j M');
            $data[] = $perHari[$kunci] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
