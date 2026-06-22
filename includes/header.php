<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1565C0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="description" content="MOPI - Aplikasi Mobile PKL Siswa">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME ?> | <?= SCHOOL_NAME ?></title>
    <?php
    // Favicon dari logo sekolah dan tahun aktif
    $faviconUrl = '';
    $tahunAktif = '';
    try {
        $dbFav = getDB();
        $stmtFav = $dbFav->prepare("SELECT logo, tahun_aktif FROM sekolah WHERE id = ? LIMIT 1");
        $stmtFav->execute([SCHOOL_ID]);
        $rowFav = $stmtFav->fetch();
        if ($rowFav) {
            if ($rowFav['logo'] && file_exists(UPLOAD_PATH . $rowFav['logo'])) {
                $faviconUrl = UPLOAD_URL . $rowFav['logo'];
            }
            $tahunAktif = $rowFav['tahun_aktif'] ?? '';
        }
    } catch (Exception $e) {
    }
    ?>
    <?php if ($faviconUrl): ?>
        <link rel="icon" type="image/png" href="<?= $faviconUrl ?>">
        <link rel="apple-touch-icon" href="<?= $faviconUrl ?>">
    <?php else: ?>
        <link rel="icon"
            href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            
            <div class="header-right">
                <?php if (!empty($tahunAktif)): ?>
                    <span style="font-size: 0.8rem; font-weight: 600;">Tahun PKL Aktif : <?= htmlspecialchars($tahunAktif) ?></span>
                <?php endif; ?>
            </div>
        </header>
        <?php endif; ?>

    <div class="app-container">