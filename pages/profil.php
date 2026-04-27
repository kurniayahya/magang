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

$pageTitle = 'Profil Saya';
$showBack = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="animate-fade-in">
    <div class="card" style="text-align: center; padding-top: 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 100px; background: linear-gradient(135deg, var(--primary), var(--accent));"></div>
        <img src="<?= getAvatarUrl($user['foto'], $user['nama']) ?>" style="width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; position: relative; z-index: 1; box-shadow: var(--shadow-md);">
        <h2 style="margin-top: 15px; font-size: 1.3rem;"><?= $user['nama'] ?></h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">NIS: <?= $siswa['nis'] ?> • <?= $siswa['kelas'] ?></p>
        
        <div style="display: flex; gap: 10px; padding: 0 10px;">
            <button class="btn btn-primary" style="flex: 1; border-radius: 10px; font-size: 0.85rem;">
                <i class="fas fa-edit"></i> Edit Profil
            </button>
            <a href="<?= APP_URL ?>/pengaturan" class="btn btn-outline" style="flex: 1; border-radius: 10px; font-size: 0.85rem;">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Informasi Sekolah</div>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="activity-icon"><i class="fas fa-school"></i></div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Asal Sekolah</div>
                    <div style="font-weight: 600;"><?= $siswa['sekolah_nama'] ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="activity-icon"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Jurusan</div>
                    <div style="font-weight: 600;"><?= $siswa['jurusan_nama'] ?> (<?= $siswa['jurusan_kode'] ?>)</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="activity-icon"><i class="fas fa-user-tie"></i></div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Guru Pembimbing</div>
                    <div style="font-weight: 600;"><?= $siswa['nama_guru_pembimbing'] ?></div>
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
                    <div style="font-weight: 600;"><?= $siswa['tempat_pkl_nama'] ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Alamat</div>
                    <div style="font-weight: 600; font-size: 0.85rem;"><?= $siswa['tempat_pkl_alamat'] ?></div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="activity-icon" style="background: var(--primary-light); color: var(--primary);"><i class="fas fa-user-check"></i></div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Pembimbing Industri</div>
                    <div style="font-weight: 600;"><?= $siswa['nama_pembimbing_industri'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <a href="<?= APP_URL ?>/logout" class="btn" style="background: #FFF; color: var(--error); border: 1px solid #FEE2E2; margin-top: 10px;">
        <i class="fas fa-sign-out-alt"></i> Keluar Aplikasi
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
