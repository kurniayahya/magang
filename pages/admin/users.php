<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

$user = getCurrentUser();
$db   = getDB();
$msg  = '';
$err  = '';

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // Tambah / Edit User
    if ($action === 'save_user') {
        $uid   = (int)($_POST['uid'] ?? 0);
        $nama  = sanitize($_POST['nama']);
        $email = sanitize($_POST['email']);
        $role  = $_POST['role'];
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $pass  = $_POST['password'] ?? '';

        if ($uid === 0) {
            // Cek duplikat email
            $chk = $db->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch()) { $err = "Email sudah digunakan."; }
            else {
                $hash = hashPassword($pass ?: 'password');
                $stmt = $db->prepare("INSERT INTO users (nama,email,password,role,aktif) VALUES (?,?,?,?,?)");
                $stmt->execute([$nama,$email,$hash,$role,$aktif]);
                $newId = $db->lastInsertId();

                if ($role === 'siswa') {
                    $s = $db->prepare("INSERT INTO siswa (user_id,nis,kelas,jurusan_id,sekolah_id,tempat_pkl_id,guru_pembimbing_id,tanggal_mulai,tanggal_selesai,total_hari_pkl,tahun_pkl) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $s->execute([
                        $newId,
                        sanitize($_POST['nis']),
                        sanitize($_POST['kelas']),
                        (int)$_POST['jurusan_id'],
                        SCHOOL_ID,
                        (int)$_POST['tempat_pkl_id'] ?: null,
                        (int)$_POST['guru_pembimbing_id'] ?: null,
                        $_POST['tanggal_mulai'] ?: null,
                        $_POST['tanggal_selesai'] ?: null,
                        (int)($_POST['total_hari_pkl'] ?: 90),
                        sanitize($_POST['tahun_pkl'] ?? '')
                    ]);
                } elseif ($role === 'guru') {
                    // Check duplicate kode
                    $kode = sanitize($_POST['kode']);
                    $chkKode = $db->prepare("SELECT id FROM guru WHERE kode = ?");
                    $chkKode->execute([$kode]);
                    if ($chkKode->fetch()) {
                        $err = "Kode Guru sudah digunakan.";
                        $db->prepare("DELETE FROM users WHERE id=?")->execute([$newId]); // rollback
                    } else {
                        $g = $db->prepare("INSERT INTO guru (user_id,nip,kode,sekolah_id) VALUES (?,?,?,?)");
                        $g->execute([$newId, sanitize($_POST['nip']), $kode, SCHOOL_ID]);
                    }
                }
                if (!$err) {
                    $msg = "User berhasil ditambahkan.";
                }
            }
        } else {
            // Update user
            $stmt = $db->prepare("UPDATE users SET nama=?,email=?,role=?,aktif=? WHERE id=?");
            $stmt->execute([$nama,$email,$role,$aktif,$uid]);
            if ($pass) {
                $h = $db->prepare("UPDATE users SET password=? WHERE id=?");
                $h->execute([hashPassword($pass), $uid]);
            }
            $msg = "User berhasil diperbarui.";
        }
    }

    // Hapus user
    if ($action === 'delete_user') {
        $uid = (int)$_POST['uid'];
        if ($uid !== (int)$_SESSION['user_id']) {
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $msg = "User dihapus.";
        } else {
            $err = "Tidak bisa menghapus akun sendiri.";
        }
    }

    // Reset password
    if ($action === 'reset_password') {
        $uid = (int)$_POST['uid'];
        $newPass = hashPassword('password');
        $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$newPass, $uid]);
        $msg = "Password direset ke 'password'.";
    }

    if (!$err) {
        header("Location: " . APP_URL . "/admin/users" . ($msg ? "?msg=" . urlencode($msg) : ""));
        exit;
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

// Filter
$sekolahInfo = $db->query("SELECT tahun_aktif FROM sekolah WHERE id = " . SCHOOL_ID)->fetch();
$currentTahunAktif = $sekolahInfo['tahun_aktif'] ?? '2024';

$filterRole = $_GET['role'] ?? '';
$search     = sanitize($_GET['q'] ?? '');
$filterTahun = sanitize($_GET['tahun'] ?? $currentTahunAktif);

$where = "WHERE 1=1";
$params = [];
if ($filterRole) { $where .= " AND u.role=?"; $params[] = $filterRole; }
if ($search)     { $where .= " AND (u.nama LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

// Apply filter tahun hanya untuk siswa
if ($filterTahun && $filterRole !== 'admin' && $filterRole !== 'guru') {
    $where .= " AND (u.role != 'siswa' OR s.tahun_pkl = ?)";
    $params[] = $filterTahun;
}

$stmt = $db->prepare("SELECT u.*, s.nis, s.kelas, s.tahun_pkl, g.kode as guru_kode FROM users u LEFT JOIN siswa s ON u.id = s.user_id LEFT JOIN guru g ON u.id = g.user_id $where ORDER BY u.role, u.nama LIMIT 100");
$stmt->execute($params);
$users = $stmt->fetchAll();

$tempatList  = getAllTempat();
$jurusanList = getAllJurusan();
$guruList    = getAllGuru();

$pageTitle = 'Kelola User';
$showBack  = true;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-auto-dismiss"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card" style="padding:15px;margin-bottom:15px;">
    <form method="GET" action="" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <input type="hidden" name="route" value="admin/users">
        <input type="text" name="q" class="form-control" style="flex:1;min-width:120px;padding:10px 14px;"
               placeholder="Cari nama / email..." value="<?= $search ?>">
        <input type="text" name="tahun" class="form-control" style="width:100px;padding:10px 14px;"
               placeholder="Tahun" value="<?= $filterTahun ?>">
        <select name="role" class="form-control" style="width:auto;padding:10px 14px;" onchange="this.form.submit()">
            <option value="">Semua Role</option>
            <?php foreach(['siswa','guru','admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $filterRole===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" style="width:auto;padding:10px 18px;"><i class="fas fa-search"></i></button>
    </form>
</div>

<!-- Tombol Tambah -->
<button class="btn btn-primary" onclick="openSheet('sheetAddUser')" style="margin-bottom:15px;">
    <i class="fas fa-user-plus"></i> Tambah User
</button>

<!-- List Users -->
<div class="card" style="padding:0;overflow:hidden;">
    <?php foreach ($users as $u): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border);">
        <img src="<?= getAvatarUrl($u['foto']??null, $u['nama']) ?>"
             style="width:42px;height:42px;border-radius:50%;object-fit:cover;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:0.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $u['nama'] ?></div>
            <div style="font-size:0.75rem;color:var(--text-muted);"><?= $u['email'] ?></div>
            <div style="display:flex;gap:6px;margin-top:4px;flex-wrap:wrap;">
                <span style="font-size:0.65rem;padding:2px 8px;border-radius:20px;background:<?= $u['role']==='admin'?'#1e1e3f':($u['role']==='guru'?'#1a4731':'#1e3a5f') ?>;color:white;font-weight:700;">
                    <?= strtoupper($u['role']) ?>
                </span>
                <?php if ($u['nis']): ?>
                <span style="font-size:0.65rem;padding:2px 8px;border-radius:20px;background:var(--border);color:var(--text-muted);">NIS: <?= $u['nis'] ?></span>
                <span style="font-size:0.65rem;padding:2px 8px;border-radius:20px;background:#e0f2fe;color:#0369a1;">Tahun: <?= $u['tahun_pkl'] ?: '-' ?></span>
                <?php endif; ?>
                <?php if ($u['guru_kode']): ?>
                <span style="font-size:0.65rem;padding:2px 8px;border-radius:20px;background:#fef3c7;color:#b45309;">Kode: <?= $u['guru_kode'] ?></span>
                <?php endif; ?>
                <span style="font-size:0.65rem;padding:2px 8px;border-radius:20px;background:<?= $u['aktif']?'#d1fae5':'#fee2e2' ?>;color:<?= $u['aktif']?'#065f46':'#991b1b' ?>;">
                    <?= $u['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
            <!-- Reset PW -->
            <form method="POST" action="" onsubmit="return confirm('Reset password ke \'password\'?')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <button class="btn" style="padding:6px 10px;width:auto;font-size:0.7rem;background:var(--warning);color:white;"
                        title="Reset Password"><i class="fas fa-key"></i></button>
            </form>
            <!-- Hapus -->
            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
            <form method="POST" action="" onsubmit="return confirm('Hapus user ini?')">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <button class="btn" style="padding:6px 10px;width:auto;font-size:0.7rem;background:var(--error);color:white;"
                        title="Hapus"><i class="fas fa-trash"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
    <p style="text-align:center;color:var(--text-muted);padding:30px;">Tidak ada user ditemukan.</p>
    <?php endif; ?>
</div>

<!-- Bottom Sheet: Tambah User -->
<div class="sheet-overlay"></div>
<div class="bottom-sheet" id="sheetAddUser" style="max-height:90vh;overflow-y:auto;">
    <div class="sheet-handle"></div>
    <h3 style="margin-bottom:18px;font-size:1.1rem;"><i class="fas fa-user-plus" style="color:var(--primary);"></i> Tambah User Baru</h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_user">
        <input type="hidden" name="uid" value="0">

        <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required placeholder="Nama lengkap">
        </div>
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required placeholder="email@contoh.com">
        </div>
        <div class="form-group">
            <label class="form-label">Password <small style="color:var(--text-muted)">(kosong = 'password')</small></label>
            <input type="password" name="password" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group">
            <label class="form-label">Role</label>
            <select name="role" class="form-control" id="roleSelect" onchange="toggleRoleFields()">
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <!-- Field Siswa -->
        <div id="fieldsSiswa">
            <div class="form-group">
                <label class="form-label">Tahun PKL</label>
                <input type="text" name="tahun_pkl" class="form-control" value="<?= htmlspecialchars($currentTahunAktif) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">NIS</label>
                <input type="text" name="nis" class="form-control" placeholder="NIS siswa">
            </div>
            <div class="form-group">
                <label class="form-label">Kelas</label>
                <input type="text" name="kelas" class="form-control" placeholder="XII TKR 1">
            </div>
            <div class="form-group">
                <label class="form-label">Jurusan</label>
                <select name="jurusan_id" class="form-control">
                    <option value="">- Pilih Jurusan -</option>
                    <?php foreach($jurusanList as $j): ?>
                    <option value="<?= $j['id'] ?>"><?= $j['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tempat PKL</label>
                <select name="tempat_pkl_id" class="form-control">
                    <option value="">- Pilih Tempat PKL -</option>
                    <?php foreach($tempatList as $tp): ?>
                    <option value="<?= $tp['id'] ?>"><?= $tp['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Guru Pembimbing</label>
                <select name="guru_pembimbing_id" class="form-control">
                    <option value="">- Pilih Guru -</option>
                    <?php foreach($guruList as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= $g['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="form-group">
                    <label class="form-label">Tgl Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Tgl Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Hari PKL</label>
                <input type="number" name="total_hari_pkl" class="form-control" value="90" min="1">
            </div>
        </div>

        <!-- Field Guru -->
        <div id="fieldsGuru" style="display:none;">
            <div class="form-group">
                <label class="form-label">Kode Guru</label>
                <input type="text" name="kode" class="form-control" placeholder="G-01" maxlength="10">
                <small style="color:var(--text-muted);font-size:0.75rem;">Maksimal 10 karakter, harus unik.</small>
            </div>
            <div class="form-group">
                <label class="form-label">NIP</label>
                <input type="text" name="nip" class="form-control" placeholder="NIP guru">
            </div>
        </div>

        <div class="form-group" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="aktif" id="aktifCheck" checked style="width:auto;">
            <label for="aktifCheck" class="form-label" style="margin:0;">Akun Aktif</label>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:10px;">
            <i class="fas fa-save"></i> Simpan User
        </button>
    </form>
</div>

<?php $extraScript = "
<script>
function toggleRoleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('fieldsSiswa').style.display = role === 'siswa' ? 'block' : 'none';
    document.getElementById('fieldsGuru').style.display  = role === 'guru'  ? 'block' : 'none';
}
toggleRoleFields();
</script>
";
include __DIR__ . '/../../includes/footer.php'; ?>
