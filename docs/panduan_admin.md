# Panduan Penggunaan MOPI — Administrator

## 1. Login
- Buka aplikasi MOPI di browser.
- Masukkan Email dan Password admin (default: `admin@mopi.id` / `password`).
- Klik tombol **Login**.

---

## 2. Dashboard
Setelah login, Anda akan melihat ringkasan:
- **Total Siswa** — Jumlah seluruh siswa terdaftar.
- **Total Guru** — Jumlah guru aktif.
- **Hadir Hari Ini** — Siswa yang sudah presensi hari ini.
- **Jurnal Pending** — Jurnal yang belum divalidasi guru.

---

## 3. Kelola Pengguna (Users)
Menu **Kelola User** digunakan untuk manajemen akun.
- **Tambah User**: Klik tombol "Tambah User", pilih Role (Siswa/Guru/Admin), lengkapi data (Nama, Email, NIS/Kode, Jurusan, Tempat PKL, Guru Pembimbing, dsb).
- **Reset Password**: Klik ikon 🔑 di samping user untuk mereset password ke `password`.
- **Hapus User**: Klik ikon 🗑️ untuk menghapus akun.
- Gunakan **filter Role** dan **pencarian** untuk menemukan user dengan cepat.

---

## 4. Import Data Massal (Excel)
Untuk mempercepat input data banyak siswa/guru sekaligus:
1. Masuk ke menu **Import**.
2. Pilih tab **Siswa**, **Guru**, atau **Tempat PKL**.
3. Klik **Download Template** untuk mendapatkan format yang benar.
4. Isi file Excel sesuai format, lalu unggah kembali dan klik **Import Sekarang**.

---

## 5. Kelola Tempat PKL
Menu **Tempat PKL** untuk mengelola lokasi DU/DI.
- Klik **Tambah Tempat PKL** dan isi data instansi/perusahaan.
- Tentukan titik lokasi GPS menggunakan **peta interaktif** — seret marker atau klik lokasi di peta.
- Atur **Radius (meter)**: siswa hanya bisa presensi jika berada dalam radius ini (kecuali menggunakan WFA).

---

## 6. Pengaturan Jam Presensi *(Fitur Baru)*
Menu **Pengaturan** digunakan untuk mengatur rentang jam presensi yang berlaku global.
- **Sesi Masuk**: Atur jam mulai dan jam selesai presensi Masuk (contoh: 06:00 — 09:00).
- **Sesi Pulang**: Atur jam mulai dan jam selesai presensi Pulang (contoh: 15:00 — 18:00).
- Siswa **tidak dapat** melakukan presensi di luar rentang jam ini.
- Format jam menggunakan **24 jam** (HH:MM).

---

## 7. Rekap Laporan & Export Excel *(Fitur Baru)*
Menu **Rekap Laporan** menampilkan seluruh data presensi dan jurnal semua siswa.
- **Filter Jurusan**: Pilih jurusan tertentu untuk mempersempit tampilan data.
- **Filter Tanggal**: Atur rentang tanggal yang ingin ditampilkan.
- Klik tombol **Export Excel** untuk mengunduh laporan dalam format `.xlsx`.

---

## 8. Profil
- Ubah nama, email, dan password pada menu **Profil**.
- Jaga kerahasiaan password Anda.
