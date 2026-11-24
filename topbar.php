<?php
$akunTopbar = Auth::user();
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Buka atau tutup menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="page-title"><?= e($pageTitle ?? APP_NAME) ?></h1>
    </div>

    <div class="topbar-right">
        <div class="clock">
            <i class="fa-regular fa-clock"></i>
            <div>
                <span class="clock-date" id="tanggalSekarang"></span>
                <span class="clock-time" id="jamSekarang">--:--:--</span>
            </div>
        </div>

        <div class="user-chip">
            <span class="avatar"><i class="fa-solid fa-user"></i></span>
            <div>
                <strong><?= e($akunTopbar['nama']) ?></strong>
                <small><?= e(Role::label($akunTopbar['role'])) ?></small>
            </div>
        </div>
    </div>
</header>
