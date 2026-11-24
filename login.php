<?php
/*
 * Halaman login. Tidak ada pilihan role: sistem membaca role dari database
 * setelah username dan password cocok.
 */
?>
<div class="login-wrapper">
    <section class="login-brand">
        <img class="login-logo" src="<?= e(asset('img/logo-warkop.png')) ?>" alt="Logo WARKOP KHANSA">
        <h1 class="brand-title">WARKOP <span>KHANSA</span></h1>
        <p class="brand-tagline">Ngopi Suka-Suka</p>
    </section>

    <section class="login-card" id="loginCard">
        <h2>Masuk</h2>
        <p class="login-subtitle">Gunakan username dan password akun Anda.</p>

        <?php require APP_PATH . '/Views/partials/flash.php'; ?>

        <form method="post" action="<?= e(url('login')) ?>" id="formLogin" novalidate>
            <?= Csrf::field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <i class="fa-solid fa-user"></i>
                    <input id="username" name="username" type="text" autocomplete="username"
                           placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                           placeholder="Masukkan password" required>
                    <button type="button" class="toggle-password" data-toggle-password="#password"
                            aria-label="Tampilkan password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login btn-block">
                <span class="btn-text">Masuk</span>
            </button>
        </form>
    </section>
</div>

<footer class="login-footer">
    <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
    <span>Akses menyesuaikan role akun</span>
</footer>
