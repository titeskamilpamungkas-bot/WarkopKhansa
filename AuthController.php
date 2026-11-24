<?php

/**
 * Login dan logout. Role akun ditentukan oleh database, bukan oleh form login.
 */
class AuthController extends Controller
{
    /** Halaman awal: arahkan sesuai kondisi login dan role. */
    public function root(): void
    {
        $user = Auth::user();

        if ($user === null) {
            $this->redirect('login');
        }

        $this->redirect(Role::beranda($user['role']));
    }

    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title'     => 'Masuk',
            'bodyClass' => 'login-body',
            'styles'    => ['css/login.css'],
        ], 'blank');
    }

    public function login(): void
    {
        $username = $this->post('username');
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        $error = Auth::attempt($username, $password);

        if ($error !== null) {
            Session::flash('danger', $error);
            $this->redirect('login');
        }

        $user = Auth::user();
        Session::flash('success', 'Selamat datang, ' . $user['nama'] . '.');

        // Setelah login, role dari database menentukan halaman tujuan
        $this->redirect(Role::beranda($user['role']));
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'Anda telah keluar dari sistem.');
        $this->redirect('login');
    }
}
