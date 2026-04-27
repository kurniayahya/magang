# MOPI - Mobile Praktik Kerja Lapangan (PKL)

MOPI adalah aplikasi berbasis web dengan tampilan *mobile-first* yang dirancang khusus untuk memudahkan siswa dalam mengelola aktivitas Praktik Kerja Lapangan (PKL). Aplikasi ini memungkinkan siswa untuk melakukan presensi berbasis lokasi, mengisi jurnal harian, dan memantau progres PKL mereka secara real-time.

## ✨ Fitur Utama

-   🏠 **Dashboard Informatif**: Ringkasan hari ke-berapa PKL, statistik harian, dan shortcut menu penting.
-   📍 **Presensi GPS**: Check-in dan check-out harian dengan validasi lokasi (GPS) untuk mencegah manipulasi.
-   📝 **Jurnal Harian**: Input kegiatan harian lengkap dengan deskripsi dan fitur upload dokumentasi (foto/video).
-   📊 **Progres PKL**: Visualisasi progres masa PKL melalui progress bar yang dinamis.
-   🧑🎓 **Profil & Info PKL**: Informasi detail mengenai sekolah, jurusan, guru pembimbing, serta profil tempat PKL (DU/DI).
-   📱 **Mobile-First Design**: Tampilan yang dioptimalkan untuk perangkat smartphone dengan estetika premium.

## 🛠️ Teknologi yang Digunakan

-   **Bahasa Pemrograman**: PHP (Native)
-   **Database**: MySQL / MariaDB
-   **Styling**: Vanilla CSS (Modern Design System)
-   **Icons & Fonts**: FontAwesome 6, Google Fonts (Poppins)
-   **Interaktivitas**: Vanilla JavaScript

## 🚀 Panduan Instalasi

### 1. Persiapan Database
- Buat database baru dengan nama `mopi_pkl` di MySQL Anda.
- Import file `database.sql` yang tersedia di direktori root project.
  ```bash
  mysql -u root -p mopi_pkl < database.sql
  ```

### 2. Konfigurasi Aplikasi
- Buka file `config/database.php`.
- Sesuaikan konfigurasi database sesuai dengan lingkungan server Anda:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_NAME', 'mopi_pkl');
  ```

### 3. Menjalankan Aplikasi
- Letakkan folder project di dalam direktori web server Anda (misal: `htdocs` untuk XAMPP).
- Akses melalui browser: `http://localhost/magang/`

## 🔑 Akun Login (Demo)

Gunakan akun berikut untuk mencoba aplikasi:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Siswa** | `andi@mopi.id` | `password` |
| **Admin** | `admin@mopi.id` | `password` |

## 📁 Struktur Folder

```text
/magang
├── assets/             # File statis (CSS, JS, Images)
├── config/             # Konfigurasi database & aplikasi
├── includes/           # Komponen reusable (Header, Footer, Functions)
├── pages/              # Halaman fitur utama aplikasi
├── uploads/            # Direktori penyimpanan file upload (foto jurnal)
├── database.sql        # Skema database & data dummy
├── index.php           # Halaman Login (Entry Point)
└── logout.php          # Script Logout
```

---
Dibuat dengan ❤️ untuk kemudahan administrasi PKL Siswa.
