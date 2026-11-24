<?php

/**
 * Data member. Tambah dan ubah: semua role. Hapus: admin saja (dijaga Router).
 */
class MemberController extends Controller
{
    public function index(): void
    {
        $cari = $this->get('q');

        $this->view('member/index', [
            'title'     => 'Member',
            'pageTitle' => 'Member',
            'hasil'     => Member::paginate($cari),
            'ringkasan' => Member::ringkasan(),
            'cari'      => $cari,
            'scripts'   => [],
        ]);
    }

    public function tambah(): void
    {
        [$data, $error] = $this->validasiMember();

        if ($error !== null) {
            Session::flash('danger', $error);
            $this->redirect('member');
        }

        $nomor = Member::create($data['nama'], $data['no_hp'], $data['alamat']);
        Session::flash('success', 'Member ' . $data['nama'] . ' berhasil didaftarkan dengan nomor ' . $nomor . '.');
        $this->redirect('member');
    }

    public function edit(): void
    {
        $id = $this->angka('post', 'id');
        if ($id <= 0 || Member::findById($id) === null) {
            Session::flash('danger', 'Data member tidak ditemukan.');
            $this->redirect('member');
        }

        [$data, $error] = $this->validasiMember();

        if ($error !== null) {
            Session::flash('danger', $error);
            $this->redirect('member');
        }

        $status = $this->post('status', 'aktif');
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            Session::flash('danger', 'Status member tidak valid.');
            $this->redirect('member');
        }

        Member::update($id, $data['nama'], $data['no_hp'], $data['alamat'], $status);
        Session::flash('success', 'Data member ' . $data['nama'] . ' berhasil diperbarui.');
        $this->redirect('member');
    }

    public function hapus(): void
    {
        $id = $this->angka('post', 'id');
        $member = $id > 0 ? Member::findById($id) : null;

        if ($member === null) {
            Session::flash('danger', 'Data member tidak ditemukan.');
            $this->redirect('member');
        }

        Member::delete($id);
        Session::flash('success', 'Member ' . $member['nama'] . ' berhasil dihapus.');
        $this->redirect('member');
    }

    /** @return array{0: array, 1: ?string} */
    private function validasiMember(): array
    {
        $nama = $this->post('nama');
        $noHp = preg_replace('/\s+/', '', $this->post('no_hp'));
        $alamat = $this->post('alamat');

        if ($nama === '' || mb_strlen($nama) > 100) {
            return [[], 'Nama member wajib diisi (maksimal 100 karakter).'];
        }
        if ($noHp !== '' && !preg_match('/^\+?[0-9]{8,20}$/', $noHp)) {
            return [[], 'Nomor HP hanya boleh berisi angka (8–20 digit).'];
        }
        if (mb_strlen($alamat) > 255) {
            return [[], 'Alamat maksimal 255 karakter.'];
        }

        return [[
            'nama'   => $nama,
            'no_hp'  => $noHp !== '' ? $noHp : null,
            'alamat' => $alamat !== '' ? $alamat : null,
        ], null];
    }
}
