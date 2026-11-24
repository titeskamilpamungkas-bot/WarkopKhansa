<?php

/**
 * Autentikasi: login, pengecekan sesi, dan validasi akun pada setiap permintaan.
 *
 * Role TIDAK pernah diambil dari form. Setiap permintaan membaca ulang akun
 * dari database dan memastikan status aktif serta role-nya dikenali sistem.
 */
class Auth
{
    private static ?array $akun = null;
    private static bool $sudahDicek = false;

    /**
     * Coba login. Mengembalikan null jika berhasil, atau pesan error.
     */
    public static function attempt(string $username, string $password): ?string
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return 'Username dan password wajib diisi.';
        }

        $user = User::findByUsername($username);

        // Pesan dibuat umum agar tidak membocorkan username yang terdaftar
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return 'Username atau password salah.';
        }

        if ($user['status'] !== 'aktif') {
            return 'Akun ini sedang nonaktif. Hubungi pemilik.';
        }

        // Validasi role dilakukan sistem dari data database
        if (!Role::isValid($user['role'])) {
            return 'Role akun tidak dikenali sistem. Hubungi pemilik.';
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('login_at', time());
        self::$sudahDicek = false;
        self::$akun = null;

        return null;
    }

    /**
     * Akun yang sedang login, atau null. Dibaca ulang dari database
     * dan dihentikan jika akun nonaktif atau role tidak valid.
     */
    public static function user(): ?array
    {
        if (self::$sudahDicek) {
            return self::$akun;
        }

        self::$sudahDicek = true;

        $id = Session::get('user_id');
        if ($id === null) {
            return null;
        }

        $user = User::findById((int) $id);

        if ($user === null || $user['status'] !== 'aktif' || !Role::isValid($user['role'])) {
            self::keluarPaksa('Sesi Anda berakhir. Akun tidak aktif atau role tidak valid.');
            return null;
        }

        self::$akun = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        Session::destroy();
        Session::start();
        self::$akun = null;
        self::$sudahDicek = false;
    }

    private static function keluarPaksa(string $pesan): void
    {
        self::logout();
        Session::flash('danger', $pesan);
    }
}
