<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$user = getCurrentUser();
$pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';

$db = getDB();
$totalSiswa = $db->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$totalGuru = $db->query("SELECT COUNT(*) FROM users WHERE role='guru' AND aktif=1")->fetchColumn();
$totalHadir = $db->query("SELECT COUNT(*) FROM presensi WHERE tanggal = CURDATE() AND status = 'hadir'")->fetchColumn();
$jurnalPending = $db->query("SELECT COUNT(*) FROM jurnal WHERE status = 'terkirim'")->fetchColumn();
?>

<div class="dash-header" style="background: linear-gradient(135deg, #1a1a2e, #16213e); padding: 20px 20px 45px 20px;">
    <div class="user-welcome">
        <h2 style="font-size: 1.3rem; margin-bottom: 2px;">Halo, <?= explode(' ', $user['nama'])[0] ?>!</h2>
        <p style="font-size: 0.85rem;">Panel Administrator MOPI PKL</p>
    </div>
    <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" class="user-avatar-dash"
        style="top: 20px; width: 45px; height: 45px;" alt="Avatar">
</div>

<!-- Stats -->
<div
    style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:15px;padding:0 2px;margin-bottom:15px;">
    <div class="card" style="margin-bottom:0;padding:12px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:var(--primary);"><?= $totalSiswa ?></div>
        <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;margin-top:3px;">TOTAL SISWA</div>
    </div>
    <div class="card" style="margin-bottom:0;padding:18px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:var(--accent);"><?= $totalGuru ?></div>
        <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;margin-top:3px;">TOTAL GURU</div>
    </div>
    <div class="card" style="margin-bottom:0;padding:18px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:var(--success);"><?= $totalHadir ?></div>
        <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;margin-top:3px;">HADIR HARI INI</div>
    </div>
    <div class="card" style="margin-bottom:0;padding:18px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:var(--warning);"><?= $jurnalPending ?></div>
        <div style="font-size:0.7rem;color:var(--text-muted);font-weight:600;margin-top:3px;">JURNAL PENDING</div>
    </div>
</div>

<!-- Menu Admin -->
<div class="card animate-fade-in">
    <div class="card-title"><i class="fas fa-th-large" style="color:var(--primary);"></i> Menu Utama</div>
    <div class="menu-grid" style="grid-template-columns:repeat(3,1fr);gap:18px;">
        <a href="<?= APP_URL ?>/admin/users" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe);">
                <i class="fas fa-users-gear"></i>
            </div>
            <span class="menu-label">Kelola User</span>
        </a>
        <a href="<?= APP_URL ?>/admin/import" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7);">
                <i class="fas fa-file-import"></i>
            </div>
            <span class="menu-label">Import Data</span>
        </a>
        <a href="<?= APP_URL ?>/admin/tempat_pkl" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#f7971e,#ffd200);">
                <i class="fas fa-building-flag"></i>
            </div>
            <span class="menu-label">Tempat PKL</span>
        </a>
        <a href="<?= APP_URL ?>/admin/rekap" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#00c6ff,#0072ff);">
                <i class="fas fa-file-invoice"></i>
            </div>
            <span class="menu-label">Rekap Laporan</span>
        </a>
        <a href="<?= APP_URL ?>/admin/pengaturan" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#b2bec3,#636e72);">
                <i class="fas fa-cogs"></i>
            </div>
            <span class="menu-label">Presensi</span>
        </a>
        <a href="<?= APP_URL ?>/profil" class="menu-item">
            <div class="menu-icon" style="background:linear-gradient(135deg,#a18cd1,#fbc2eb);">
                <i class="fas fa-user-shield"></i>
            </div>
            <span class="menu-label">Profil Admin</span>
        </a>
    </div>
</div>

<!-- Siswa Terbaru -->
<div class="card animate-fade-in">
    <div class="card-title"><i class="fas fa-users" style="color:var(--primary);"></i> Aktivitas Siswa Hari Ini</div>
    <div class="activity-list">
        <?php
        $stmt = $db->prepare("
            SELECT u.nama, tp.nama as tempat_pkl, p.jam_masuk, p.status as presensi_status
            FROM siswa s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
            LEFT JOIN presensi p ON s.id = p.siswa_id AND p.tanggal = CURDATE()
            ORDER BY p.jam_masuk DESC
            LIMIT 8
        ");
        $stmt->execute();
        $list = $stmt->fetchAll();
        foreach ($list as $s): ?>
            <div class="activity-item">
                <div class="activity-icon"
                    style="background:<?= $s['presensi_status'] == 'hadir' ? 'var(--primary-light)' : '#F1F5F9' ?>;color:<?= $s['presensi_status'] == 'hadir' ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                    <i class="fas fa-user"></i>
                </div>
                <div class="activity-info">
                    <div class="activity-name"><?= $s['nama'] ?></div>
                    <div class="activity-time"><?= $s['tempat_pkl'] ?? 'Belum ada tempat' ?></div>
                </div>
                <div
                    style="font-size:0.72rem;font-weight:700;color:<?= $s['presensi_status'] == 'hadir' ? 'var(--success)' : 'var(--text-muted)' ?>;">
                    <?= $s['presensi_status'] == 'hadir' ? '✓ ' . $s['jam_masuk'] : 'Belum' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>