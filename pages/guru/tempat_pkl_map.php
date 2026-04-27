<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('guru');

$user = getCurrentUser();
$db   = getDB();
$msg  = '';

// Ambil tempat PKL dari siswa bimbingan guru ini
$siswaBimbingan = getSiswaBimbingan($user['id']);
$tempatIds = array_unique(array_filter(array_column($siswaBimbingan, 'tempat_pkl_id')));

// Ambil data tempat PKL
$tempatList = [];
if ($tempatIds) {
    $inClause = implode(',', array_fill(0, count($tempatIds), '?'));
    $stmt = $db->prepare("SELECT * FROM tempat_pkl WHERE id IN ($inClause)");
    $stmt->execute($tempatIds);
    $tempatList = $stmt->fetchAll();
}

// Update lokasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int)$_POST['tempat_id'];
    $lat    = (float)str_replace(',', '.', $_POST['latitude']);
    $lng    = (float)str_replace(',', '.', $_POST['longitude']);
    $radius = (int)($_POST['radius_meter'] ?? 100);

    // Pastikan id tempat adalah milik siswa bimbingan
    if (in_array($id, $tempatIds)) {
        $stmt = $db->prepare("UPDATE tempat_pkl SET latitude=?,longitude=?,radius_meter=? WHERE id=?");
        $stmt->execute([$lat, $lng, $radius, $id]);
        $msg = "Lokasi berhasil diperbarui.";
    }
    header("Location: " . APP_URL . "/guru/peta?msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$pageTitle = 'Peta Lokasi PKL';
$showBack  = true;
$extraHead = '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    #guruMap { width:100%; height:300px; border-radius:var(--radius-md); }
</style>
';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>

<?php if (empty($tempatList)): ?>
<div class="card" style="text-align:center;padding:40px 20px;">
    <i class="fas fa-map" style="font-size:3rem;color:var(--text-muted);margin-bottom:15px;display:block;"></i>
    <div style="font-weight:700;">Belum Ada Data Tempat PKL</div>
    <div style="color:var(--text-muted);font-size:0.85rem;margin-top:8px;">Siswa bimbingan Anda belum memiliki tempat PKL yang terdaftar.</div>
</div>
<?php endif; ?>

<?php foreach ($tempatList as $tp): ?>
<div class="card" style="margin-bottom:20px;">
    <div style="margin-bottom:12px;">
        <div style="font-weight:700;font-size:1rem;"><?= $tp['nama'] ?></div>
        <div style="font-size:0.78rem;color:var(--text-muted);"><?= $tp['alamat'] ?></div>
        <?php
        // Siswa yang PKL di sini
        $siswaDisini = array_filter($siswaBimbingan, fn($s) => $s['tempat_pkl_id'] == $tp['id']);
        ?>
        <div style="margin-top:6px;font-size:0.75rem;color:var(--primary);font-weight:600;">
            <?= count($siswaDisini) ?> siswa bimbingan PKL di sini
        </div>
    </div>

    <!-- Map -->
    <div id="map_<?= $tp['id'] ?>" style="width:100%;height:250px;border-radius:var(--radius-md);margin-bottom:12px;"></div>

    <!-- Form update lokasi -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="tempat_id" value="<?= $tp['id'] ?>">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;margin-bottom:10px;">
            <div>
                <label class="form-label" style="font-size:0.75rem;">Latitude</label>
                <input type="text" name="latitude" id="lat_<?= $tp['id'] ?>" class="form-control"
                       value="<?= $tp['latitude'] ?>" placeholder="-6.200000" style="padding:10px;font-size:0.85rem;">
            </div>
            <div>
                <label class="form-label" style="font-size:0.75rem;">Longitude</label>
                <input type="text" name="longitude" id="lng_<?= $tp['id'] ?>" class="form-control"
                       value="<?= $tp['longitude'] ?>" placeholder="106.816666" style="padding:10px;font-size:0.85rem;">
            </div>
            <div>
                <label class="form-label" style="font-size:0.75rem;">Radius (m)</label>
                <input type="number" name="radius_meter" id="radius_<?= $tp['id'] ?>" class="form-control"
                       value="<?= $tp['radius_meter'] ?? 100 ?>" style="padding:10px;font-size:0.85rem;">
            </div>
            <button type="button" onclick="useMyLoc(<?= $tp['id'] ?>)"
                    class="btn btn-outline" style="width:auto;padding:10px 12px;font-size:0.8rem;" title="Gunakan Lokasi Saya">
                <i class="fas fa-location-crosshairs"></i>
            </button>
        </div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:10px;">
            <i class="fas fa-info-circle"></i> Klik pada peta atau drag marker untuk mengatur titik lokasi.
        </div>
        <button type="submit" class="btn btn-primary" style="height:48px;">
            <i class="fas fa-save"></i> Simpan Lokasi
        </button>
    </form>
</div>
<?php endforeach; ?>

<?php
$tempatJson = json_encode($tempatList);
$extraScript = "
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
<script>
var maps = {}, markers = {};

document.addEventListener('DOMContentLoaded', function() {
    var tempatList = $tempatJson;
    tempatList.forEach(function(tp) {
        var lat = parseFloat(tp.latitude)  || -6.200000;
        var lng = parseFloat(tp.longitude) || 106.816666;
        var id  = tp.id;

        maps[id] = L.map('map_' + id, {scrollWheelZoom: false}).setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap', maxZoom: 19
        }).addTo(maps[id]);

        markers[id] = L.marker([lat, lng], {draggable: true}).addTo(maps[id])
            .bindPopup(tp.nama).openPopup();

        markers[id].on('dragend', function(e) {
            var ll = e.target.getLatLng();
            document.getElementById('lat_' + id).value = ll.lat.toFixed(6);
            document.getElementById('lng_' + id).value = ll.lng.toFixed(6);
        });

        maps[id].on('click', function(e) {
            markers[id].setLatLng(e.latlng);
            document.getElementById('lat_' + id).value = e.latlng.lat.toFixed(6);
            document.getElementById('lng_' + id).value = e.latlng.lng.toFixed(6);
        });

        // Radius circle
        if (tp.radius_meter) {
            L.circle([lat, lng], {radius: tp.radius_meter, color: '#1E6FD9', fillOpacity: 0.1}).addTo(maps[id]);
        }
    });
});

function useMyLoc(id) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            markers[id].setLatLng([lat, lng]);
            maps[id].setView([lat, lng], 17);
            document.getElementById('lat_' + id).value = lat.toFixed(6);
            document.getElementById('lng_' + id).value = lng.toFixed(6);
        });
    }
}
</script>
";
include __DIR__ . '/../../includes/footer.php'; ?>
