<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

if (!$user) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Check if user is a student
if ($user['role'] !== 'siswa') {
    $logoutUrl = APP_URL . '/logout';
    die("
        <div style='font-family: sans-serif; text-align: center; padding: 50px;'>
            <h2>Akses Terbatas</h2>
            <p>Halaman ini hanya untuk <b>Siswa PKL</b>. Role Anda saat ini: <b>" . $user['role'] . "</b></p>
            <br>
            <a href='$logoutUrl' style='padding: 10px 20px; background: #1E6FD9; color: white; text-decoration: none; border-radius: 5px;'>Logout & Login sebagai Siswa</a>
        </div>
    ");
}

$siswa = getSiswaInfo($user['id']);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan di database. Pastikan database sudah terisi dengan benar.");
}

$pageTitle = 'Beranda';
include __DIR__ . '/../includes/header.php';

// Get today's attendance
$db = getDB();
$stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? AND tanggal = CURDATE()");
$stmt->execute([$siswa['id']]);
$todayPresensi = $stmt->fetch();

$hariKe = getHariKePKL($siswa['tanggal_mulai']);
$totalHari = (int)($siswa['total_hari_pkl'] ?? 90);
$progressPersen = min(100, round(($hariKe / max(1, $totalHari)) * 100));

// Get recent activities (journals & presence)
$stmt = $db->prepare("
    (SELECT 'jurnal' as tipe, tanggal, kegiatan as info, status, created_at FROM jurnal WHERE siswa_id = ?)
    UNION
    (SELECT 'presensi' as tipe, tanggal, status as info, validasi as status, created_at FROM presensi WHERE siswa_id = ?)
    ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$siswa['id'], $siswa['id']]);
$activities = $stmt->fetchAll();
?>

<div class="dash-header">
    <div class="user-welcome">
        <h2>Halo, <?= explode(' ', $user['nama'])[0] ?>!</h2>
        <p>PKL Hari ke-<?= $hariKe ?></p>
    </div>
    <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" class="user-avatar-dash" alt="Avatar">
</div>

<div class="stats-card animate-fade-in">
    <div class="card-title" style="margin-bottom: 10px; font-weight: 600;">
        <i class="fas fa-chart-line" style="color: var(--primary);"></i> Progres Hari Ini
    </div>
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value">3 <span class="stat-unit">Jam</span></div>
        </div>
        <div class="stat-divider">20 <span class="stat-unit">Menit</span></div>
        <div class="stat-divider">+</div>
        <div class="stat-item">
            <div class="stat-value">2 <span class="stat-unit">Tugas</span></div>
        </div>
    </div>
    <div class="progress-container">
        <div class="progress-bar" style="width: <?= $progressPersen ?>%;"></div>
    </div>
</div>

<div class="menu-grid animate-fade-in" style="margin-top: 25px;">
    <a href="<?= APP_URL ?>/presensi" class="menu-item">
        <div class="menu-icon bg-orange">
            <i class="fas fa-fingerprint"></i>
        </div>
        <span class="menu-label">Check In</span>
    </a>
    <a href="<?= APP_URL ?>/jurnal" class="menu-item">
        <div class="menu-icon bg-blue">
            <i class="fas fa-book-open"></i>
        </div>
        <span class="menu-label">Laporan</span>
    </a>
    <a href="<?= APP_URL ?>/portofolio" class="menu-item">
        <div class="menu-icon bg-teal">
            <i class="fas fa-images"></i>
        </div>
        <span class="menu-label">Galeri</span>
    </a>
    <a href="<?= APP_URL ?>/profil" class="menu-item">
        <div class="menu-icon bg-yellow">
            <i class="fas fa-info-circle"></i>
        </div>
        <span class="menu-label">Info PKL</span>
    </a>
</div>

<div class="card animate-fade-in">
    <div class="card-title">
        <i class="fas fa-clock-rotate-left" style="color: var(--primary);"></i> Recent Activity
    </div>
    <div class="activity-list">
        <?php foreach ($activities as $act): ?>
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas <?= $act['tipe'] == 'jurnal' ? 'fa-pen-to-square' : 'fa-check-circle' ?>"></i>
            </div>
            <div class="activity-info">
                <div class="activity-name"><?= $act['tipe'] == 'jurnal' ? 'Upload Laporan' : 'Presensi: ' . ucfirst($act['info']) ?></div>
                <div class="activity-time"><?= formatTanggal($act['tanggal']) ?></div>
            </div>
            <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: var(--text-muted);"></i>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($activities)): ?>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 10px 0;">Belum ada aktivitas.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
