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

$pageTitle = 'Laporan Harian';
$showBack = true;
include __DIR__ . '/../includes/header.php';

$db = getDB();
$hariKe = getHariKePKL($siswa['tanggal_mulai']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $kegiatan = sanitize($_POST['kegiatan']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    
    // Check if entry exists
    $stmt = $db->prepare("SELECT id FROM jurnal WHERE siswa_id = ? AND tanggal = ?");
    $stmt->execute([$siswa['id'], $tanggal]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $db->prepare("UPDATE jurnal SET kegiatan = ?, deskripsi = ?, status = 'terkirim' WHERE id = ?");
        $stmt->execute([$kegiatan, $deskripsi, $existing['id']]);
        $jurnalId = $existing['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO jurnal (siswa_id, tanggal, hari_ke, kegiatan, deskripsi, status) VALUES (?, ?, ?, ?, ?, 'terkirim')");
        $stmt->execute([$siswa['id'], $tanggal, $hariKe, $kegiatan, $deskripsi]);
        $jurnalId = $db->lastInsertId();
    }

    // Handle File Upload (Simulated)
    if (!empty($_FILES['foto']['name'])) {
        $fileName = time() . '_' . $_FILES['foto']['name'];
        $stmt = $db->prepare("INSERT INTO jurnal_foto (jurnal_id, nama_file) VALUES (?, ?)");
        $stmt->execute([$jurnalId, $fileName]);
    }

    header("Location: " . APP_URL . "/jurnal?success=1");
    exit;
}

// Get today's journal
$stmt = $db->prepare("SELECT * FROM jurnal WHERE siswa_id = ? AND tanggal = CURDATE()");
$stmt->execute([$siswa['id']]);
$todayJurnal = $stmt->fetch();
?>

<div class="animate-fade-in">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <i class="fas fa-check-circle"></i> Laporan harian berhasil dikirim!
        </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="card">
            <div style="background: var(--background); padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid var(--border);">
                <div style="font-weight: 700; color: var(--text-main);">Hari Ke-<?= $hariKe ?>, <?= formatTanggal(date('Y-m-d'), 'd F Y') ?></div>
            </div>

            <div class="form-group">
                <label class="form-label">Kegiatan</label>
                <input type="text" name="kegiatan" class="form-control" placeholder="Contoh: Servis Motor" required value="<?= $todayJurnal['kegiatan'] ?? '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Catatan / Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="4" placeholder="Jelaskan detail pekerjaan Anda hari ini..."><?= $todayJurnal['deskripsi'] ?? '' ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upload Foto</label>
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-camera upload-icon"></i>
                    <p style="font-weight: 600; font-size: 0.9rem;">Ambil Foto / Video</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted);">Maksimal 5MB (JPG, PNG, MP4)</p>
                    <input type="file" id="fileInput" name="foto" style="display: none;" accept="image/*,video/*">
                </div>
                <div id="fileName" style="margin-top: 10px; font-size: 0.8rem; color: var(--primary); font-weight: 600; text-align: center;"></div>
            </div>

            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #E67E22, #D35400); margin-top: 20px; height: 55px; font-size: 1rem;">
                Kirim Laporan
            </button>
        </div>
    </form>

    <div class="card" style="margin-top: 20px; border-top: 5px solid var(--primary);">
        <div class="card-title">Riwayat Jurnal</div>
        <div class="activity-list">
            <?php
            $stmt = $db->prepare("SELECT * FROM jurnal WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 5");
            $stmt->execute([$siswa['id']]);
            $history = $stmt->fetchAll();
            foreach ($history as $h):
            ?>
            <div class="activity-item">
                <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);">
                    <i class="fas fa-book"></i>
                </div>
                <div class="activity-info">
                    <div class="activity-name"><?= $h['kegiatan'] ?></div>
                    <div class="activity-time"><?= formatTanggal($h['tanggal']) ?> • Hari Ke-<?= $h['hari_ke'] ?></div>
                </div>
                <div style="font-size: 0.7rem; padding: 4px 8px; border-radius: 20px; background: <?= $h['status'] == 'divalidasi' ? 'var(--success)' : 'var(--warning)' ?>; color: white; font-weight: 700;">
                    <?= strtoupper($h['status']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="#" style="display: block; text-align: center; margin-top: 15px; font-size: 0.85rem; color: var(--primary); font-weight: 600;">Lihat Semua Jurnal <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<?php 
$extraScript = "
<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        document.getElementById('fileName').innerHTML = '<i class=\"fas fa-file-image\"></i> ' + this.files[0].name;
    }
});
</script>
";
include __DIR__ . '/../includes/footer.php'; 
?>
