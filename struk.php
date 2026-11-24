<div class="struk-page">
    <div class="flash-wrap">
        <?php require APP_PATH . '/Views/partials/flash.php'; ?>
    </div>

    <article class="struk">
        <h2>WARKOP KHANSA</h2>
        <p class="struk-center">Sistem Kasir &amp; Inventaris</p>
        <hr>

        <div class="struk-row"><span>Kode</span><span><?= e($trx['kode_transaksi']) ?></span></div>
        <div class="struk-row"><span>Waktu</span><span><?= e(tanggalWaktuIndo($trx['tanggal'])) ?></span></div>
        <div class="struk-row"><span>Kasir</span><span><?= e($trx['nama_kasir']) ?></span></div>
        <?php if (!empty($trx['nama_member'])): ?>
            <div class="struk-row"><span>Member</span><span><?= e($trx['nama_member']) ?></span></div>
        <?php endif; ?>
        <hr>

        <?php foreach ($trx['items'] as $item): ?>
            <div class="struk-item">
                <div><?= e($item['nama_menu']) ?></div>
                <div class="struk-row">
                    <span><?= (int) $item['jumlah'] ?> x <?= e(rupiah($item['harga_satuan'])) ?></span>
                    <span><?= e(rupiah($item['subtotal'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <hr>

        <div class="struk-row"><span>Total</span><span><?= e(rupiah($trx['total_harga'])) ?></span></div>
        <div class="struk-row"><strong>Grand total</strong><strong><?= e(rupiah($trx['grand_total'])) ?></strong></div>
        <div class="struk-row"><span>Metode</span><span><?= e(labelMetode($trx['metode_pembayaran'])) ?></span></div>
        <div class="struk-row"><span>Bayar</span><span><?= e(rupiah($trx['bayar'])) ?></span></div>
        <div class="struk-row"><span>Kembali</span><span><?= e(rupiah($trx['kembalian'])) ?></span></div>
        <hr>

        <p class="struk-center">Terima kasih, sampai jumpa lagi!</p>
    </article>

    <div class="struk-actions no-print">
        <button type="button" class="btn btn-primary" data-print>
            <i class="fa-solid fa-print"></i> Cetak
        </button>
        <a class="btn btn-outline" href="<?= e(url('transaksi')) ?>">
            <i class="fa-solid fa-cash-register"></i> Transaksi baru
        </a>
    </div>
</div>
