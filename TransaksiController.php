<?php

/**
 * Halaman kasir: pilih menu, proses pembayaran, dan cetak struk.
 */
class TransaksiController extends Controller
{
    private const METODE_VALID = ['tunai', 'qris', 'transfer'];

    public function index(): void
    {
        // Token sekali pakai: mencegah transaksi tercatat dua kali jika form dikirim ulang
        $nonce = bin2hex(random_bytes(16));
        Session::set('nonce_transaksi', $nonce);

        $menu = Menu::aktifUntukKasir();

        $kategori = [];
        foreach ($menu as $m) {
            $kategori[$m['nama_kategori']] = true;
        }

        $this->view('transaksi/index', [
            'title'     => 'Transaksi',
            'pageTitle' => 'Transaksi Kasir',
            'menu'      => $menu,
            'kategori'  => array_keys($kategori),
            'member'    => Member::aktifUntukKasir(),
            'nonce'     => $nonce,
            'scripts'   => ['js/transaksi.js'],
        ]);
    }

    public function proses(): void
    {
        // Cek token sekali pakai
        $nonceForm = $this->post('nonce');
        $nonceSesi = Session::get('nonce_transaksi');
        Session::set('nonce_transaksi', null);

        if ($nonceSesi === null || !hash_equals((string) $nonceSesi, $nonceForm)) {
            Session::flash('warning', 'Transaksi ini sudah diproses atau formulir sudah kedaluwarsa.');
            $this->redirect('transaksi');
        }

        $items = $this->parseKeranjang($this->post('keranjang'));
        if ($items === null) {
            Session::flash('danger', 'Keranjang tidak valid. Tambahkan menu lalu coba lagi.');
            $this->redirect('transaksi');
        }

        $memberRaw = $this->post('member_id');
        $memberId = ctype_digit($memberRaw) && (int) $memberRaw > 0 ? (int) $memberRaw : null;

        $metode = $this->post('metode', 'tunai');
        if (!in_array($metode, self::METODE_VALID, true)) {
            $metode = 'tunai';
        }

        $bayarRaw = preg_replace('/[^0-9]/', '', $this->post('bayar'));
        if ($bayarRaw === '' || $bayarRaw === null) {
            Session::flash('danger', 'Nominal bayar wajib diisi dengan angka.');
            $this->redirect('transaksi');
        }

        try {
            $hasil = Transaksi::buat(
                (int) $this->user()['id'],
                $memberId,
                $items,
                (float) $bayarRaw,
                $metode
            );
        } catch (RuntimeException $e) {
            Session::flash('danger', $e->getMessage());
            $this->redirect('transaksi');
        } catch (PDOException $e) {
            error_log('Transaksi gagal: ' . $e->getMessage());
            Session::flash('danger', 'Transaksi gagal disimpan. Silakan coba lagi.');
            $this->redirect('transaksi');
        }

        Session::flash('success', 'Transaksi ' . $hasil['kode'] . ' berhasil disimpan.');
        $this->redirect('struk?id=' . $hasil['id']);
    }

    public function struk(): void
    {
        $id = $this->angka('get', 'id');
        $transaksi = $id > 0 ? Transaksi::findDetail($id) : null;

        if ($transaksi === null) {
            abort(404, 'Struk transaksi tidak ditemukan.');
        }

        $this->view('transaksi/struk', [
            'title' => 'Struk ' . $transaksi['kode_transaksi'],
            'trx'   => $transaksi,
        ], 'blank');
    }

    /**
     * Membaca keranjang dari JSON: [{menu_id, jumlah}, ...].
     * Mengembalikan null jika ada data yang tidak valid.
     *
     * @return array<int, int>|null menu_id => jumlah
     */
    private function parseKeranjang(string $json): ?array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || $data === []) {
            return null;
        }

        $hasil = [];
        foreach ($data as $baris) {
            if (!is_array($baris)) {
                return null;
            }

            $menuId = filter_var($baris['menu_id'] ?? null, FILTER_VALIDATE_INT);
            $jumlah = filter_var($baris['jumlah'] ?? null, FILTER_VALIDATE_INT);

            if ($menuId === false || $jumlah === false || $menuId <= 0 || $jumlah <= 0 || $jumlah > 999) {
                return null;
            }

            $hasil[$menuId] = ($hasil[$menuId] ?? 0) + $jumlah;
        }

        return $hasil;
    }
}
