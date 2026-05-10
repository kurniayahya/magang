<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Kebijakan Privasi';
include __DIR__ . '/../includes/header.php';
?>

<div class="privacy-container animate-fade-in" style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <div class="card" style="padding: 30px; line-height: 1.7; color: var(--text-dark);">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 60px; height: 60px; background: rgba(30,111,217,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                <i class="fas fa-shield-halved" style="font-size: 24px; color: var(--primary);"></i>
            </div>
            <h2 style="font-weight: 800; color: var(--primary); margin-bottom: 5px;">Kebijakan Privasi</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Terakhir diperbarui: <?= date('d F Y') ?></p>
        </div>

        <section style="margin-bottom: 25px;">
            <h3 style="font-weight: 600; color: var(--primary); border-bottom: 2px solid rgba(30,111,217,0.1); padding-bottom: 8px; margin-bottom: 15px;">1. Informasi yang Kami Kumpulkan</h3>
            <p>Aplikasi MOPI (Mobile Praktik Kerja Lapangan) mengumpulkan informasi untuk memberikan layanan yang lebih baik kepada siswa dan sekolah:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Data Profil:</strong> Nama lengkap, email, nomor induk siswa, dan foto profil.</li>
                <li><strong>Data Aktivitas:</strong> Jurnal harian, tugas, dan progres PKL yang Anda unggah.</li>
                <li><strong>Data Lokasi (GPS):</strong> Kami mengumpulkan data lokasi perangkat Anda saat Anda melakukan "Presensi Masuk" atau "Presensi Pulang" untuk memvalidasi kehadiran Anda di lokasi PKL yang telah ditentukan.</li>
            </ul>
        </section>

        <section style="margin-bottom: 25px;">
            <h3 style="font-weight: 600; color: var(--primary); border-bottom: 2px solid rgba(30,111,217,0.1); padding-bottom: 8px; margin-bottom: 15px;">2. Penggunaan Informasi</h3>
            <p>Kami menggunakan informasi yang dikumpulkan untuk:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Memverifikasi kehadiran siswa di tempat PKL melalui koordinat GPS.</li>
                <li>Menyusun laporan kegiatan PKL otomatis untuk sekolah dan guru pembimbing.</li>
                <li>Memfasilitasi komunikasi antara siswa, guru, dan admin sekolah.</li>
                <li>Meningkatkan fungsionalitas dan keamanan aplikasi.</li>
            </ul>
        </section>

        <section style="margin-bottom: 25px;">
            <h3 style="font-weight: 600; color: var(--primary); border-bottom: 2px solid rgba(30,111,217,0.1); padding-bottom: 8px; margin-bottom: 15px;">3. Keamanan Data</h3>
            <p>Keamanan data Anda adalah prioritas kami. Kami menerapkan langkah-langkah keamanan teknis dan organisasional untuk melindungi data Anda dari akses yang tidak sah, pengubahan, pengungkapan, atau penghancuran yang tidak semestinya.</p>
        </section>

        <section style="margin-bottom: 25px;">
            <h3 style="font-weight: 600; color: var(--primary); border-bottom: 2px solid rgba(30,111,217,0.1); padding-bottom: 8px; margin-bottom: 15px;">4. Berbagi Informasi</h3>
            <p>Data Anda hanya dapat diakses oleh:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Diri Anda sendiri.</li>
                <li>Guru Pembimbing yang ditugaskan kepada Anda.</li>
                <li>Administrator Sistem di sekolah Anda.</li>
            </ul>
            <p style="margin-top: 10px;">Kami tidak menjual atau membagikan data pribadi Anda kepada pihak ketiga di luar ekosistem sekolah tanpa izin Anda.</p>
        </section>

        <section style="margin-bottom: 25px;">
            <h3 style="font-weight: 600; color: var(--primary); border-bottom: 2px solid rgba(30,111,217,0.1); padding-bottom: 8px; margin-bottom: 15px;">5. Kontak Kami</h3>
            <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi Administrator Sekolah atau melalui menu bantuan di dalam aplikasi.</p>
        </section>

        <div style="text-align: center; margin-top: 40px;">
            <a href="<?= APP_URL ?>" class="btn btn-primary" style="display: inline-block; padding: 10px 25px; text-decoration: none;">Kembali ke Login</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
