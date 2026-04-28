<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    $db->exec("ALTER TABLE sekolah ADD COLUMN tahun_aktif VARCHAR(4) DEFAULT '2024'");
    echo "Added tahun_aktif to sekolah.\n";
    
    $db->exec("ALTER TABLE siswa ADD COLUMN tahun_pkl VARCHAR(4)");
    echo "Added tahun_pkl to siswa.\n";
    
    $db->exec("ALTER TABLE guru ADD COLUMN kode VARCHAR(10) UNIQUE");
    echo "Added kode to guru.\n";
    
    echo "Database migration successful.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
