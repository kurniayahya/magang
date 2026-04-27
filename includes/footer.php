</div><!-- end .app-container -->

<?php if (isLoggedIn()): ?>
<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <?php
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $navItems = [
        ['href' => APP_URL . '/dashboard', 'icon' => 'fa-home', 'label' => 'Beranda', 'page' => 'dashboard'],
        ['href' => APP_URL . '/presensi', 'icon' => 'fa-fingerprint', 'label' => 'Presensi', 'page' => 'presensi'],
        ['href' => APP_URL . '/jurnal', 'icon' => 'fa-book-open', 'label' => 'Jurnal', 'page' => 'jurnal'],
        ['href' => APP_URL . '/laporan', 'icon' => 'fa-chart-bar', 'label' => 'Laporan', 'page' => 'laporan'],
        ['href' => APP_URL . '/profil', 'icon' => 'fa-user-circle', 'label' => 'Profil', 'page' => 'profil'],
    ];
    foreach ($navItems as $item):
        $isActive = ($currentPage === $item['page']) ? 'active' : '';
    ?>
    <a href="<?= $item['href'] ?>" class="nav-item <?= $isActive ?>">
        <i class="fas <?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<?= isset($extraScript) ? $extraScript : '' ?>
</body>
</html>
