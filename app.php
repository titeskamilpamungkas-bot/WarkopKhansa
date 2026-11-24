<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? $title . ' · ' . APP_NAME : APP_NAME) ?></title>
    <link rel="icon" href="<?= e(asset('img/logo-warkop.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="app-body">
<div class="app-layout" id="appLayout">
    <?php require APP_PATH . '/Views/partials/sidebar.php'; ?>

    <div class="app-main">
        <?php require APP_PATH . '/Views/partials/topbar.php'; ?>

        <main class="content">
            <?php require APP_PATH . '/Views/partials/flash.php'; ?>
            <?= $content ?>
        </main>

        <footer class="app-footer">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Sistem Kasir &amp; Inventaris
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php foreach (($scripts ?? []) as $js): ?>
    <script src="<?= e(asset($js)) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
