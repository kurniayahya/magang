<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('guru');

$user = getCurrentUser();
$db   = getDB();
$msg  = '';

// Ambil id siswa bimbingan guru ini
$siswaBimbingan = getSiswaBimbingan($user['id']);
$siswaIds = array_column($siswaBimbingan, 'id');

// Action validasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $jurnalId = (int)$_POST['jurnal_id'];
    $action   = $_POST['action_val']; // validasi | tolak
    $catatan  = sanitize($_POST['catatan'] ?? '');

    // Pastikan jurnal ini milik siswa bimbingan guru
    if ($siswaIds) {
        $inClause = implode(',', array_fill(0, count($siswaIds), '?'));
        $chkParams = array_merge([$jurnalId], $siswaIds);
        $chk = $db->prepare("SELECT id FROM jurnal WHERE id=? AND siswa_id IN ($inClause)");
        $chk->execute($chkParams);
        if ($chk->fetch()) {
            $status = ($action === 'validasi') ? 'divalidasi' : 'ditolak';
            $stmt = $db->prepare("UPDATE jurnal SET status=?, validasi_oleh=?, catatan_validator=? WHERE id=?");
            $stmt->execute([$status, $user['id'], $catatan, $jurnalId]);
            $msg = $action === 'validasi' ? "Jurnal berhasil divalidasi." : "Jurnal ditolak.";
        }
    }
    header("Location: " . APP_URL . "/guru/validasi?msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Ambil jurnal pending dari siswa bimbingan
$jurnalList = [];
if ($siswaIds) {
    $inClause = implode(',', array_fill(0, count($siswaIds), '?'));
    $stmt = $db->prepare("
        SELECT j.*, u.nama as siswa_nama, s.kelas, tp.nama as tempat_pkl_nama
        FROM jurnal j
        JOIN siswa s ON j.siswa_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
        WHERE j.siswa_id IN ($inClause) AND j.status = 'terkirim'
        ORDER BY j.tanggal DESC
    ");
    $stmt->execute($siswaIds);
    $jurnalList = $stmt->fetchAll();
}

$pageTitle = 'Validasi Jurnal';
$showBack  = true;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>

<?php if (empty($jurnalList)): ?>
<div class="card" style="text-align:center;padding:40px 20px;">
    <i class="fas fa-clipboard-check" style="font-size:3rem;color:var(--success);margin-bottom:15px;display:block;"></i>
    <div style="font-weight:700;font-size:1rem;margin-bottom:8px;">Semua Jurnal Sudah Divalidasi</div>
    <div style="color:var(--text-muted);font-size:0.85rem;">Tidak ada jurnal yang menunggu validasi.</div>
</div>
<?php endif; ?>

<?php foreach ($jurnalList as $j): ?>
<div class="card" style="margin-bottom:15px;">
    <!-- Header Jurnal -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
        <div>
            <div style="font-weight:700;font-size:0.95rem;"><?= $j['siswa_nama'] ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);"><?= $j['kelas'] ?> • <?= $j['tempat_pkl_nama'] ?></div>
        </div>
        <div style="font-size:0.75rem;font-weight:600;color:var(--text-muted);text-align:right;">
            <?= getHariNama($j['tanggal']) ?><br><?= formatTanggal($j['tanggal']) ?>
        </div>
    </div>
    <!-- Isi Jurnal -->
    <div style="background:var(--background);border-radius:var(--radius-sm);padding:12px;margin-bottom:12px;border:1px solid var(--border);">
        <div style="font-weight:600;font-size:0.85rem;margin-bottom:5px;color:var(--primary);">
            <i class="fas fa-tasks"></i> <?= $j['kegiatan'] ?>
        </div>
        <?php if ($j['deskripsi']): ?>
        <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.6;"><?= nl2br($j['deskripsi']) ?></div>
        <?php endif; ?>
    </div>
    <!-- Form Validasi -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="jurnal_id" value="<?= $j['id'] ?>">
        <div class="form-group">
            <textarea name="catatan" class="form-control" rows="2"
                      placeholder="Catatan untuk siswa (opsional)..." style="font-size:0.85rem;"></textarea>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="submit" name="action_val" value="validasi" class="btn btn-primary"
                    style="flex:1;background:linear-gradient(135deg,var(--success),#059669);">
                <i class="fas fa-check-circle"></i> Validasi
            </button>
            <button type="submit" name="action_val" value="tolak" class="btn"
                    style="flex:1;background:var(--error);color:white;"
                    onclick="return confirm('Tolak jurnal ini?')">
                <i class="fas fa-times-circle"></i> Tolak
            </button>
        </div>
    </form>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
