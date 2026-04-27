<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

if ($user['role'] === 'siswa') {
    redirect(APP_URL . '/dashboard');
}

$pageTitle = 'Dashboard Admin/Guru';
include __DIR__ . '/../includes/header.php';

$db = getDB();

// Get overall stats
$totalSiswa = $db->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$totalHadir = $db->query("SELECT COUNT(*) FROM presensi WHERE tanggal = CURDATE() AND status = 'hadir'")->fetchColumn();
$jurnalMasuk = $db->query("SELECT COUNT(*) FROM jurnal WHERE status = 'terkirim'")->fetchColumn();

// Get list of students and their locations
$stmt = $db->prepare("
    SELECT s.*, u.nama, tp.nama as tempat_pkl, p.jam_masuk, p.status as presensi_status
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
    LEFT JOIN presensi p ON s.id = p.siswa_id AND p.tanggal = CURDATE()
");
$stmt->execute();
$siswaList = $stmt->fetchAll();
?>

<div class="dash-header" style="background: linear-gradient(135deg, #2C3E50, #000000);">
    <div class="user-welcome">
        <h2>Halo, <?= explode(' ', $user['nama'])[0] ?>!</h2>
        <p>Panel Monitoring PKL (<?= ucfirst($user['role']) ?>)</p>
    </div>
    <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" class="user-avatar-dash" alt="Avatar">
</div>

<div class="menu-grid animate-fade-in" style="margin-top: -30px; padding: 0 10px;">
    <div class="card" style="margin-bottom: 0; padding: 15px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?= $totalSiswa ?></div>
        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">TOTAL SISWA</div>
    </div>
    <div class="card" style="margin-bottom: 0; padding: 15px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--success);"><?= $totalHadir ?></div>
        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">HADIR HARI INI</div>
    </div>
    <div class="card" style="margin-bottom: 0; padding: 15px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--warning);"><?= $jurnalMasuk ?></div>
        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600;">JURNAL PENDING</div>
    </div>
</div>

<div class="card animate-fade-in" style="margin-top: 20px;">
    <div class="card-title">
        <i class="fas fa-users" style="color: var(--primary);"></i> Monitoring Siswa
    </div>
    <div class="activity-list">
        <?php foreach ($siswaList as $s): ?>
        <div class="activity-item" style="padding: 15px 0;">
            <div class="activity-icon" style="background: <?= $s['presensi_status'] == 'hadir' ? 'var(--primary-light)' : '#F1F5F9' ?>;">
                <i class="fas fa-user"></i>
            </div>
            <div class="activity-info">
                <div class="activity-name"><?= $s['nama'] ?></div>
                <div class="activity-time"><?= $s['tempat_pkl'] ?? 'Belum ada tempat' ?></div>
                <div style="font-size: 0.7rem; margin-top: 4px;">
                    <?php if ($s['presensi_status'] == 'hadir'): ?>
                        <span style="color: var(--success); font-weight: 600;"><i class="fas fa-check-circle"></i> Hadir (<?= $s['jam_masuk'] ?>)</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted);"><i class="fas fa-clock"></i> Belum Absen</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display: flex; gap: 5px;">
                <a href="#" class="header-icon-btn" style="color: var(--primary); font-size: 1rem;"><i class="fas fa-eye"></i></a>
                <a href="#" class="header-icon-btn" style="color: var(--accent); font-size: 1rem;"><i class="fas fa-comment"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="menu-grid animate-fade-in" style="margin-top: 20px;">
    <a href="#" class="menu-item">
        <div class="menu-icon bg-purple">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="menu-label">Validasi Jurnal</span>
    </a>
    <a href="#" class="menu-item">
        <div class="menu-icon bg-blue">
            <i class="fas fa-map-marked-alt"></i>
        </div>
        <span class="menu-label">Peta Siswa</span>
    </a>
    <a href="#" class="menu-item">
        <div class="menu-icon bg-green">
            <i class="fas fa-star"></i>
        </div>
        <span class="menu-label">Penilaian</span>
    </a>
    <a href="<?= APP_URL ?>/logout" class="menu-item">
        <div class="menu-icon" style="background: #e74c3c; color: white;">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <span class="menu-label">Keluar</span>
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
