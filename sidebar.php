<?php
/*
 * Sidebar. Menu yang tampil ditentukan oleh role akun dari database.
 */
$akunSidebar = Auth::user();
$daftarMenu = require ROOT_PATH . '/config/menu.php';
$halamanAktif = explode('/', Router::currentPath())[0];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-badge">
            <img class="logo-img" src="<?= e(asset('img/logo-warkop.png')) ?>" alt="Logo WARKOP KHANSA">
        </div>
        <div class="brand-text">
            <strong>WARKOP</strong>
            <span>KHANSA</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($daftarMenu as $item): ?>
            <?php if (!Role::izinkan($akunSidebar['role'], $item['roles'])) continue; ?>
            <a href="<?= e(url($item['route'])) ?>"
               class="nav-item<?= $halamanAktif === $item['route'] ? ' active' : '' ?>">
                <i class="<?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <form method="post" action="<?= e(url('logout')) ?>" class="logout-form">
            <?= Csrf::field() ?>
            <button type="submit" class="nav-item nav-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </button>
        </form>
    </nav>

    <div class="sidebar-art">
        <div class="art-badge">
            <img class="logo-img" src="<?= e(asset('img/logo-warkop.png')) ?>" alt="">
        </div>
        <strong>KHANSA</strong>
        <span>Warkop &middot; Kasir &amp; Stok</span>
    </div>
</aside>
