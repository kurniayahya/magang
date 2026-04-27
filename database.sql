-- ============================================
-- MOPI - Mobile PKL Application Database
-- Versi: 2.0 (Updated setelah perombakan fitur)
-- ============================================
-- Perubahan dari v1:
--   - Role 'pembimbing_industri' dihapus dari users (tidak digunakan di app)
--   - Demo data: user pembimbing_industri dihapus, diganti guru ke-2
--   - Jurnal demo status 'terkirim' agar bisa dicoba validasi oleh guru
--   - Tambah 2 guru demo dengan masing-masing siswa bimbingan
--   - Tambah SET FOREIGN_KEY_CHECKS & DROP TABLE untuk re-import bersih
-- ============================================

-- Gunakan database yang sesuai (sesuaikan dengan DB_NAME di config/database.php)
CREATE DATABASE IF NOT EXISTS `magang` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `magang`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS pengaturan_notifikasi;
DROP TABLE IF EXISTS portofolio;
DROP TABLE IF EXISTS notifikasi;
DROP TABLE IF EXISTS chat;
DROP TABLE IF EXISTS penilaian;
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS jurnal_foto;
DROP TABLE IF EXISTS jurnal;
DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS guru;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS tempat_pkl;
DROP TABLE IF EXISTS jurusan;
DROP TABLE IF EXISTS sekolah;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SCHEMA
-- ============================================

-- Tabel Sekolah
-- Digunakan sebagai referensi internal. Nama sekolah dikonfigurasi
-- via konstanta SCHOOL_NAME di config/database.php untuk tampilan UI.
-- SCHOOL_ID di config/database.php harus sesuai dengan id di tabel ini.
CREATE TABLE sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(200) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Jurusan
CREATE TABLE jurusan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kode VARCHAR(20),
    sekolah_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sekolah_id) REFERENCES sekolah(id)
);

-- Tabel Tempat PKL (DU/DI)
CREATE TABLE tempat_pkl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(200) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    radius_meter INT DEFAULT 100,
    nama_pembimbing VARCHAR(100),
    bidang_usaha VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Users
-- Role yang aktif digunakan: siswa, guru, admin
-- (pembimbing_industri dihapus karena tidak ada dashboard/route-nya)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa','guru','admin') DEFAULT 'siswa',
    foto VARCHAR(255),
    telepon VARCHAR(20),
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Tabel Siswa (extends users)
-- sekolah_id diisi otomatis dari konstanta SCHOOL_ID (config/database.php),
-- tidak lagi diinput manual di form maupun file import.
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nis VARCHAR(20) UNIQUE,
    kelas VARCHAR(30),
    jurusan_id INT,
    sekolah_id INT,
    tempat_pkl_id INT,
    guru_pembimbing_id INT,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    total_hari_pkl INT DEFAULT 90,
    status_pkl ENUM('aktif','selesai','tidak_aktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jurusan_id) REFERENCES jurusan(id),
    FOREIGN KEY (sekolah_id) REFERENCES sekolah(id),
    FOREIGN KEY (tempat_pkl_id) REFERENCES tempat_pkl(id),
    FOREIGN KEY (guru_pembimbing_id) REFERENCES users(id)
);

-- Tabel Guru (extends users)
-- sekolah_id diisi otomatis dari SCHOOL_ID, tidak diinput manual.
CREATE TABLE guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nip VARCHAR(30),
    sekolah_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sekolah_id) REFERENCES sekolah(id)
);

-- Tabel Presensi
CREATE TABLE presensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_keluar TIME,
    lat_masuk DECIMAL(10, 8),
    lng_masuk DECIMAL(11, 8),
    lat_keluar DECIMAL(10, 8),
    lng_keluar DECIMAL(11, 8),
    status ENUM('hadir','izin','sakit','alpha') DEFAULT 'hadir',
    keterangan TEXT,
    foto_masuk VARCHAR(255),
    foto_keluar VARCHAR(255),
    validasi ENUM('pending','valid','tidak_valid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    UNIQUE KEY unique_presensi (siswa_id, tanggal)
);

-- Tabel Jurnal Harian
-- validasi_oleh merujuk ke users.id (guru yang memvalidasi)
CREATE TABLE jurnal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    hari_ke INT,
    kegiatan TEXT NOT NULL,
    deskripsi TEXT,
    hasil TEXT,
    kendala TEXT,
    status ENUM('draft','terkirim','divalidasi','ditolak') DEFAULT 'draft',
    validasi_oleh INT NULL,
    catatan_validator TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (validasi_oleh) REFERENCES users(id)
);

-- Tabel Foto Jurnal
CREATE TABLE jurnal_foto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurnal_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    deskripsi VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jurnal_id) REFERENCES jurnal(id) ON DELETE CASCADE
);

-- Tabel Laporan PKL
CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    periode_mulai DATE,
    periode_selesai DATE,
    file_pdf VARCHAR(255),
    status ENUM('draft','dikirim','divalidasi','ditolak') DEFAULT 'draft',
    catatan TEXT,
    validasi_oleh INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (validasi_oleh) REFERENCES users(id)
);

-- Tabel Penilaian
-- tipe_penilai: hanya 'guru' yang aktif digunakan saat ini
CREATE TABLE penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    penilai_id INT NOT NULL,
    tipe_penilai ENUM('guru') NOT NULL,
    aspek_kedisiplinan INT DEFAULT 0,
    aspek_kejujuran INT DEFAULT 0,
    aspek_kerjasama INT DEFAULT 0,
    aspek_inisiatif INT DEFAULT 0,
    aspek_keterampilan INT DEFAULT 0,
    aspek_pengetahuan INT DEFAULT 0,
    nilai_akhir DECIMAL(5,2),
    komentar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (penilai_id) REFERENCES users(id)
);

-- Tabel Chat / Pesan
CREATE TABLE chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dari_user_id INT NOT NULL,
    ke_user_id INT NOT NULL,
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dari_user_id) REFERENCES users(id),
    FOREIGN KEY (ke_user_id) REFERENCES users(id)
);

-- Tabel Notifikasi
CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('info','warning','success','danger') DEFAULT 'info',
    dibaca TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Portofolio
CREATE TABLE portofolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    kategori VARCHAR(50),
    file VARCHAR(255),
    thumbnail VARCHAR(255),
    tampilkan TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE
);

-- Tabel Pengaturan Notifikasi User
CREATE TABLE pengaturan_notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    notif_checkin TINYINT(1) DEFAULT 1,
    notif_jurnal TINYINT(1) DEFAULT 1,
    notif_laporan TINYINT(1) DEFAULT 1,
    notif_info TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- DATA DUMMY / SEED
-- ============================================

-- Sekolah (id=1 harus sesuai dengan SCHOOL_ID di config/database.php)
INSERT INTO sekolah (nama, alamat, telepon) VALUES
('SMK Negeri 1 Kota Contoh', 'Jl. Pendidikan No. 1, Kota Contoh', '0211234567');

-- Jurusan
INSERT INTO jurusan (nama, kode, sekolah_id) VALUES
('Teknik Kendaraan Ringan',    'TKR', 1),
('Rekayasa Perangkat Lunak',   'RPL', 1),
('Teknik Komputer Jaringan',   'TKJ', 1),
('Akuntansi Keuangan Lembaga', 'AKL', 1);

-- Tempat PKL (dengan koordinat GPS lengkap)
INSERT INTO tempat_pkl (nama, alamat, telepon, email, latitude, longitude, radius_meter, nama_pembimbing, bidang_usaha) VALUES
('Bengkel Jaya Auto',          'Jl. Raya Bengkel No. 15, Kota Contoh',  '0219876543', 'bengkel.jaya@email.com',   -6.200000, 106.816666, 150, 'Bapak Jaya Santoso', 'Otomotif'),
('PT. Solusi Digital Indonesia','Jl. Teknologi No. 88, Kota Contoh',    '0218765432', 'info@solusidigital.id',    -6.210000, 106.820000, 100, 'Ibu Dian Lestari',   'Teknologi Informasi'),
('Koperasi Simpan Pinjam Makmur','Jl. Koperasi No. 5, Kota Contoh',    '0217654321', 'kspm@email.com',           -6.195000, 106.812000, 100, 'Bapak Ahmad Yusuf',  'Keuangan');

-- ============================================
-- Users (password default: "password")
-- Hash bcrypt dari string "password"
-- ============================================
INSERT INTO users (nama, email, password, role) VALUES
-- id=1 Admin
('Administrator',   'admin@mopi.id',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
-- id=2,3,4 Siswa
('Andi Pratama',    'andi@mopi.id',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa'),
('Budi Santoso',    'budi@mopi.id',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa'),
('Siti Rahayu',     'siti@mopi.id',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa'),
-- id=5,6 Guru
('Pak Budi Setiawan','guru@mopi.id',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru'),
('Bu Sari Lestari', 'guru2@mopi.id',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru');
-- Password default: password

-- Guru (sekolah_id otomatis dari SCHOOL_ID=1)
INSERT INTO guru (user_id, nip, sekolah_id) VALUES
(5, '19800101200001001', 1),
(6, '19850215200002002', 1);

-- Siswa (sekolah_id otomatis dari SCHOOL_ID=1, guru_pembimbing_id = users.id guru)
-- Andi & Budi dibimbing Pak Budi (id=5), Siti dibimbing Bu Sari (id=6)
INSERT INTO siswa (user_id, nis, kelas, jurusan_id, sekolah_id, tempat_pkl_id, guru_pembimbing_id, tanggal_mulai, tanggal_selesai, total_hari_pkl, status_pkl) VALUES
(2, '12345', 'XII TKR 1', 1, 1, 1, 5, '2026-01-06', '2026-04-04', 90, 'aktif'),
(3, '12346', 'XII RPL 1', 2, 1, 2, 5, '2026-01-06', '2026-04-04', 90, 'aktif'),
(4, '12347', 'XII AKL 1', 4, 1, 3, 6, '2026-01-06', '2026-04-04', 90, 'aktif');

-- Presensi dummy (siswa_id=1 = Andi, siswa_id=2 = Budi)
INSERT INTO presensi (siswa_id, tanggal, jam_masuk, jam_keluar, lat_masuk, lng_masuk, status, validasi) VALUES
(1, CURDATE() - INTERVAL 4 DAY, '07:30:00', '16:00:00', -6.200010, 106.816670, 'hadir', 'valid'),
(1, CURDATE() - INTERVAL 3 DAY, '07:25:00', '16:05:00', -6.200010, 106.816670, 'hadir', 'valid'),
(1, CURDATE() - INTERVAL 2 DAY, '07:45:00', '16:00:00', -6.200010, 106.816670, 'hadir', 'valid'),
(1, CURDATE() - INTERVAL 1 DAY, '07:30:00', '16:00:00', -6.200010, 106.816670, 'hadir', 'valid'),
(2, CURDATE() - INTERVAL 1 DAY, '07:50:00', '16:00:00', -6.210010, 106.820010, 'hadir', 'valid');

-- Jurnal dummy
-- Status 'terkirim' = bisa dicoba divalidasi oleh guru di halaman guru/validasi
-- Status 'divalidasi' = sudah divalidasi
INSERT INTO jurnal (siswa_id, tanggal, hari_ke, kegiatan, deskripsi, status, validasi_oleh) VALUES
(1, CURDATE() - INTERVAL 4 DAY, 7,  'Servis Motor Rutin',         'Melakukan pemeriksaan dan penggantian oli mesin sepeda motor milik pelanggan',       'divalidasi', 5),
(1, CURDATE() - INTERVAL 3 DAY, 8,  'Ganti Ban & Cek Rem',        'Mengganti ban depan dan belakang, melakukan pemeriksaan sistem pengereman',          'divalidasi', 5),
(1, CURDATE() - INTERVAL 2 DAY, 9,  'Meeting dengan Tim',         'Mengikuti briefing pagi bersama seluruh mekanik bengkel',                            'terkirim',   NULL),
(1, CURDATE() - INTERVAL 1 DAY, 10, 'Servis Motor & Upload Laporan','Mengecek & mengganti oli motor, sekaligus menyiapkan laporan mingguan',            'terkirim',   NULL),
(2, CURDATE() - INTERVAL 1 DAY, 7,  'Setup Server Development',   'Membantu tim IT mengkonfigurasi server development untuk proyek baru perusahaan',     'terkirim',   NULL),
(3, CURDATE() - INTERVAL 1 DAY, 7,  'Rekap Laporan Keuangan',     'Membantu staf keuangan merekap transaksi bulanan dan menyusun laporan neraca',       'terkirim',   NULL);

-- Notifikasi dummy (untuk Andi, user_id=2)
INSERT INTO notifikasi (user_id, judul, pesan, tipe, dibaca) VALUES
(2, 'Pengingat Check-In',       'Jangan lupa check-in hari ini sebelum jam 08.00!',                              'warning', 0),
(2, 'Jurnal Divalidasi',        'Jurnal harian tanggal kemarin telah divalidasi oleh guru pembimbing.',          'success', 0),
(2, 'Info dari Sekolah',        'Rapat orang tua siswa PKL akan dilaksanakan pada bulan depan.',                 'info',    1),
(2, 'Laporan Perlu Dilengkapi', 'Harap segera melengkapi laporan PKL minggu ke-2.',                              'warning', 0);

-- Penilaian dummy (hanya oleh guru)
INSERT INTO penilaian (siswa_id, penilai_id, tipe_penilai, aspek_kedisiplinan, aspek_kejujuran, aspek_kerjasama, aspek_inisiatif, aspek_keterampilan, aspek_pengetahuan, nilai_akhir, komentar) VALUES
(1, 5, 'guru', 85, 90, 88, 82, 87, 84, 86.00, 'Siswa menunjukkan perkembangan yang baik selama PKL');

-- Portofolio dummy (Andi)
INSERT INTO portofolio (siswa_id, judul, deskripsi, kategori, tampilkan) VALUES
(1, 'Servis Mesin Motor Honda',     'Dokumentasi proses servis mesin motor Honda Beat milik pelanggan',          'Otomotif', 1),
(1, 'Ganti Oli Massal 20 Unit',     'Kegiatan ganti oli serentak untuk armada ojek online',                      'Otomotif', 1),
(1, 'Laporan Minggu Pertama PKL',   'Rekap kegiatan dan pembelajaran selama minggu pertama di bengkel',          'Laporan',  1);

-- Pengaturan notifikasi default untuk semua siswa & guru
INSERT INTO pengaturan_notifikasi (user_id, notif_checkin, notif_jurnal, notif_laporan, notif_info) VALUES
(2, 1, 1, 1, 1),
(3, 1, 1, 1, 0),
(4, 1, 1, 0, 1),
(5, 1, 1, 1, 1),
(6, 1, 1, 1, 1);
