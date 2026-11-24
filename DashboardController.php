<?php

/**
 * Dashboard: statistik dari database, grafik 7 hari, dan peringatan stok.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        $hariIni = date('Y-m-d');
        $kemarin = date('Y-m-d', strtotime('-1 day'));
        $awalBulan = date('Y-m-01');

        $statHariIni = Transaksi::statistikTanggal($hariIni);
        $statKemarin = Transaksi::statistikTanggal($kemarin);

        $grafik = $this->grafikTujuhHari($hariIni);

        $this->view('dashboard/index', [
            'title'       => 'Dashboard',
            'pageTitle'   => 'Dashboard',
            'user'        => $this->user(),
            'omzetHari'   => $statHariIni['omzet'],
            'trxHari'     => $statHariIni['jumlah'],
            'trendOmzet'  => $this->trend($statHariIni['omzet'], $statKemarin['omzet'], 'kemarin'),
            'trendTrx'    => $this->trend((float) $statHariIni['jumlah'], (float) $statKemarin['jumlah'], 'kemarin'),
            'member'      => Member::ringkasan(),
            'menu'        => Menu::ringkasan(),
            'stokRendah'  => Menu::stokRendah(5),
            'terlaris'    => Transaksi::produkTerlaris($awalBulan, $hariIni, 5),
            'kategori'    => Transaksi::kategoriTerlaris($awalBulan, $hariIni),
            'terbaru'     => Transaksi::terbaru(5),
            'grafik'      => $grafik,
            'bulanIni'    => tanggalIndo($awalBulan) . ' – ' . tanggalIndo($hariIni),
        ]);
    }

    /** Omzet 7 hari terakhir, hari tanpa penjualan diisi nol. */
    private function grafikTujuhHari(string $hariIni): array
    {
        $dari = date('Y-m-d', strtotime('-6 days'));
        $perHari = [];
        foreach (Transaksi::omzetPerHari($dari, $hariIni) as $baris) {
            $perHari[$baris['tgl']] = (float) $baris['omzet'];
        }

        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('j M', strtotime($tgl));
            $data[] = $perHari[$tgl] ?? 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Teks dan arah perubahan dibanding periode pembanding. */
    private function trend(float $sekarang, float $pembanding, string $namaPembanding): array
    {
        if ($pembanding <= 0) {
            return $sekarang > 0
                ? ['arah' => 'naik', 'teks' => 'Belum ada data ' . $namaPembanding]
                : ['arah' => 'netral', 'teks' => 'Belum ada penjualan'];
        }

        $persen = (($sekarang - $pembanding) / $pembanding) * 100;
        $arah = $persen > 0 ? 'naik' : ($persen < 0 ? 'turun' : 'netral');
        $tanda = $persen > 0 ? '+' : '';

        return [
            'arah' => $arah,
            'teks' => $tanda . number_format($persen, 1, ',', '.') . '% dari ' . $namaPembanding,
        ];
    }
}
