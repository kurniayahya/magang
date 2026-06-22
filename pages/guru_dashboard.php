<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('guru');

$user = getCurrentUser();
$db   = getDB();

$sekolahInfo = $db->query("SELECT tahun_aktif FROM sekolah WHERE id = " . SCHOOL_ID)->fetch();
$currentTahunAktif = $sekolahInfo['tahun_aktif'] ?? '2024';

$siswaBimbinganRaw = getSiswaBimbingan($user['id']);
$siswaBimbingan = array_values(array_filter($siswaBimbinganRaw, function($s) use ($currentTahunAktif) {
    return $s['tahun_pkl'] == $currentTahunAktif;
}));

$totalSiswa  = count($siswaBimbingan);
$totalHadir  = count(array_filter($siswaBimbingan, fn($s) => $s['presensi_status'] === 'hadir'));
$belumAbsen  = $totalSiswa - $totalHadir;

// Jurnal pending validasi dari siswa bimbingan
$siswaIds = array_column($siswaBimbingan, 'id');
$jurnalPending = 0;
if ($siswaIds) {
    $inClause = implode(',', array_fill(0, count($siswaIds), '?'));
    $stmt = $db->prepare("SELECT COUNT(*) FROM jurnal WHERE siswa_id IN ($inClause) AND status='terkirim'");
    $stmt->execute($siswaIds);
    $jurnalPending = $stmt->fetchColumn();
}

$pageTitle = 'Dashboard Guru';
include __DIR__ . '/../includes/header.php';
?>

<div class="dash-header" style="background:linear-gradient(135deg,#1a3a5c,#0d2137); padding: 20px 20px 45px 20px;">
    <div class="user-welcome">
        <h2 style="font-size: 1.3rem; margin-bottom: 2px;">Halo, <?= explode(' ', $user['nama'])[0] ?>!</h2>
        <p style="font-size: 0.85rem;">Panel Guru Pembimbing PKL</p>
    </div>
    <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" class="user-avatar-dash" style="top: 20px; width: 45px; height: 45px;" alt="Avatar">
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:15px;padding:0 2px;margin-bottom:15px;">
    <div class="card" style="margin-bottom:0;padding:12px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:800;color:var(--primary);"><?= $totalSiswa ?></div>
        <div style="font-size:0.65rem;color:var(--text-muted);font-weight:600;margin-top:3px;">TOTAL SISWA</div>
    </div>
    <div class="card" style="margin-bottom:0;padding:15px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:800;color:var(--success);"><?= $totalHadir ?></div>
        <div style="font-size:0.65rem;color:var(--text-muted);font-weight:600;margin-top:3px;">HADIR HARI INI</div>
    </div>
    <div class="card" style="margin-bottom:0;padding:15px;text-align:center;">
        <div style="font-size:1.6rem;font-weight:800;color:var(--warning);"><?= $jurnalPending ?></div>
        <div style="font-size:0.65rem;color:var(--text-muted);font-weight:600;margin-top:3px;">JURNAL PENDING</div>
    </div>
</div>

<!-- Shortcut Menu -->
<div class="menu-grid" style="grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;">
    <a href="<?= APP_URL ?>/guru/validasi" class="menu-item">
        <div class="menu-icon" style="background:linear-gradient(135deg,#a18cd1,#fbc2eb);position:relative;">
            <i class="fas fa-clipboard-check"></i>
            <?php if ($jurnalPending > 0): ?>
            <span style="position:absolute;top:-5px;right:-5px;background:var(--error);color:white;font-size:0.6rem;font-weight:700;padding:2px 6px;border-radius:20px;">
                <?= $jurnalPending ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="menu-label">Validasi Jurnal</span>
    </a>
    <a href="<?= APP_URL ?>/guru/peta" class="menu-item">
        <div class="menu-icon" style="background:linear-gradient(135deg,#f7971e,#ffd200);">
            <i class="fas fa-map-location-dot"></i>
        </div>
        <span class="menu-label">Peta PKL</span>
    </a>
    <a href="<?= APP_URL ?>/guru/rekap" class="menu-item">
        <div class="menu-icon" style="background:linear-gradient(135deg,#00c6ff,#0072ff);">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="menu-label">Rekap</span>
    </a>
    <a href="<?= APP_URL ?>/profil" class="menu-item">
        <div class="menu-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe);">
            <i class="fas fa-user-circle"></i>
        </div>
        <span class="menu-label">Profil</span>
    </a>
</div>

<!-- Daftar Siswa Bimbingan -->
<div class="card animate-fade-in">
    <div class="card-title">
        <i class="fas fa-users" style="color:var(--primary);"></i>
        Siswa Bimbingan Saya
    </div>
    <div class="activity-list">
        <?php if (empty($siswaBimbingan)): ?>
        <p style="text-align:center;color:var(--text-muted);padding:20px 0;font-size:0.85rem;">
            Belum ada siswa yang ditugaskan ke Anda.
        </p>
        <?php endif; ?>
        <?php foreach ($siswaBimbingan as $s): ?>
        <?php $hadir = $s['presensi_status'] === 'hadir'; ?>
        <div class="activity-item" style="padding:12px 0;">
            <img src="<?= getAvatarUrl($s['foto'], $s['nama']) ?>"
                 style="width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <div class="activity-info">
                <div class="activity-name"><?= $s['nama'] ?></div>
                <div class="activity-time">
                    <?= $s['jurusan_nama'] ?? '-' ?> •
                    <?= $s['tempat_pkl_nama'] ?? 'Belum ada tempat' ?>
                </div>
                <div style="margin-top:4px;">
                    <?php if ($hadir): ?>
                    <span style="font-size:0.7rem;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:20px;font-weight:600;">
                        <i class="fas fa-check-circle"></i> Hadir <?= $s['jam_masuk'] ?>
                        <?php if ($s['jam_keluar']): ?> – <?= $s['jam_keluar'] ?><?php endif; ?>
                    </span>
                    <?php else: ?>
                    <span style="font-size:0.7rem;background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:20px;font-weight:600;">
                        <i class="fas fa-clock"></i> Belum Absen
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
