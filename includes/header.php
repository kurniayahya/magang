<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1565C0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="description" content="MOPI - Aplikasi Mobile PKL Siswa">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME ?> | <?= SCHOOL_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <?= isset($extraHead) ? $extraHead : '' ?>
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Top Header -->
<header class="top-header">
    <div class="header-left">
        <?php if (isset($showBack) && $showBack): ?>
            <button class="btn-back" onclick="history.back()">
                <i class="fas fa-arrow-left"></i>
            </button>
        <?php else: ?>
            <div class="app-brand">
                <span class="brand-icon">🎓</span>
                <span class="brand-name">MOPI</span>
            </div>
        <?php endif; ?>
        <?php if (isset($pageTitle)): ?>
            <h1 class="header-title"><?= $pageTitle ?></h1>
        <?php endif; ?>
    </div>

</header>
<?php endif; ?>

<div class="app-container">
