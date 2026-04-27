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

$pageTitle = 'Lokasi Anda';
$showBack = true;
$extraHead = '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { width: 100%; height: 280px; border-radius: var(--radius-md); z-index: 1; }
    .map-container { padding: 0; overflow: hidden; }
</style>
';
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
        <div id="map"></div>
    </div>
    <div style="background: var(--primary); color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-md);">
        <i class="fas fa-building" style="font-size: 1.3rem; opacity: 0.85;"></i>
        <div>
            <div style="font-size: 0.72rem; opacity: 0.85;">Tempat PKL:</div>
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
$pklLat  = $siswa['latitude']  ?? -6.200000;
$pklLng  = $siswa['longitude'] ?? 106.816666;
$pklNama = addslashes($siswa['tempat_pkl_nama'] ?? 'Tempat PKL');
$extraScript = "
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    /* ========== LEAFLET MAP ========== */
    var pklLat  = {$pklLat};
    var pklLng  = {$pklLng};
    var pklNama = '{$pklNama}';

    var map = L.map('map', { zoomControl: true, scrollWheelZoom: false }).setView([pklLat, pklLng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href=\"https://www.openstreetmap.org/copyright\">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    var pklIcon = L.divIcon({
        html: '<div style=\"background:var(--primary);width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);\"></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -38],
        className: ''
    });

    L.marker([pklLat, pklLng], { icon: pklIcon })
        .addTo(map)
        .bindPopup('<b>' + pklNama + '</b>')
        .openPopup();

    /* ========== GPS PRESENCE ========== */
    var userMarker = null;

    const btn     = document.getElementById('btnCheck');
    const latIn   = document.getElementById('latInput');
    const lngIn   = document.getElementById('lngInput');
    const locInfo = document.getElementById('locationInfo');
    const form    = document.getElementById('presenceForm');

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

                    if (userMarker) map.removeLayer(userMarker);
                    userMarker = L.circleMarker([res.lat, res.lng], {
                        radius: 10, color: '#10B981', fillColor: '#10B981',
                        fillOpacity: 0.6, weight: 3
                    }).addTo(map).bindPopup('Lokasi Anda').openPopup();
                    map.setView([res.lat, res.lng], 17);

                    setTimeout(() => { form.submit(); }, 1200);
                }
            });
        });
    }
});
</script>
";
include __DIR__ . '/../includes/footer.php'; 
?>
