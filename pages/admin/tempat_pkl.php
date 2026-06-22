<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

$user = getCurrentUser();
$db = getDB();
$msg = '';
$err = '';

// --- Tempat PKL CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_tempat') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = sanitize($_POST['nama']);
        $alamat = sanitize($_POST['alamat']);
        $telepon = sanitize($_POST['telepon']);
        $email = sanitize($_POST['email_tempat']);
        $lat = (float) str_replace(',', '.', $_POST['latitude']);
        $lng = (float) str_replace(',', '.', $_POST['longitude']);
        $radius = (int) ($_POST['radius_meter'] ?? 100);
        $pembimbing = sanitize($_POST['nama_pembimbing']);
        $bidang = sanitize($_POST['bidang_usaha']);

        if ($id === 0) {
            $stmt = $db->prepare("INSERT INTO tempat_pkl (nama,alamat,telepon,email,latitude,longitude,radius_meter,nama_pembimbing,bidang_usaha) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$nama, $alamat, $telepon, $email, $lat, $lng, $radius, $pembimbing, $bidang]);
            $msg = "Tempat PKL berhasil ditambahkan.";
        } else {
            $stmt = $db->prepare("UPDATE tempat_pkl SET nama=?,alamat=?,telepon=?,email=?,latitude=?,longitude=?,radius_meter=?,nama_pembimbing=?,bidang_usaha=? WHERE id=?");
            $stmt->execute([$nama, $alamat, $telepon, $email, $lat, $lng, $radius, $pembimbing, $bidang, $id]);
            $msg = "Tempat PKL berhasil diperbarui.";
        }
    }

    if ($action === 'delete_tempat') {
        $id = (int) $_POST['id'];
        $chk = $db->prepare("SELECT COUNT(*) FROM siswa WHERE tempat_pkl_id=?");
        $chk->execute([$id]);
        if ($chk->fetchColumn() > 0) {
            $err = "Tidak bisa menghapus — masih ada siswa yang PKL di tempat ini.";
        } else {
            $db->prepare("DELETE FROM tempat_pkl WHERE id=?")->execute([$id]);
            $msg = "Tempat PKL dihapus.";
        }
    }

    if (!$err) {
        header("Location: " . APP_URL . "/admin/tempat_pkl" . ($msg ? "?msg=" . urlencode($msg) : ""));
        exit;
    }
}

if (isset($_GET['msg']))
    $msg = $_GET['msg'];

// Export Excel
if (isset($_GET['export']) && $_GET['export'] == '1') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Data_Tempat_PKL.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nama Tempat</th><th>Alamat</th><th>Telepon</th><th>Email</th><th>Nama Pembimbing</th><th>Bidang Usaha</th><th>Latitude</th><th>Longitude</th><th>Radius</th></tr>";
    $tempatExport = getAllTempat();
    foreach ($tempatExport as $tp) {
        echo "<tr>";
        echo "<td>" . $tp['id'] . "</td>";
        echo "<td>" . htmlspecialchars($tp['nama'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['alamat'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['telepon'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['email'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['nama_pembimbing'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['bidang_usaha'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['latitude'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['longitude'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($tp['radius_meter'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

$tempatList = getAllTempat();

$sekolahInfo = $db->query("SELECT tahun_aktif FROM sekolah WHERE id = " . SCHOOL_ID)->fetch();
$currentTahunAktif = $sekolahInfo['tahun_aktif'] ?? '2024';

// Hitung jumlah siswa per tempat
$siswaCounts = [];
$res = $db->prepare("SELECT tempat_pkl_id, COUNT(*) as total FROM siswa WHERE tahun_pkl = ? GROUP BY tempat_pkl_id");
$res->execute([$currentTahunAktif]);
foreach ($res->fetchAll() as $row)
    $siswaCounts[$row['tempat_pkl_id']] = $row['total'];

$pageTitle = 'Kelola Tempat PKL';
$showBack = true;
$extraHead = '
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
    #adminMap { width:100%; height:260px; border-radius:var(--radius-md); }
</style>
';
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
<?php endif; ?>

<div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;">
    <button class="btn btn-primary" onclick="openAddSheet()" style="width:auto;flex:1;">
        <i class="fas fa-plus"></i> Tambah Tempat PKL
    </button>
    <a href="?export=1" class="btn btn-outline" style="width:auto;flex:1;text-align:center;">
        <i class="fas fa-file-export" style="color:#1E6FD9;"></i> Export Excel
    </a>
    <a href="?route=admin/import&type=tempat" class="btn btn-outline" style="width:auto;flex:1;text-align:center;">
        <i class="fas fa-file-excel" style="color:#1D6F42;"></i> Import Excel
    </a>
</div>

<!-- Daftar Tempat PKL -->
<?php foreach ($tempatList as $tp): ?>
    <div class="card" style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div style="flex:1;">
                <div style="font-weight:700;font-size:0.95rem;">
                    <span
                        style="font-size:0.7rem;background:var(--warning);padding:3px 10px;border-radius:20px;font-weight:600;">
                            <?= $tp['id'] ?>
                    </span> &nbsp; &nbsp;

                    <?= $tp['nama'] ?>
                </div>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:3px;"><?= $tp['alamat'] ?></div>
                <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
                    <span
                        style="font-size:0.7rem;background:var(--primary-light);color:var(--primary);padding:3px 10px;border-radius:20px;font-weight:600;">
                            <?= $siswaCounts[$tp['id']] ?? 0 ?> Siswa
                    </span>
                        <?php if ($tp['latitude'] && $tp['longitude']): ?>
                        <span
                            style="font-size:0.7rem;background:#d1fae5;color:#065f46;padding:3px 10px;border-radius:20px;font-weight:600;">
                            <i class="fas fa-location-dot"></i> GPS Tersetting
                        </span>
                        <?php else: ?>
                        <span
                            style="font-size:0.7rem;background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:20px;font-weight:600;">
                            Belum Ada GPS
                        </span>
                        <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <button class="btn btn-primary" style="padding:7px 12px;width:auto;font-size:0.8rem;"
                    onclick="openEditSheet(<?= htmlspecialchars(json_encode($tp)) ?>)">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="POST" action="" onsubmit="return confirm('Hapus tempat ini?')">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete_tempat">
                    <input type="hidden" name="id" value="<?= $tp['id'] ?>">
                    <button class="btn"
                        style="padding:7px 12px;width:auto;font-size:0.8rem;background:var(--error);color:white;">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Bottom Sheet Form -->
<div class="sheet-overlay"></div>
<div class="bottom-sheet" id="sheetTempat" style="max-height:92vh;overflow-y:auto;">
    <div class="sheet-handle"></div>
    <h3 id="sheetTitle" style="margin-bottom:15px;font-size:1.1rem;"><i class="fas fa-building"
            style="color:var(--primary);"></i> Tempat PKL</h3>

    <form method="POST" action="" id="formTempat">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_tempat">
        <input type="hidden" name="id" id="tempatId" value="0">

        <div class="form-group">
            <label class="form-label">Nama Perusahaan / Instansi</label>
            <input type="text" name="nama" id="tNama" class="form-control" required placeholder="Nama tempat PKL">
        </div>
        <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" id="tAlamat" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="telepon" id="tTelepon" class="form-control" placeholder="08xx">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email_tempat" id="tEmail" class="form-control" placeholder="info@email.com">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div class="form-group">
                <label class="form-label">Nama Pembimbing</label>
                <input type="text" name="nama_pembimbing" id="tPembimbing" class="form-control" placeholder="Nama PIC">
            </div>
            <div class="form-group">
                <label class="form-label">Bidang Usaha</label>
                <input type="text" name="bidang_usaha" id="tBidang" class="form-control" placeholder="Teknologi">
            </div>
        </div>

        <!-- Map untuk set lokasi -->
        <div class="form-group">
            <label class="form-label">Lokasi GPS <small style="color:var(--text-muted)">(klik peta atau drag
                    marker)</small></label>
            <div id="adminMap" style="margin-bottom:10px;"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;">
                <div>
                    <label class="form-label" style="font-size:0.75rem;">Latitude</label>
                    <input type="text" name="latitude" id="tLat" class="form-control" placeholder="-6.200000"
                        style="padding:10px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:0.75rem;">Longitude</label>
                    <input type="text" name="longitude" id="tLng" class="form-control" placeholder="106.816666"
                        style="padding:10px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:0.75rem;">Radius (m)</label>
                    <input type="number" name="radius_meter" id="tRadius" class="form-control" value="100"
                        style="padding:10px;">
                </div>
                <button type="button" onclick="useMyLocation()" class="btn btn-outline"
                    style="width:auto;padding:10px 12px;font-size:0.8rem;white-space:nowrap;">
                    <i class="fas fa-location-crosshairs"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:10px;">
            <i class="fas fa-save"></i> Simpan
        </button>
    </form>
</div>

<?php
$tempatJson = json_encode($tempatList);
$extraScript = "
<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
<script>
var adminMap, markerAdmin;

function initMap(lat, lng) {
    if (adminMap) adminMap.remove();
    lat = lat || -6.200000;
    lng = lng || 106.816666;
    adminMap = L.map('adminMap', {scrollWheelZoom: false}).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19
    }).addTo(adminMap);

    markerAdmin = L.marker([lat, lng], {draggable: true}).addTo(adminMap);
    markerAdmin.on('dragend', function(e) {
        setLatLng(e.target.getLatLng().lat, e.target.getLatLng().lng);
    });
    adminMap.on('click', function(e) {
        markerAdmin.setLatLng(e.latlng);
        setLatLng(e.latlng.lat, e.latlng.lng);
    });
}

function setLatLng(lat, lng) {
    document.getElementById('tLat').value = lat.toFixed(6);
    document.getElementById('tLng').value = lng.toFixed(6);
}

function useMyLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            markerAdmin.setLatLng([lat, lng]);
            adminMap.setView([lat, lng], 16);
            setLatLng(lat, lng);
        });
    }
}

function openAddSheet() {
    document.getElementById('sheetTitle').innerHTML = '<i class=\"fas fa-plus\" style=\"color:var(--primary);\"></i> Tambah Tempat PKL';
    document.getElementById('tempatId').value = 0;
    ['tNama','tAlamat','tTelepon','tEmail','tPembimbing','tBidang'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('tLat').value = '';
    document.getElementById('tLng').value = '';
    document.getElementById('tRadius').value = 100;
    openSheet('sheetTempat');
    setTimeout(() => initMap(-6.200000, 106.816666), 300);
}

function openEditSheet(tp) {
    document.getElementById('sheetTitle').innerHTML = '<i class=\"fas fa-edit\" style=\"color:var(--primary);\"></i> Edit Tempat PKL';
    document.getElementById('tempatId').value = tp.id;
    document.getElementById('tNama').value = tp.nama || '';
    document.getElementById('tAlamat').value = tp.alamat || '';
    document.getElementById('tTelepon').value = tp.telepon || '';
    document.getElementById('tEmail').value = tp.email || '';
    document.getElementById('tPembimbing').value = tp.nama_pembimbing || '';
    document.getElementById('tBidang').value = tp.bidang_usaha || '';
    document.getElementById('tLat').value = tp.latitude || '';
    document.getElementById('tLng').value = tp.longitude || '';
    document.getElementById('tRadius').value = tp.radius_meter || 100;
    openSheet('sheetTempat');
    var lat = parseFloat(tp.latitude) || -6.200000;
    var lng = parseFloat(tp.longitude) || 106.816666;
    setTimeout(() => initMap(lat, lng), 300);
}
</script>
";
include __DIR__ . '/../../includes/footer.php'; ?>