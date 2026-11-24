<?php

/**
 * Pengaturan: akun sendiri (semua role) dan kelola pengguna (admin saja).
 */
class PengaturanController extends Controller
{
    public function index(): void
    {
        $user = $this->user();
        $tab = $this->get('tab', 'akun');

        if (!in_array($tab, ['akun', 'pengguna', 'tampilan'], true)) {
            $tab = 'akun';
        }
        if ($tab === 'pengguna' && $user['role'] !== 'admin') {
            $tab = 'akun';
        }

        $this->view('pengaturan/index', [
            'title'     => 'Pengaturan',
            'pageTitle' => 'Pengaturan',
            'user'      => $user,
            'tab'       => $tab,
            'pengguna'  => $user['role'] === 'admin' ? User::semua() : [],
            'daftarRole' => Role::semua(),
            'scripts'   => [],
        ]);
    }

    /** Ubah nama dan/atau password akun yang sedang login. */
    public function ubahAkun(): void
    {
        $user = $this->user();
        $nama = $this->post('nama');
        $passwordLama = is_string($_POST['password_lama'] ?? null) ? $_POST['password_lama'] : '';
        $passwordBaru = is_string($_POST['password_baru'] ?? null) ? $_POST['password_baru'] : '';
        $konfirmasi = is_string($_POST['konfirmasi'] ?? null) ? $_POST['konfirmasi'] : '';

        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->kembali('akun', 'danger', 'Nama wajib diisi (maksimal 100 karakter).');
        }

        if ($passwordBaru !== '') {
            if (!password_verify($passwordLama, $user['password_hash'])) {
                $this->kembali('akun', 'danger', 'Password lama tidak sesuai. Perubahan belum disimpan.');
            }
            if (strlen($passwordBaru) < 6) {
                $this->kembali('akun', 'danger', 'Password baru minimal 6 karakter.');
            }
            if ($passwordBaru !== $konfirmasi) {
                $this->kembali('akun', 'danger', 'Konfirmasi password tidak sama.');
            }
        }

        // Semua validasi lolos, baru simpan
        User::ubahNama((int) $user['id'], $nama);
        if ($passwordBaru !== '') {
            User::ubahPassword((int) $user['id'], password_hash($passwordBaru, PASSWORD_DEFAULT));
        }

        $this->kembali('akun', 'success', 'Data akun berhasil disimpan.');
    }

    /** Tambah pengguna baru. Role dipilih dari daftar yang dikenali sistem. */
    public function tambahUser(): void
    {
        $username = $this->post('username');
        $nama = $this->post('nama');
        $role = $this->post('role');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        if (!preg_match('/^[a-zA-Z0-9_.]{3,50}$/', $username)) {
            $this->kembali('pengguna', 'danger', 'Username 3–50 karakter, hanya huruf, angka, titik, atau garis bawah.');
        }
        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->kembali('pengguna', 'danger', 'Nama wajib diisi (maksimal 100 karakter).');
        }
        if (!Role::isValid($role)) {
            $this->kembali('pengguna', 'danger', 'Role tidak dikenali sistem.');
        }
        if (strlen($password) < 6) {
            $this->kembali('pengguna', 'danger', 'Password minimal 6 karakter.');
        }
        if (User::usernameTerpakai($username)) {
            $this->kembali('pengguna', 'danger', 'Username ' . $username . ' sudah dipakai.');
        }

        User::create($username, password_hash($password, PASSWORD_DEFAULT), $nama, $role);
        $this->kembali('pengguna', 'success', 'Akun ' . $username . ' (' . Role::label($role) . ') berhasil dibuat.');
    }

    /** Aktifkan atau nonaktifkan akun. Akun sendiri tidak boleh dinonaktifkan. */
    public function ubahStatusUser(): void
    {
        $id = $this->angka('post', 'id');
        $target = $id > 0 ? User::findById($id) : null;

        if ($target === null) {
            $this->kembali('pengguna', 'danger', 'Akun tidak ditemukan.');
        }
        if ($id === (int) $this->user()['id']) {
            $this->kembali('pengguna', 'warning', 'Anda tidak bisa menonaktifkan akun yang sedang dipakai.');
        }

        $statusBaru = $target['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        User::ubahStatus($id, $statusBaru);

        $this->kembali('pengguna', 'success', 'Akun ' . $target['username'] . ' sekarang ' . $statusBaru . '.');
    }

    private function kembali(string $tab, string $tipe, string $pesan): void
    {
        Session::flash($tipe, $pesan);
        $this->redirect('pengaturan?tab=' . $tab);
    }
}
