<?php
/*
 * Fungsi bantu global. Dimuat sekali dari index.php.
 */

/** Escape output HTML. Gunakan untuk SEMUA data yang ditampilkan ke layar. */
function e(mixed $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Membentuk URL aplikasi. Contoh: url('barang?hal=2') */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');

    if (PRETTY_URL) {
        return BASE_URL . '/' . $path;
    }

    $query = '';
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
    }

    return BASE_URL . '/index.php?r=' . $path . ($query !== '' ? '&' . $query : '');
}

/** URL file statis (css, js, img) dengan penanda versi agar cache ter-update. */
function asset(string $file): string
{
    $lokasi = ROOT_PATH . '/assets/' . $file;
    $versi = is_file($lokasi) ? filemtime($lokasi) : time();

    return BASE_URL . '/assets/' . $file . '?v=' . $versi;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Hentikan permintaan dan tampilkan halaman error.
 */
function abort(int $kode, string $pesan = ''): void
{
    http_response_code($kode);

    $judul = match ($kode) {
        403     => 'Akses Ditolak',
        404     => 'Halaman Tidak Ditemukan',
        419     => 'Sesi Kedaluwarsa',
        default => 'Terjadi Kesalahan',
    };

    if ($pesan === '') {
        $pesan = match ($kode) {
            403     => 'Anda tidak memiliki izin untuk membuka halaman ini.',
            404     => 'Alamat yang Anda tuju tidak tersedia.',
            default => 'Silakan coba lagi beberapa saat.',
        };
    }

    View::render('errors/halaman', ['kode' => $kode, 'judul' => $judul, 'pesan' => $pesan], 'blank');
    exit;
}

/** Format rupiah: 35000 menjadi "Rp 35.000" */
function rupiah(float|int|string $angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

const BULAN_INDO = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

/** "2026-11-09" menjadi "9 November 2026" */
function tanggalIndo(string $tanggal): string
{
    $waktu = strtotime($tanggal);
    if ($waktu === false) {
        return '-';
    }

    return date('j', $waktu) . ' ' . BULAN_INDO[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
}

/** "2026-11-09 14:30:00" menjadi "9 Nov 2026, 14:30" */
function tanggalWaktuIndo(string $waktu): string
{
    $ts = strtotime($waktu);
    if ($ts === false) {
        return '-';
    }

    return date('j', $ts) . ' ' . mb_substr(BULAN_INDO[(int) date('n', $ts)], 0, 3) . ' '
        . date('Y', $ts) . ', ' . date('H:i', $ts);
}

/** Status stok: habis, menipis, atau aman. */
function stokStatus(int $stok, int $stokMinimum): array
{
    if ($stok <= 0) {
        return ['label' => 'Habis', 'kelas' => 'badge-habis'];
    }
    if ($stok <= $stokMinimum) {
        return ['label' => 'Menipis', 'kelas' => 'badge-menipis'];
    }

    return ['label' => 'Aman', 'kelas' => 'badge-aman'];
}

function badgeStok(int $stok, int $stokMinimum): string
{
    $status = stokStatus($stok, $stokMinimum);

    return '<span class="badge ' . e($status['kelas']) . '">' . e($status['label']) . '</span>';
}

function labelMetode(string $metode): string
{
    return match ($metode) {
        'tunai'    => 'Tunai',
        'qris'     => 'QRIS',
        'transfer' => 'Transfer',
        default    => ucfirst($metode),
    };
}

/** Ikon Font Awesome untuk setiap kategori produk. */
function ikonKategori(string $nama): string
{
    return match (strtolower($nama)) {
        'rokok'   => 'fa-solid fa-smoking',
        'kopi'    => 'fa-solid fa-mug-hot',
        'minuman' => 'fa-solid fa-glass-water',
        'makanan' => 'fa-solid fa-bowl-food',
        'gas'     => 'fa-solid fa-fire-flame-simple',
        'galon'   => 'fa-solid fa-droplet',
        default   => 'fa-solid fa-utensils',
    };
}

/** Kode transaksi unik, contoh: TRX-20261109-4821 */
function generateKodeTransaksi(): string
{
    return 'TRX-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

/** Nilai atribut data-* yang aman untuk JSON. */
function dataJson(array $data): string
{
    return e(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}

/** Kode baru untuk barang atau member, misal: prefix "MBR" menghasilkan "MBR-0007". */
function nomorBaru(string $prefix, int $nomorTerakhir, int $lebar, string $pemisah = '-'): string
{
    return $prefix . $pemisah . str_pad((string) ($nomorTerakhir + 1), $lebar, '0', STR_PAD_LEFT);
}
