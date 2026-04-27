# MOPI - Mobile Praktik Kerja Lapangan (PKL)

MOPI adalah aplikasi berbasis web dengan tampilan *mobile-first* yang dirancang untuk memudahkan manajemen Praktik Kerja Lapangan (PKL). Aplikasi ini menghubungkan 3 peran utama: **Siswa**, **Guru Pembimbing**, dan **Administrator Sekolah** dengan antarmuka modern yang terintegrasi.

## ✨ Fitur Utama (Berdasarkan Role)

### 🧑‍🎓 Siswa
- **Presensi GPS**: Check-in dan check-out menggunakan peta Leaflet interaktif.
- **Jurnal Harian**: Input kegiatan harian yang dikirim langsung ke Guru Pembimbing.
- **Dashboard & Progres**: Pantau statistik kehadiran dan jumlah hari efektif PKL.
- **Portofolio**: Unggah dan kelola hasil kerja/dokumentasi selama di lapangan.

### 👨‍🏫 Guru Pembimbing
- **Dashboard Monitoring**: Pantau seluruh siswa bimbingan dalam satu layar (jumlah hadir, jurnal pending).
- **Validasi Jurnal**: Kemudahan melihat, menolak, atau memvalidasi jurnal harian siswa bimbingan (disertai catatan feedback).
- **Peta Lokasi PKL**: Set titik absensi tempat PKL (Latitude, Longitude) menggunakan peta Leaflet (via drag marker / lokasi perangkat) agar siswa bisa presensi secara akurat.

### 🛡️ Administrator
- **Dashboard Statistik**: Pantau total kehadiran, jumlah siswa, dan notifikasi sistem.
- **Kelola Pengguna (CRUD)**: Manajemen data Siswa, Guru, dan Admin.
- **Import Massal Excel (XLSX)**: Import data ratusan Siswa dan Guru dalam sekali klik dengan format `.xlsx`.
- **Manajemen Tempat PKL**: Data DU/DI dan titik kordinat maps tersentralisasi.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP (Native)
- **Database**: MySQL / MariaDB
- **Library Tambahan**: 
  - `PhpSpreadsheet` (Import Excel/XLSX)
  - `Leaflet.js` & `OpenStreetMap` (Gratis, Interaktif Map tanpa API Key)
- **Frontend**: Vanilla CSS (Modern UI), JS, FontAwesome 6, Google Fonts

---

## ⚙️ Persyaratan Sistem (System Requirements)

Untuk memastikan aplikasi berjalan dengan sempurna, pastikan server atau *hosting* Anda telah memenuhi persyaratan berikut:

- **PHP Version**: Minimal PHP 7.4 (Sangat direkomendasikan PHP 8.0 atau lebih baru)
- **Ekstensi PHP yang Wajib Diaktifkan**:
  - `pdo_mysql` : Untuk koneksi dan interaksi database yang aman.
  - `gd` : Wajib aktif untuk fitur *image processing* (melakukan kompresi JPG dan reduksi dimensi otomatis sebesar 20% saat siswa mengunggah foto jurnal).
  - `zip`, `xml`, `gd` : Dibutuhkan oleh pustaka *PhpSpreadsheet* agar dapat membaca format Excel (`.xlsx`) saat fitur Import Data digunakan.
- **Pengaturan Server (`php.ini`)**:
  - `allow_url_fopen = On` : Wajib disetel *On* agar fungsi `file_get_contents()` dapat memverifikasi token API dari Google saat menggunakan fitur **Login with Google**.
  - `upload_max_filesize` dan `post_max_size` : Disarankan disetel ke angka yang memadai (misal: `20M`) untuk memfasilitasi unggahan file tugas, jurnal, dan logo sekolah.

---

## 🚀 Panduan Instalasi & Deploy

### 1. Download & Persiapan Library
Pastikan Anda sudah menginstal **Composer** di komputer/server Anda.
```bash
# Clone atau copy project ke folder server (misal: htdocs/magang)
cd d:\htdocs\magang

# Install dependency untuk fitur Import Excel
composer install
```

### 2. Persiapan Database
1. Buat database baru bernama `magang` di MySQL.
2. Import file `database.sql` yang ada di direktori project ke dalam database tersebut.
   *(File ini sudah berisi skema lengkap dan data dummy untuk testing)*

### 3. Konfigurasi Aplikasi
1. Duplikat/copy file `.env.example` menjadi `.env` di direktori utama (root).
2. Sesuaikan nilai konfigurasi di dalam file `.env`:
   ```env
   APP_NAME="MOPI PKL"
   APP_URL="https://magang.domain-anda.com" # Wajib disesuaikan dengan URL asli aplikasi Anda
   SCHOOL_NAME="SMK Negeri 1 Kota Contoh"
   SCHOOL_ID=1

   DB_HOST="localhost"
   DB_USER="root"
   DB_PASS="password_database_anda"
   DB_NAME="magang"
   ```

### 4. Konfigurasi Web Server (Routing)
Aplikasi ini menggunakan sistem *Single Entry Point* (seluruh rute masuk melalui `index.php`).

**Untuk Pengguna Apache (cPanel, XAMPP, dll):**
- Konfigurasi sudah tersedia pada file `.htaccess`.
- Jika Anda menaruh aplikasi di **Root Domain** (misal: `https://magang.domain.com`), atur `RewriteBase /` di dalam `.htaccess`.
- Jika ditaruh di **Sub-Folder** (misal: `https://domain.com/magang`), atur `RewriteBase /magang/` di `.htaccess`.

**Untuk Pengguna NGINX (CloudPanel, VPS, dll):**
- Server NGINX mengabaikan file `.htaccess`. Anda perlu mengedit blok konfigurasi *VHost* aplikasi Anda.
- Tambahkan kode berikut pada bagian `location /`:
  ```nginx
  location / {
      try_files $uri $uri/ /index.php?route=$uri&$args;
  }
  ```

---

## 🔑 Akun Login (Demo)

Gunakan akun berikut untuk mencoba fitur-fitur di aplikasi (semua password default adalah `password`):

| Role | Nama | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Administrator | `admin@mopi.id` | `password` |
| **Guru** | Pak Budi Setiawan | `guru@mopi.id` | `password` |
| **Guru** | Bu Sari Lestari | `guru2@mopi.id` | `password` |
| **Siswa** | Andi Pratama | `andi@mopi.id` | `password` |
| **Siswa** | Budi Santoso | `budi@mopi.id` | `password` |

*(Catatan: Andi dan Budi adalah siswa bimbingan dari Pak Budi. Silakan login sebagai Andi untuk mengirim jurnal, lalu login sebagai Pak Budi untuk memvalidasi jurnal tersebut).*

---

## 📁 Struktur Folder

```text
/magang
├── assets/             # File statis (CSS, JS, Images)
├── config/             # Konfigurasi database (database.php)
├── includes/           # Komponen reusable (Header, Footer, Functions)
├── pages/              # Halaman fitur aplikasi (dipisah berdasarkan folder admin/guru/siswa)
├── vendor/             # Folder hasil instalasi composer (PhpSpreadsheet)
├── uploads/            # Penyimpanan file (foto jurnal & presensi)
├── database.sql        # Skema database & data dummy (siap pakai)
├── index.php           # Entry Point (Routing & Login)
├── .env                # File Konfigurasi (Database, URL, Identitas)
└── .htaccess           # Routing Server (Apache)
```

---
Dibuat untuk mempermudah administrasi dan monitoring Praktik Kerja Lapangan (PKL) Siswa secara modern dan terintegrasi.
