<div class="error-page">
    <div class="card error-card">
        <div class="error-code"><?= (int) $kode ?></div>
        <h2><?= e($judul) ?></h2>
        <p class="error-pesan"><?= e($pesan) ?></p>
        <div class="form-actions error-actions">
            <a class="btn btn-primary" href="<?= e(url('')) ?>">Kembali ke beranda</a>
        </div>
    </div>
</div>
