</div><!-- end .app-container -->

<?php if (isLoggedIn()): ?>
<!-- Bottom Navigation -->
<nav class="bottom-nav">
    <?php
    $role = $_SESSION['user_role'] ?? '';
    $currentRoute = $_GET['route'] ?? '';

    if ($role === 'siswa') {
        $navItems = [
            ['href' => APP_URL . '/dashboard',  'icon' => 'fa-home',         'label' => 'Beranda',  'route' => 'dashboard'],
            ['href' => APP_URL . '/presensi',   'icon' => 'fa-fingerprint',  'label' => 'Presensi', 'route' => 'presensi'],
            ['href' => APP_URL . '/jurnal',     'icon' => 'fa-book-open',    'label' => 'Jurnal',   'route' => 'jurnal'],
            ['href' => APP_URL . '/laporan',    'icon' => 'fa-chart-bar',    'label' => 'Laporan',  'route' => 'laporan'],
            ['href' => APP_URL . '/profil',     'icon' => 'fa-user-circle',  'label' => 'Profil',   'route' => 'profil'],
        ];
    } elseif ($role === 'guru') {
        $navItems = [
            ['href' => APP_URL . '/guru_dashboard', 'icon' => 'fa-house-chimney', 'label' => 'Dashboard', 'route' => 'guru_dashboard'],
            ['href' => APP_URL . '/guru/validasi',  'icon' => 'fa-clipboard-check','label' => 'Validasi',  'route' => 'guru/validasi'],
            ['href' => APP_URL . '/guru/peta',      'icon' => 'fa-map-location-dot','label' => 'Peta PKL', 'route' => 'guru/peta'],
            ['href' => APP_URL . '/profil',         'icon' => 'fa-user-circle',    'label' => 'Profil',   'route' => 'profil'],
        ];
    } else { // admin
        $navItems = [
            ['href' => APP_URL . '/admin_dashboard', 'icon' => 'fa-gauge-high',      'label' => 'Dashboard', 'route' => 'admin_dashboard'],
            ['href' => APP_URL . '/admin/users',     'icon' => 'fa-users-gear',       'label' => 'Users',     'route' => 'admin/users'],
            ['href' => APP_URL . '/admin/import',    'icon' => 'fa-file-import',      'label' => 'Import',    'route' => 'admin/import'],
            ['href' => APP_URL . '/admin/tempat_pkl','icon' => 'fa-building-flag',    'label' => 'Tempat PKL','route' => 'admin/tempat_pkl'],
            ['href' => APP_URL . '/profil',          'icon' => 'fa-user-circle',      'label' => 'Profil',    'route' => 'profil'],
        ];
    }

    foreach ($navItems as $item):
        $isActive = ($currentRoute === $item['route']) ? 'active' : '';
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

