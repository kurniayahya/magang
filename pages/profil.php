<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$user = getCurrentUser();

if (!$user) {
    session_destroy();
    header('Location: ' . APP_URL);
    exit;
}

$db = getDB();

// --- Handle Change Password (Semua Role) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verify_csrf();
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $currentUser = $stmt->fetch();

    if (!password_verify($password_lama, $currentUser['password'])) {
        $error = "Password lama tidak sesuai.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $new_hash = password_hash($password_baru, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user['id']]);
        header("Location: " . APP_URL . "/profil?success=2");
        exit;
    }
}

// --- Handle Form Submission untuk Admin ---
if ($user['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama_sekolah = sanitize($_POST['nama_sekolah'] ?? '');
    $alamat_sekolah = sanitize($_POST['alamat_sekolah'] ?? '');
    $telepon_sekolah = sanitize($_POST['telepon_sekolah'] ?? '');
    $tahun_aktif = sanitize($_POST['tahun_aktif'] ?? '2024');

    // Handle File Upload
    $logo_sql = "";
    $params = [$nama_sekolah, $alamat_sekolah, $telepon_sekolah, $tahun_aktif];

    if (!empty($_FILES['logo_sekolah']['name'])) {
        $uploadDir = UPLOAD_PATH;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['logo_sekolah']['name']);
        $targetFile = $uploadDir . $fileName;
        
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['logo_sekolah']['tmp_name'], $targetFile)) {
                $logo_sql = ", logo = ?";
                $params[] = $fileName;
            }
        }
    }
    
    $params[] = SCHOOL_ID;

    // Update table sekolah
    $stmt = $db->prepare("UPDATE sekolah SET nama = ?, alamat = ?, telepon = ?, tahun_aktif = ? {$logo_sql} WHERE id = ?");
    $stmt->execute($params);

    // Update .env to reflect the new SCHOOL_NAME constant
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile) && is_writable($envFile)) {
        $content = file_get_contents($envFile);
        if (preg_match("/^SCHOOL_NAME=.*$/m", $content)) {
            $content = preg_replace("/^SCHOOL_NAME=.*$/m", 'SCHOOL_NAME="' . addslashes($nama_sekolah) . '"', $content);
        } else {
            $content .= "\nSCHOOL_NAME=\"" . addslashes($nama_sekolah) . "\"";
        }
        file_put_contents($envFile, $content);
    }

    header("Location: " . APP_URL . "/profil?success=1");
    exit;
}

if ($user['role'] === 'siswa') {
    $siswa = getSiswaInfo($user['id']);
    if (!$siswa) {
        die("Error: Data siswa tidak ditemukan.");
    }
} else if ($user['role'] === 'guru') {
    $stmt = $db->prepare("SELECT g.nip, sk.nama as sekolah_nama FROM guru g LEFT JOIN sekolah sk ON g.sekolah_id = sk.id WHERE g.user_id = ?");
    $stmt->execute([$user['id']]);
    $guruInfo = $stmt->fetch();
} else if ($user['role'] === 'admin') {
    $stmt = $db->prepare("SELECT * FROM sekolah WHERE id = ?");
    $stmt->execute([SCHOOL_ID]);
    $sekolahInfo = $stmt->fetch();
}

$pageTitle = 'Profil ' . ucfirst($user['role']);
$showBack = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="animate-fade-in">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <i class="fas fa-check-circle"></i> <?= $_GET['success'] == '2' ? 'Password berhasil diubah!' : 'Berhasil memperbarui data!' ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card" style="text-align: center; padding-top: 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 100px; background: linear-gradient(135deg, var(--primary), var(--accent));"></div>
        <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; position: relative; z-index: 1; box-shadow: var(--shadow-md);">
        <h2 style="margin-top: 15px; font-size: 1.3rem;"><?= htmlspecialchars($user['nama']) ?></h2>
        
        <?php if ($user['role'] === 'siswa'): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">NIS: <?= htmlspecialchars($siswa['nis']) ?> • <?= htmlspecialchars($siswa['kelas']) ?></p>
        <?php elseif ($user['role'] === 'guru'): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">NIP: <?= htmlspecialchars($guruInfo['nip'] ?? '-') ?> • <?= htmlspecialchars($guruInfo['sekolah_nama'] ?? SCHOOL_NAME) ?></p>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">Administrator Sistem</p>
        <?php endif; ?>
        
    
    </div>

    <?php if ($user['role'] === 'siswa'): ?>
        <div class="card">
            <div class="card-title">Informasi Sekolah</div>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon"><i class="fas fa-school"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Asal Sekolah</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($siswa['sekolah_nama']) ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Jurusan</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($siswa['jurusan_nama']) ?> (<?= htmlspecialchars($siswa['jurusan_kode']) ?>)</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon"><i class="fas fa-user-tie"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Guru Pembimbing</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($siswa['nama_guru_pembimbing'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="border-left: 5px solid var(--accent);">
            <div class="card-title">Tempat PKL</div>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);"><i class="fas fa-building"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Nama Perusahaan</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($siswa['tempat_pkl_nama'] ?? '-') ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Alamat</div>
                        <div style="font-weight: 600; font-size: 0.85rem;"><?= htmlspecialchars($siswa['tempat_pkl_alamat'] ?? '-') ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);"><i class="fas fa-user-check"></i></div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Pembimbing Industri</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($siswa['nama_pembimbing_industri'] ?? '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'admin'): ?>
        <div class="card">
            <div class="card-title">Identitas Sekolah</div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label class="form-label">Nama Sekolah</label>
                    <input type="text" name="nama_sekolah" class="form-control" value="<?= htmlspecialchars($sekolahInfo['nama'] ?? '') ?>" required>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Ini akan merubah nama sekolah di seluruh aplikasi.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="alamat_sekolah" class="form-control" rows="3"><?= htmlspecialchars($sekolahInfo['alamat'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="telepon_sekolah" class="form-control" value="<?= htmlspecialchars($sekolahInfo['telepon'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tahun Aktif PKL</label>
                    <input type="number" name="tahun_aktif" class="form-control" value="<?= htmlspecialchars($sekolahInfo['tahun_aktif'] ?? '2024') ?>" min="2000" max="2100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Logo Sekolah</label>
                    <?php if (!empty($sekolahInfo['logo'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($sekolahInfo['logo']) ?>" alt="Logo Sekolah" style="max-height: 80px; border-radius: 8px; border: 1px solid var(--border);">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo_sekolah" class="form-control" accept="image/*">
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Kosongkan jika tidak ingin mengubah logo.</small>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    <i class="fas fa-save"></i> Simpan Identitas Sekolah
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($user['role'] === 'guru' || $user['role'] === 'admin'): ?>
        <div class="card" style="border-left: 5px solid var(--warning);">
            <div class="card-title">Ganti Password</div>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="password_lama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; background: linear-gradient(135deg, var(--warning), #D97706);">
                    <i class="fas fa-key"></i> Simpan Password Baru
                </button>
            </form>
        </div>
    <?php endif; ?>

    <a href="<?= APP_URL ?>/logout" class="btn" style="background: #FFF; color: var(--error); border: 1px solid #FEE2E2; margin-top: 10px;">
        <i class="fas fa-sign-out-alt"></i> Keluar Aplikasi
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
