<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

$db = getDB();
$msg = '';
$err = '';

// Ambil data sekolah (id = SCHOOL_ID)
$stmt = $db->prepare("SELECT * FROM sekolah WHERE id = ?");
$stmt->execute([SCHOOL_ID]);
$sekolah = $stmt->fetch();

if (!$sekolah) {
    die("Error: Data sekolah tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $jam_masuk_mulai = $_POST['jam_masuk_mulai'];
    $jam_masuk_selesai = $_POST['jam_masuk_selesai'];
    $jam_pulang_mulai = $_POST['jam_pulang_mulai'];
    $jam_pulang_selesai = $_POST['jam_pulang_selesai'];
    
    try {
        $stmt = $db->prepare("UPDATE sekolah SET jam_masuk_mulai = ?, jam_masuk_selesai = ?, jam_pulang_mulai = ?, jam_pulang_selesai = ? WHERE id = ?");
        $stmt->execute([$jam_masuk_mulai, $jam_masuk_selesai, $jam_pulang_mulai, $jam_pulang_selesai, SCHOOL_ID]);
        $msg = "Pengaturan berhasil diperbarui.";
        
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM sekolah WHERE id = ?");
        $stmt->execute([SCHOOL_ID]);
        $sekolah = $stmt->fetch();
    } catch (PDOException $e) {
        $err = "Gagal memperbarui pengaturan: " . $e->getMessage();
    }
}

$pageTitle = 'Pengaturan Sekolah';
$showBack = true;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>

<?php if ($err): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
<?php endif; ?>

<div class="card animate-fade-in">
    <div class="card-title"><i class="fas fa-clock" style="color:var(--primary);"></i> Rentang Jam Presensi</div>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: var(--primary);">Sesi Masuk</h4>
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_masuk_mulai" class="form-control" value="<?= $sekolah['jam_masuk_mulai'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_masuk_selesai" class="form-control" value="<?= $sekolah['jam_masuk_selesai'] ?>" required>
                </div>
            </div>
            
            <div>
                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: var(--primary);">Sesi Pulang</h4>
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="jam_pulang_mulai" class="form-control" value="<?= $sekolah['jam_pulang_mulai'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="jam_pulang_selesai" class="form-control" value="<?= $sekolah['jam_pulang_selesai'] ?>" required>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Simpan Pengaturan
        </button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
