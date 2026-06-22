# MOPI - Mobile Praktik Kerja Lapangan (PKL)

MOPI adalah aplikasi berbasis web dengan tampilan *mobile-first* yang dirancang untuk memudahkan manajemen Praktik Kerja Lapangan (PKL). Aplikasi ini menghubungkan 3 peran utama: **Siswa**, **Guru Pembimbing**, dan **Administrator Sekolah** dengan antarmuka modern yang terintegrasi.

## ✨ Fitur Utama (Berdasarkan Role)

### 🧑‍🎓 Siswa
- **Presensi GPS (Masuk & Pulang)**: Dua tombol terpisah — **Masuk** dan **Pulang** — menggunakan peta Leaflet interaktif untuk verifikasi lokasi secara real-time.
- **WFA (Work From Anywhere)**: Siswa dapat mencentang opsi WFA jika tidak berada di lokasi PKL, dengan mengisi alasan yang wajib diisi.
- **Validasi Radius**: Presensi hanya bisa dilakukan jika berada dalam radius yang ditentukan oleh admin. WFA mengabaikan radius.
- **Validasi Jam**: Presensi Masuk dan Pulang hanya bisa dilakukan dalam rentang jam yang diatur admin.
- **Jurnal Harian**: Input kegiatan harian yang dikirim langsung ke Guru Pembimbing.
- **Dashboard & Progres**: Pantau statistik kehadiran dan jumlah hari efektif PKL.

### 👨‍🏫 Guru Pembimbing
- **Dashboard Monitoring**: Pantau seluruh siswa bimbingan dalam satu layar, yang otomatis difilter berdasarkan **Tahun PKL Aktif** saat ini.
- **Validasi Jurnal**: Melihat, menolak, atau memvalidasi jurnal harian siswa bimbingan dengan catatan feedback.
- **Rekap Laporan**: Lihat dan ekspor rekap presensi dan jurnal seluruh siswa bimbingan ke format Excel.
- **Peta Lokasi PKL**: Pantau titik absensi tempat PKL siswa bimbingan.

### 🛡️ Administrator
- **Dashboard Statistik**: Pantau total kehadiran, jumlah siswa, guru, dan jurnal pending.
- **Kelola Pengguna (CRUD)**: Manajemen data Siswa, Guru, dan Admin.
- **Import Massal Excel (XLSX)**: Import data ratusan Siswa dan Guru dalam sekali klik.
- **Manajemen Tempat PKL**: Data DU/DI dan titik koordinat GPS tersentralisasi dengan radius absensi yang dapat dikustomisasi. Tersedia juga fitur **Export Excel** untuk mengunduh seluruh data tempat PKL beserta ID-nya.
- **Filter Tahun Aktif**: Jumlah siswa pada masing-masing tempat PKL otomatis disesuaikan dengan **Tahun PKL Aktif** sekolah.
- **Rekap Laporan**: Lihat dan ekspor rekap presensi dan jurnal **semua siswa** dengan filter berdasarkan jurusan ke format Excel.
- **Pengaturan Jam Presensi**: Atur rentang jam Masuk dan rentang jam Pulang yang berlaku global untuk seluruh siswa.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP (Native)
- **Database**: MySQL / MariaDB
- **Library Tambahan**:
  - `PhpSpreadsheet` (Import & Export Excel/XLSX)
  - `Leaflet.js` & `OpenStreetMap` (Peta Interaktif tanpa API Key)
- **Frontend**: Vanilla CSS (Modern UI), JS, FontAwesome 6, Google Fonts

---

## ⚙️ Persyaratan Sistem

- **PHP Version**: Minimal PHP 7.4 (Direkomendasikan PHP 8.0+)
- **Ekstensi PHP Wajib**: `pdo_mysql`, `gd`, `zip`, `xml`
- **Pengaturan `php.ini`**: `upload_max_filesize` dan `post_max_size` disarankan minimal `20M`.

---

## 🚀 Panduan Instalasi & Deploy

### 1. Download & Persiapan Library
```bash
cd d:\htdocs\magang
composer install
```

### 2. Persiapan Database
1. Buat database baru bernama `magang` di MySQL.
2. Import file `database.sql` ke dalam database tersebut.
3. **Jalankan migrasi** untuk menambahkan kolom terbaru:
   ```
   http://localhost/magang/migrate.php
   ```

### 3. Konfigurasi Aplikasi
Copy `.env.example` menjadi `.env` dan sesuaikan isinya:
```env
APP_NAME="MOPI PKL"
APP_URL="https://magang.domain-anda.com"
SCHOOL_NAME="SMK Negeri 1 Kota Contoh"
SCHOOL_ID=1

DB_HOST="localhost"
DB_USER="root"
DB_PASS="password_database_anda"
DB_NAME="magang"
```

### 4. Konfigurasi Web Server (Routing)
**Apache (XAMPP, cPanel):** Konfigurasi sudah ada di `.htaccess`. Sesuaikan `RewriteBase` jika dipasang di subfolder.

**NGINX:** Tambahkan pada konfigurasi VHost:
```nginx
location / {
    try_files $uri $uri/ /index.php?route=$uri&$args;
}
```

---

## 🔑 Akun Login (Demo)

| Role | Nama | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Administrator | `admin@mopi.id` | `password` |
| **Guru** | Pak Budi Setiawan | `guru@mopi.id` | `password` |
| **Guru** | Bu Sari Lestari | `guru2@mopi.id` | `password` |
| **Siswa** | Andi Pratama | `andi@mopi.id` | `password` |
| **Siswa** | Budi Santoso | `budi@mopi.id` | `password` |

---

## 📁 Struktur Folder

```text
/magang
├── assets/             # File statis (CSS, JS, Images)
├── config/             # Konfigurasi database (database.php)
├── docs/               # Panduan pengguna (Admin, Guru, Siswa)
├── includes/           # Komponen reusable (Header, Footer, Functions)
├── pages/              # Halaman fitur (admin/, guru/, siswa/)
├── vendor/             # Hasil instalasi Composer (PhpSpreadsheet)
├── uploads/            # Penyimpanan file (foto jurnal & presensi)
├── database.sql        # Skema database & data dummy
├── migrate.php         # Script migrasi database (update kolom baru)
├── index.php           # Entry Point (Routing & Login)
├── .env                # File Konfigurasi (Database, URL, Identitas)
└── .htaccess           # Routing Server (Apache)
```

---

Dibuat untuk mempermudah administrasi dan monitoring Praktik Kerja Lapangan (PKL) Siswa secara modern dan terintegrasi.
