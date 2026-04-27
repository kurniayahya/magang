<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

if (!$user) {
    session_destroy();
    header('Location: ' . APP_URL);
    exit;
}

if ($user['role'] !== 'siswa') {
    die("Halaman ini hanya untuk Siswa PKL.");
}

$siswa = getSiswaInfo($user['id']);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan.");
}

$pageTitle = 'Laporan / Riwayat';
$showBack = true;
include __DIR__ . '/../includes/header.php';

$db = getDB();

$tab = $_GET['tab'] ?? 'jurnal';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($tab === 'jurnal') {
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM jurnal WHERE siswa_id = ?");
    $stmtCount->execute([$siswa['id']]);
    $totalData = $stmtCount->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM jurnal WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $siswa['id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();
} else {
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM presensi WHERE siswa_id = ?");
    $stmtCount->execute([$siswa['id']]);
    $totalData = $stmtCount->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $siswa['id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();
}

$totalPages = ceil($totalData / $limit);
?>

<div class="animate-fade-in">
    <!-- Tabs -->
    <div style="display: flex; background: var(--card-bg); border-radius: var(--radius-md); overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-sm);">
        <a href="?tab=jurnal" style="flex: 1; text-align: center; padding: 15px; font-weight: 600; text-decoration: none; color: <?= $tab === 'jurnal' ? 'white' : 'var(--text-main)' ?>; background: <?= $tab === 'jurnal' ? 'var(--primary)' : 'transparent' ?>; border-bottom: 3px solid <?= $tab === 'jurnal' ? 'var(--primary)' : 'transparent' ?>; transition: 0.3s;">
            <i class="fas fa-book"></i> Riwayat Jurnal
        </a>
        <a href="?tab=absensi" style="flex: 1; text-align: center; padding: 15px; font-weight: 600; text-decoration: none; color: <?= $tab === 'absensi' ? 'white' : 'var(--text-main)' ?>; background: <?= $tab === 'absensi' ? 'var(--primary)' : 'transparent' ?>; border-bottom: 3px solid <?= $tab === 'absensi' ? 'var(--primary)' : 'transparent' ?>; transition: 0.3s;">
            <i class="fas fa-clock"></i> Riwayat Absensi
        </a>
    </div>

    <div class="card">
        <div class="card-title"><?= $tab === 'jurnal' ? 'Riwayat Jurnal' : 'Riwayat Absensi' ?></div>
        
        <?php if (count($data) > 0): ?>
            <div class="activity-list">
                <?php foreach ($data as $row): ?>
                    <?php if ($tab === 'jurnal'): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="activity-info">
                                <div class="activity-name"><?= htmlspecialchars($row['kegiatan']) ?></div>
                                <div class="activity-time"><?= formatTanggal($row['tanggal']) ?> • Hari Ke-<?= $row['hari_ke'] ?></div>
                            </div>
                            <?php
                            $bgStatus = 'var(--text-muted)';
                            if ($row['status'] == 'divalidasi') $bgStatus = 'var(--success)';
                            elseif ($row['status'] == 'ditolak') $bgStatus = 'var(--error)';
                            elseif ($row['status'] == 'terkirim') $bgStatus = 'var(--primary)';
                            ?>
                            <div style="font-size: 0.7rem; padding: 4px 8px; border-radius: 20px; background: <?= $bgStatus ?>; color: white; font-weight: 700;">
                                <?= strtoupper($row['status']) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?= $row['status'] == 'hadir' ? 'var(--primary-light)' : '#FEE2E2' ?>; color: <?= $row['status'] == 'hadir' ? 'var(--primary)' : '#EF4444' ?>;">
                                <i class="fas <?= $row['status'] == 'hadir' ? 'fa-clock' : 'fa-user-xmark' ?>"></i>
                            </div>
                            <div class="activity-info">
                                <div class="activity-name"><?= getHariNama($row['tanggal']) ?>, <?= formatTanggal($row['tanggal']) ?></div>
                                <div class="activity-time"><?= $row['status'] == 'hadir' ? ($row['jam_masuk'] . ' - ' . ($row['jam_keluar'] ?? '--:--')) : ucfirst($row['status']) ?></div>
                            </div>
                            <div style="font-size: 0.75rem; font-weight: 600; color: <?= $row['validasi'] == 'valid' ? 'var(--success)' : 'var(--warning)' ?>;">
                                <?= strtoupper($row['validasi']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px; gap: 5px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?tab=<?= $tab ?>&page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : '' ?>" style="padding: 8px 12px; font-size: 0.9rem; border-radius: 5px; text-decoration: none; <?= $i !== $page ? 'border: 1px solid var(--border); color: var(--text-main); background: transparent;' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: var(--text-muted);">
                <i class="fas fa-inbox fa-3x" style="margin-bottom: 10px; opacity: 0.5;"></i>
                <p>Belum ada data <?= $tab ?>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
include __DIR__ . '/../includes/footer.php'; 
?>
