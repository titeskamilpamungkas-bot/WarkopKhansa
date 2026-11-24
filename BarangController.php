<?php

/**
 * Barang & stok. Melihat: semua role. Mengubah: admin saja (dijaga Router).
 */
class BarangController extends Controller
{
    public function index(): void
    {
        $cari = $this->get('q');
        $kategoriId = $this->angka('get', 'kategori');

        $this->view('barang/index', [
            'title'     => 'Barang & Stok',
            'pageTitle' => 'Barang & Stok',
            'hasil'     => Menu::paginate($cari, $kategoriId),
            'kategori'  => Kategori::semua(),
            'ringkasan' => Menu::ringkasan(),
            'cari'      => $cari,
            'kategoriAktif' => $kategoriId,
            'scripts'   => [],
        ]);
    }

    public function tambah(): void
    {
        [$data, $error] = $this->validasiBarang(null);

        if ($error !== null) {
            Session::flash('danger', $error);
            $this->redirect('barang');
        }

        Menu::create($data);
        Session::flash('success', 'Barang ' . $data['nama'] . ' berhasil ditambahkan.');
        $this->redirect('barang');
    }

    public function edit(): void
    {
        $id = $this->angka('post', 'id');
        if ($id <= 0 || Menu::findById($id) === null) {
            Session::flash('danger', 'Barang tidak ditemukan.');
            $this->redirect('barang');
        }

        [$data, $error] = $this->validasiBarang($id);

        if ($error !== null) {
            Session::flash('danger', $error);
            $this->redirect('barang');
        }

        Menu::update($id, $data);
        Session::flash('success', 'Data ' . $data['nama'] . ' berhasil diperbarui.');
        $this->redirect('barang');
    }

    public function hapus(): void
    {
        $id = $this->angka('post', 'id');
        $menu = $id > 0 ? Menu::findById($id) : null;

        if ($menu === null) {
            Session::flash('danger', 'Barang tidak ditemukan.');
            $this->redirect('barang');
        }

        try {
            Menu::delete($id);
            Session::flash('success', 'Barang ' . $menu['nama'] . ' berhasil dihapus.');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Session::flash('warning', $menu['nama'] . ' sudah pernah terjual sehingga tidak bisa dihapus. Ubah statusnya menjadi nonaktif saja.');
            } else {
                throw $e;
            }
        }

        $this->redirect('barang');
    }

    public function tambahKategori(): void
    {
        $nama = $this->post('nama_kategori');
        $deskripsi = $this->post('deskripsi');

        if ($nama === '' || mb_strlen($nama) > 50) {
            Session::flash('danger', 'Nama kategori wajib diisi (maksimal 50 karakter).');
            $this->redirect('barang');
        }

        if (Kategori::namaTerpakai($nama)) {
            Session::flash('danger', 'Nama kategori "' . $nama . '" sudah ada.');
            $this->redirect('barang');
        }

        Kategori::create($nama, $deskripsi !== '' ? mb_substr($deskripsi, 0, 255) : null);
        Session::flash('success', 'Kategori ' . $nama . ' berhasil ditambahkan.');
        $this->redirect('barang');
    }

    /**
     * Membaca dan memeriksa form barang.
     * Mengembalikan [data, null] jika valid, atau [[], pesan] jika tidak.
     */
    private function validasiBarang(?int $idYangDiubah): array
    {
        $kode = strtoupper($this->post('kode'));
        $nama = $this->post('nama');
        $kategoriId = $this->angka('post', 'kategori_id');
        $hargaBeli = $this->post('harga_beli', '0');
        $hargaJual = $this->post('harga_jual');
        $stok = $this->post('stok', '0');
        $stokMinimum = $this->post('stok_minimum', '5');
        $satuan = $this->post('satuan', 'porsi');
        $status = $this->post('status', 'aktif');

        if ($kode === '' || mb_strlen($kode) > 20) {
            return [[], 'Kode barang wajib diisi (maksimal 20 karakter).'];
        }
        if (Menu::kodeTerpakai($kode, $idYangDiubah)) {
            return [[], 'Kode ' . $kode . ' sudah dipakai barang lain.'];
        }
        if ($nama === '' || mb_strlen($nama) > 100) {
            return [[], 'Nama barang wajib diisi (maksimal 100 karakter).'];
        }
        if (!$this->cocokKategori($kategoriId)) {
            return [[], 'Kategori tidak valid.'];
        }
        if (!is_numeric($hargaBeli) || (float) $hargaBeli < 0) {
            return [[], 'Harga beli harus berupa angka 0 atau lebih.'];
        }
        if (!is_numeric($hargaJual) || (float) $hargaJual <= 0) {
            return [[], 'Harga jual harus lebih dari 0.'];
        }
        if (!ctype_digit($stok)) {
            return [[], 'Stok harus berupa bilangan bulat 0 atau lebih.'];
        }
        if (!ctype_digit($stokMinimum)) {
            return [[], 'Stok minimum harus berupa bilangan bulat 0 atau lebih.'];
        }
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            return [[], 'Status barang tidak valid.'];
        }

        return [[
            'kode'         => $kode,
            'nama'         => $nama,
            'kategori_id'  => $kategoriId,
            'harga_beli'   => round((float) $hargaBeli, 2),
            'harga_jual'   => round((float) $hargaJual, 2),
            'stok'         => (int) $stok,
            'stok_minimum' => (int) $stokMinimum,
            'satuan'       => mb_substr($satuan !== '' ? $satuan : 'porsi', 0, 20),
            'status'       => $status,
        ], null];
    }

    private function cocokKategori(int $id): bool
    {
        foreach (Kategori::semua() as $k) {
            if ((int) $k['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}
