<?php
/*
 * Menampilkan flash message sekali saja (lalu dihapus dari session).
 * app.js mengubahnya menjadi notifikasi (toast).
 */
$ikonFlash = [
    'success' => 'fa-circle-check',
    'danger'  => 'fa-circle-xmark',
    'warning' => 'fa-triangle-exclamation',
];

foreach (Session::pullFlash() as $flash):
    $tipe = in_array($flash['tipe'], ['success', 'danger', 'warning'], true) ? $flash['tipe'] : 'danger';
?>
    <div class="alert alert-<?= e($tipe) ?>" role="alert">
        <i class="fa-solid <?= e($ikonFlash[$tipe]) ?>"></i>
        <span><?= e($flash['pesan']) ?></span>
    </div>
<?php endforeach; ?>
