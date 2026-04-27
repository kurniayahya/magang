<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

if (!$user) {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

if ($user['role'] !== 'siswa') {
    die("Halaman ini hanya untuk Siswa PKL.");
}

$siswa = getSiswaInfo($user['id']);

if (!$siswa) {
    die("Error: Data siswa tidak ditemukan.");
}

$pageTitle = 'Lokasi Anda';
$showBack = true;
include __DIR__ . '/../includes/header.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? AND tanggal = CURDATE()");
$stmt->execute([$siswa['id']]);
$today = $stmt->fetch();

$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $type = $_POST['type']; // masuk or keluar

    if (!$today && $type === 'masuk') {
        $stmt = $db->prepare("INSERT INTO presensi (siswa_id, tanggal, jam_masuk, lat_masuk, lng_masuk, status) VALUES (?, CURDATE(), CURRENT_TIME(), ?, ?, 'hadir')");
        $stmt->execute([$siswa['id'], $lat, $lng]);
        $statusMsg = 'Berhasil Check-In Masuk!';
    } elseif ($today && $type === 'keluar') {
        $stmt = $db->prepare("UPDATE presensi SET jam_keluar = CURRENT_TIME(), lat_keluar = ?, lng_keluar = ? WHERE id = ?");
        $stmt->execute([$lat, $lng, $today['id']]);
        $statusMsg = 'Berhasil Check-Out Keluar!';
    }
    header("Location: " . APP_URL . "/presensi?msg=" . urlencode($statusMsg));
    exit;
}

if (isset($_GET['msg'])) {
    $statusMsg = $_GET['msg'];
}
?>

<div class="animate-fade-in">
    <?php if ($statusMsg): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <i class="fas fa-check-circle"></i> <?= $statusMsg ?>
        </div>
    <?php endif; ?>

    <div class="map-container card">
        <img src="https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ff4444(<?= $siswa['longitude'] ?? 106.816666 ?>,<?= $siswa['latitude'] ?? -6.200000 ?>)/<?= $siswa['longitude'] ?? 106.816666 ?>,<?= $siswa['latitude'] ?? -6.200000 ?>,15/600x300?access_token=pk.eyJ1IjoiZGV2LWV4YW1wbGUiLCJhIjoiY2t4YnJueHlsMDBicjJ2cW0zeDZ3NGRqNyJ9" class="map-placeholder" alt="Peta Lokasi">
        <div class="map-pin">
            <i class="fas fa-location-dot"></i>
        </div>
        
        <div style="position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%); background: var(--primary); color: white; padding: 10px 20px; border-radius: var(--radius-md); width: 80%; text-align: center; box-shadow: var(--shadow-lg);">
            <div style="font-size: 0.75rem; opacity: 0.9;">Tempat PKL:</div>
            <div style="font-weight: 700; font-size: 0.95rem;"><?= $siswa['tempat_pkl_nama'] ?></div>
        </div>
    </div>

    <div id="locationInfo" style="text-align: center; margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
        <i class="fas fa-satellite-dish"></i> Mencari sinyal GPS...
    </div>

    <form id="presenceForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="lat" id="latInput">
        <input type="hidden" name="lng" id="lngInput">
        <input type="hidden" name="type" id="typeInput" value="<?= !$today ? 'masuk' : 'keluar' ?>">
        
        <?php if (!$today || ($today && !$today['jam_keluar'])): ?>
            <button type="button" id="btnCheck" class="btn btn-primary" style="height: 60px; font-size: 1.1rem; border-radius: 50px;">
                Cek Lokasi & <?= !$today ? 'Check-In' : 'Check-Out' ?>
            </button>
        <?php else: ?>
            <div class="card" style="text-align: center; background: var(--primary-light); border: none;">
                <i class="fas fa-check-double" style="font-size: 2rem; color: var(--primary); margin-bottom: 10px;"></i>
                <div style="font-weight: 700; color: var(--primary);">Anda sudah selesai untuk hari ini</div>
                <div style="font-size: 0.85rem; color: var(--primary);">Masuk: <?= $today['jam_masuk'] ?> | Keluar: <?= $today['jam_keluar'] ?></div>
            </div>
        <?php endif; ?>
    </form>

    <div class="card" style="margin-top: 20px;">
        <div class="card-title">Riwayat Kehadiran (7 Hari Terakhir)</div>
        <div class="activity-list">
            <?php
            $stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 7");
            $stmt->execute([$siswa['id']]);
            $history = $stmt->fetchAll();
            foreach ($history as $h):
            ?>
            <div class="activity-item">
                <div class="activity-icon" style="background: <?= $h['status'] == 'hadir' ? 'var(--primary-light)' : '#FEE2E2' ?>; color: <?= $h['status'] == 'hadir' ? 'var(--primary)' : '#EF4444' ?>;">
                    <i class="fas <?= $h['status'] == 'hadir' ? 'fa-clock' : 'fa-user-xmark' ?>"></i>
                </div>
                <div class="activity-info">
                    <div class="activity-name"><?= getHariNama($h['tanggal']) ?>, <?= formatTanggal($h['tanggal']) ?></div>
                    <div class="activity-time"><?= $h['status'] == 'hadir' ? ($h['jam_masuk'] . ' - ' . ($h['jam_keluar'] ?? '--:--')) : ucfirst($h['status']) ?></div>
                </div>
                <div style="font-size: 0.75rem; font-weight: 600; color: <?= $h['validasi'] == 'valid' ? 'var(--success)' : 'var(--warning)' ?>;">
                    <?= strtoupper($h['validasi']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php 
$extraScript = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('btnCheck');
    const latIn = document.getElementById('latInput');
    const lngIn = document.getElementById('lngInput');
    const locInfo = document.getElementById('locationInfo');
    const form = document.getElementById('presenceForm');

    if (btn) {
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Menunggu Lokasi...';
            locInfo.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Mendapatkan koordinat GPS...';
            
            getLocation(function(res) {
                if (res.error) {
                    alert(res.error);
                    btn.disabled = false;
                    btn.innerHTML = 'Cek Lokasi & ' + (document.getElementById('typeInput').value === 'masuk' ? 'Check-In' : 'Check-Out');
                    locInfo.innerHTML = '<i class=\"fas fa-triangle-exclamation\" style=\"color: var(--error);\"></i> ' + res.error;
                } else {
                    latIn.value = res.lat;
                    lngIn.value = res.lng;
                    locInfo.innerHTML = '<i class=\"fas fa-location-crosshairs\" style=\"color: var(--success);\"></i> Lokasi ditemukan: ' + res.lat.toFixed(6) + ', ' + res.lng.toFixed(6);
                    
                    setTimeout(() => {
                        form.submit();
                    }, 1000);
                }
            });
        });
    }
});
</script>
";
include __DIR__ . '/../includes/footer.php'; 
?>
