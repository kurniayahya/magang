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

$pageTitle = 'Presensi';
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

// Ambil data sekolah untuk rentang jam
$stmt = $db->prepare("SELECT * FROM sekolah WHERE id = ?");
$stmt->execute([SCHOOL_ID]);
$sekolah = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? AND tanggal = CURDATE()");
$stmt->execute([$siswa['id']]);
$today = $stmt->fetch();

$statusMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $lat        = $_POST['lat'];
    $lng        = $_POST['lng'];
    $type       = $_POST['type'];
    $is_wfa     = isset($_POST['is_wfa']) ? 1 : 0;
    $alasan_wfa = sanitize($_POST['alasan_wfa'] ?? '');

    // Waktu server WIB
    $tz          = new DateTimeZone('Asia/Jakarta');
    $currentTime = (new DateTime('now', $tz))->format('H:i:s');

    $jamMasukMulai_s   = !empty($sekolah['jam_masuk_mulai'])   ? $sekolah['jam_masuk_mulai']   : '00:00:00';
    $jamMasukSelesai_s = !empty($sekolah['jam_masuk_selesai']) ? $sekolah['jam_masuk_selesai'] : '23:59:59';
    $jamPulangMulai_s  = !empty($sekolah['jam_pulang_mulai'])  ? $sekolah['jam_pulang_mulai']  : '00:00:00';
    $jamPulangSelesai_s= !empty($sekolah['jam_pulang_selesai'])? $sekolah['jam_pulang_selesai']: '23:59:59';

    $canProceed = false;
    $errorMsg   = '';

    if ($type === 'masuk') {
        if ($today && $today['jam_masuk']) {
            $errorMsg = "Anda sudah melakukan check in hari ini.";
        } elseif ($currentTime < $jamMasukMulai_s || $currentTime > $jamMasukSelesai_s) {
            $errorMsg = "Bukan rentang jam masuk (" . substr($jamMasukMulai_s,0,5) . " - " . substr($jamMasukSelesai_s,0,5) . ")";
        } else {
            $canProceed = true;
        }
    } elseif ($type === 'keluar') {
        if ($currentTime < $jamPulangMulai_s || $currentTime > $jamPulangSelesai_s) {
            $errorMsg = "Bukan rentang jam pulang (" . substr($jamPulangMulai_s,0,5) . " - " . substr($jamPulangSelesai_s,0,5) . ")";
        } else {
            $canProceed = true;
        }
    }

    if ($canProceed) {
        if ($type === 'masuk') {
            $stmt = $db->prepare("INSERT INTO presensi (siswa_id, tanggal, jam_masuk, lat_masuk, lng_masuk, status, is_wfa, alasan_wfa) VALUES (?, CURDATE(), CURRENT_TIME(), ?, ?, 'hadir', ?, ?)");
            $stmt->execute([$siswa['id'], $lat, $lng, $is_wfa, $alasan_wfa]);
            $statusMsg = 'Berhasil Presensi Masuk!';
        } elseif ($type === 'keluar') {
            if ($today) {
                $stmt = $db->prepare("UPDATE presensi SET jam_keluar = CURRENT_TIME(), lat_keluar = ?, lng_keluar = ? WHERE id = ?");
                $stmt->execute([$lat, $lng, $today['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO presensi (siswa_id, tanggal, jam_keluar, lat_keluar, lng_keluar, status, is_wfa, alasan_wfa) VALUES (?, CURDATE(), CURRENT_TIME(), ?, ?, 'hadir', ?, ?)");
                $stmt->execute([$siswa['id'], $lat, $lng, $is_wfa, $alasan_wfa]);
            }
            $statusMsg = 'Berhasil Presensi Pulang!';
        }
        header("Location: " . APP_URL . "/presensi?msg=" . urlencode($statusMsg));
    } else {
        header("Location: " . APP_URL . "/presensi?err=" . urlencode($errorMsg));
    }
    exit;
}

if (isset($_GET['msg'])) $statusMsg = $_GET['msg'];
$errMsg = $_GET['err'] ?? '';
?>

<div class="animate-fade-in">
    <?php if ($statusMsg): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <i class="fas fa-check-circle"></i> <?= $statusMsg ?>
        </div>
    <?php endif; ?>
    <?php if ($errMsg): ?>
        <div class="alert alert-danger alert-auto-dismiss">
            <i class="fas fa-exclamation-circle"></i> <?= $errMsg ?>
        </div>
    <?php endif; ?>

    <div class="map-container card">
        <div id="map"></div>
    </div>

    <div style="background: var(--primary); color: white; padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 20px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-md);">
        <i class="fas fa-building" style="font-size: 1.3rem; opacity: 0.85;"></i>
        <div>
            <div style="font-size: 0.72rem; opacity: 0.85;">Tempat PKL:</div>
            <div style="font-weight: 700; font-size: 0.95rem;"><?= $siswa['tempat_pkl_nama'] ?? '-' ?></div>
        </div>
    </div>

    <div id="locationInfo" style="text-align: center; margin-bottom: 20px; font-size: 0.85rem; color: var(--text-muted);">
        <i class="fas fa-satellite-dish"></i> Tekan tombol untuk presensi...
    </div>

    <form id="presenceForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="lat" id="latInput">
        <input type="hidden" name="lng" id="lngInput">
        <input type="hidden" name="type" id="typeInput" value="">

        <div style="margin-bottom: 15px; background: white; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
                <input type="checkbox" name="is_wfa" id="wfaCheck" style="width: 18px; height: 18px;" value="1" <?= ($today && $today['is_wfa']) ? 'checked' : '' ?>>
                WFA (Work From Anywhere)
            </label>
            <div id="wfaReasonBox" style="display: <?= ($today && $today['is_wfa']) ? 'block' : 'none' ?>; margin-top: 10px;">
                <label class="form-label" style="font-size: 0.8rem;">Alasan WFA</label>
                <textarea name="alasan_wfa" id="wfaReason" class="form-control" rows="2" placeholder="Tuliskan alasan WFA..."><?= htmlspecialchars($today['alasan_wfa'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <button type="button" id="btnMasuk" class="btn" style="height: 60px; font-size: 1rem; border-radius: 50px; background: #10B981; color: white; font-weight: 700; border: none; cursor: pointer;">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
            <button type="button" id="btnPulang" class="btn" style="height: 60px; font-size: 1rem; border-radius: 50px; background: #EF4444; color: white; font-weight: 700; border: none; cursor: pointer;">
                <i class="fas fa-sign-out-alt"></i> Pulang
            </button>
        </div>
    </form>

    <?php if ($today): ?>
    <div class="card" style="margin-top: 15px; text-align: center; background: var(--primary-light);">
        <div style="font-size: 0.8rem; color: var(--text-muted);">Status Hari Ini</div>
        <div style="font-weight: 700; color: var(--primary);">
            Masuk: <?= $today['jam_masuk'] ?? '--:--' ?> &nbsp;|&nbsp;
            Pulang: <?= $today['jam_keluar'] ?? '--:--' ?>
            <?php if ($today['is_wfa']): ?> &nbsp;<span style="background:var(--primary);color:white;padding:1px 8px;border-radius:12px;font-size:0.7rem;">WFA</span><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-top: 20px;">
        <div class="card-title">Riwayat Kehadiran (7 Hari Terakhir)</div>
        <div class="activity-list">
            <?php
            $stmt = $db->prepare("SELECT * FROM presensi WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 7");
            $stmt->execute([$siswa['id']]);
            $history = $stmt->fetchAll();
            foreach ($history as $h): ?>
            <div class="activity-item">
                <div class="activity-icon" style="background: <?= $h['status'] == 'hadir' ? 'var(--primary-light)' : '#FEE2E2' ?>; color: <?= $h['status'] == 'hadir' ? 'var(--primary)' : '#EF4444' ?>;">
                    <i class="fas <?= $h['status'] == 'hadir' ? 'fa-clock' : 'fa-user-xmark' ?>"></i>
                </div>
                <div class="activity-info">
                    <div class="activity-name"><?= getHariNama($h['tanggal']) ?>, <?= formatTanggal($h['tanggal']) ?></div>
                    <div class="activity-time"><?= $h['status'] == 'hadir' ? (($h['jam_masuk'] ?? '--') . ' - ' . ($h['jam_keluar'] ?? '--:--')) : ucfirst($h['status']) ?></div>
                </div>
                <div style="font-size: 0.7rem; font-weight: 700; color: <?= $h['validasi'] == 'valid' ? 'var(--success)' : 'var(--warning)' ?>;">
                    <?= strtoupper($h['validasi']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// Siapkan variabel untuk JS
$pklLat        = (float)($siswa['latitude']  ?? -6.200000);
$pklLng        = (float)($siswa['longitude'] ?? 106.816666);
$pklNama       = addslashes($siswa['tempat_pkl_nama'] ?? 'Tempat PKL');
$radiusMeter   = !empty($siswa['radius_meter']) ? (int)$siswa['radius_meter'] : 100;
$jamMasukMulai    = !empty($sekolah['jam_masuk_mulai'])   ? $sekolah['jam_masuk_mulai']   : '00:00:00';
$jamMasukSelesai  = !empty($sekolah['jam_masuk_selesai']) ? $sekolah['jam_masuk_selesai'] : '23:59:59';
$jamPulangMulai   = !empty($sekolah['jam_pulang_mulai'])  ? $sekolah['jam_pulang_mulai']  : '00:00:00';
$jamPulangSelesai = !empty($sekolah['jam_pulang_selesai'])? $sekolah['jam_pulang_selesai']: '23:59:59';

include __DIR__ . '/../includes/footer.php';
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var pklLat  = <?= $pklLat ?>;
    var pklLng  = <?= $pklLng ?>;
    var pklNama = '<?= $pklNama ?>';
    var radiusMeter  = <?= $radiusMeter ?>;
    var jamMasukMulai    = '<?= $jamMasukMulai ?>';
    var jamMasukSelesai  = '<?= $jamMasukSelesai ?>';
    var jamPulangMulai   = '<?= $jamPulangMulai ?>';
    var jamPulangSelesai = '<?= $jamPulangSelesai ?>';

    // Init Leaflet
    var map = L.map('map', { zoomControl: true, scrollWheelZoom: false }).setView([pklLat, pklLng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
    var pklIcon = L.divIcon({
        html: '<div style="background:#1E6FD9;width:30px;height:30px;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,.4);"></div>',
        iconSize: [30,30], iconAnchor: [15,15], className: ''
    });
    L.marker([pklLat, pklLng], { icon: pklIcon }).addTo(map).bindPopup('<b>' + pklNama + '</b>').openPopup();

    var userMarker = null;
    var btnMasuk  = document.getElementById('btnMasuk');
    var btnPulang = document.getElementById('btnPulang');
    var typeIn    = document.getElementById('typeInput');
    var latIn     = document.getElementById('latInput');
    var lngIn     = document.getElementById('lngInput');
    var locInfo   = document.getElementById('locationInfo');
    var presForm  = document.getElementById('presenceForm');
    var wfaCheck  = document.getElementById('wfaCheck');
    var wfaBox    = document.getElementById('wfaReasonBox');
    var wfaReason = document.getElementById('wfaReason');

    if (wfaCheck) {
        wfaCheck.addEventListener('change', function() {
            wfaBox.style.display = this.checked ? 'block' : 'none';
        });
    }

    function toMin(t) { var p = t.split(':'); return parseInt(p[0])*60+parseInt(p[1]); }

    function getDistance(la1,lo1,la2,lo2) {
        var R=6371000, d2r=Math.PI/180;
        var dLat=(la2-la1)*d2r, dLon=(lo2-lo1)*d2r;
        var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(la1*d2r)*Math.cos(la2*d2r)*Math.sin(dLon/2)*Math.sin(dLon/2);
        return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
    }

    function resetBtns() {
        btnMasuk.disabled  = false; btnMasuk.innerHTML  = '<i class="fas fa-sign-in-alt"></i> Masuk';
        btnPulang.disabled = false; btnPulang.innerHTML = '<i class="fas fa-sign-out-alt"></i> Pulang';
    }

    function doPresence(type) {
        var now = new Date();
        var nowMin = now.getHours()*60 + now.getMinutes();
        var nowStr = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');

        if (type === 'masuk') {
            if (nowMin < toMin(jamMasukMulai) || nowMin > toMin(jamMasukSelesai)) {
                alert('Bukan rentang jam masuk (' + jamMasukMulai.substr(0,5) + ' - ' + jamMasukSelesai.substr(0,5) + ')\nJam sekarang: ' + nowStr);
                return;
            }
        } else {
            if (nowMin < toMin(jamPulangMulai) || nowMin > toMin(jamPulangSelesai)) {
                alert('Bukan rentang jam pulang (' + jamPulangMulai.substr(0,5) + ' - ' + jamPulangSelesai.substr(0,5) + ')\nJam sekarang: ' + nowStr);
                return;
            }
        }

        if (wfaCheck && wfaCheck.checked && wfaReason && !wfaReason.value.trim()) {
            alert('Isi alasan WFA terlebih dahulu.'); return;
        }

        typeIn.value = type;
        btnMasuk.disabled  = true; btnMasuk.innerHTML  = '<i class="fas fa-spinner fa-spin"></i>';
        btnPulang.disabled = true; btnPulang.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        locInfo.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Mendapatkan lokasi GPS...';

        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            var isWfa = wfaCheck && wfaCheck.checked;
            var dist  = getDistance(lat, lng, pklLat, pklLng);

            if (!isWfa && dist > radiusMeter) {
                alert('Di luar radius PKL (' + Math.round(dist) + 'm, batas ' + radiusMeter + 'm).\nGunakan WFA jika diizinkan.');
                locInfo.innerHTML = '<i class="fas fa-triangle-exclamation" style="color:red;"></i> Jarak: ' + Math.round(dist) + 'm';
                resetBtns(); return;
            }

            latIn.value = lat; lngIn.value = lng;
            locInfo.innerHTML = '<i class="fas fa-location-crosshairs" style="color:green;"></i> ' + lat.toFixed(5) + ', ' + lng.toFixed(5);

            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.circleMarker([lat,lng],{radius:10,color:'#10B981',fillColor:'#10B981',fillOpacity:0.7,weight:3})
                .addTo(map).bindPopup('Posisi Anda').openPopup();
            map.setView([lat,lng],17);

            setTimeout(function(){ presForm.submit(); }, 1000);

        }, function(err) {
            var msg = err.code===1?'Izin lokasi ditolak.':err.code===2?'Lokasi tidak tersedia.':'Waktu permintaan habis.';
            alert(msg); locInfo.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:red;"></i> '+msg; resetBtns();
        }, { timeout: 15000, enableHighAccuracy: true });
    }

    if (btnMasuk)  btnMasuk.addEventListener('click',  function(){ doPresence('masuk'); });
    if (btnPulang) btnPulang.addEventListener('click', function(){ doPresence('keluar'); });
})();
</script>
