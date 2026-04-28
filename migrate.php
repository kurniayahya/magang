<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

$queries = [
    "ALTER TABLE sekolah ADD COLUMN tahun_aktif VARCHAR(4) DEFAULT '2024'",
    "ALTER TABLE sekolah ADD COLUMN jam_masuk_mulai TIME DEFAULT '06:00:00'",
    "ALTER TABLE sekolah ADD COLUMN jam_masuk_selesai TIME DEFAULT '09:00:00'",
    "ALTER TABLE sekolah ADD COLUMN jam_pulang_mulai TIME DEFAULT '15:00:00'",
    "ALTER TABLE sekolah ADD COLUMN jam_pulang_selesai TIME DEFAULT '18:00:00'",
    "ALTER TABLE siswa ADD COLUMN tahun_pkl VARCHAR(4)",
    "ALTER TABLE guru ADD COLUMN kode VARCHAR(10) UNIQUE",
    "ALTER TABLE presensi ADD COLUMN is_wfa TINYINT(1) DEFAULT 0, ADD COLUMN alasan_wfa TEXT"
];

foreach ($queries as $query) {
    try {
        $db->exec($query);
        echo "Sukses eksekusi: $query <br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Abaikan (Kolom sudah ada): $query <br>\n";
        } else {
            echo "Error: " . $e->getMessage() . " pada query: $query <br>\n";
        }
    }
}

echo "<br><b>Database migration completed.</b>";
