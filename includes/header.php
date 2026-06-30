<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? setting('site_name', 'ShopEase');
$bodyClass = $bodyClass ?? '';
$flashes = consume_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | <?= e(setting('site_name', 'ShopEase')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<?php require __DIR__ . '/navbar.php'; ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast align-items-center text-bg-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'primary'))) ?> border-0" role="alert" data-bs-delay="4500">
            <div class="d-flex">
                <div class="toast-body"><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
