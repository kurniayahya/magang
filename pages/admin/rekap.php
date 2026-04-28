<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

// Autoload PhpSpreadsheet
$autoload = __DIR__ . '/../../vendor/autoload.php';
$hasSpreadsheet = file_exists($autoload);
if ($hasSpreadsheet) require_once $autoload;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$db = getDB();
$jurusan_id = $_GET['jurusan_id'] ?? '';

// Ambil list jurusan untuk filter
$jurusanList = getAllJurusan();

// Query Data
$sql = "SELECT s.id, u.nama, s.nis, s.kelas, j.nama as jurusan_nama,
        COUNT(CASE WHEN p.status = 'hadir' AND p.is_wfa = 0 THEN 1 END) as total_hadir,
        COUNT(CASE WHEN p.status = 'hadir' AND p.is_wfa = 1 THEN 1 END) as total_wfa,
        COUNT(CASE WHEN p.status = 'izin' THEN 1 END) as total_izin,
        COUNT(CASE WHEN p.status = 'sakit' THEN 1 END) as total_sakit,
        COUNT(CASE WHEN p.status = 'alpha' THEN 1 END) as total_alpha,
        (SELECT COUNT(*) FROM jurnal jn WHERE jn.siswa_id = s.id) as total_jurnal
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN jurusan j ON s.jurusan_id = j.id
    LEFT JOIN presensi p ON s.id = p.siswa_id";

$params = [];
if ($jurusan_id) {
    $sql .= " WHERE s.jurusan_id = ?";
    $params[] = $jurusan_id;
}
$sql .= " GROUP BY s.id ORDER BY u.nama ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$dataRekap = $stmt->fetchAll();

// --- Export Excel ---
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    if (!$hasSpreadsheet) {
        die("PhpSpreadsheet belum terinstall.");
    }
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Presensi & Jurnal');

    $headers = ['No', 'Nama Siswa', 'NIS', 'Kelas', 'Jurusan', 'Hadir', 'WFA', 'Sakit', 'Izin', 'Alpha', 'Total Jurnal'];
    $sheet->fromArray($headers, null, 'A1');
    
    // Style Header
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E6FD9']],
    ]);
    
    $rowIdx = 2;
    $no = 1;
    foreach ($dataRekap as $row) {
        $sheet->fromArray([
            $no++,
            $row['nama'],
            $row['nis'],
            $row['kelas'],
            $row['jurusan_nama'],
            $row['total_hadir'],
            $row['total_wfa'],
            $row['total_sakit'],
            $row['total_izin'],
            $row['total_alpha'],
            $row['total_jurnal']
        ], null, "A$rowIdx");
        $rowIdx++;
    }

    foreach (range(1, count($headers)) as $col) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }

    $filename = "Rekap_Kehadiran_Admin_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$pageTitle = 'Rekap Laporan Siswa';
$showBack = true;
include __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <form method="GET" action="" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="route" value="admin/rekap">
            <select name="jurusan_id" class="form-control" style="width:200px; padding:8px;" onchange="this.form.submit()">
                <option value="">-- Semua Jurusan --</option>
                <?php foreach ($jurusanList as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $jurusan_id == $j['id'] ? 'selected' : '' ?>><?= $j['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        
        <a href="?route=admin/rekap&jurusan_id=<?= urlencode($jurusan_id) ?>&export=excel" class="btn btn-primary" style="width:auto; padding:8px 15px;">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>
</div>

<div class="card" style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; min-width: 800px; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border);">
                <th style="padding: 10px;">Nama Siswa</th>
                <th style="padding: 10px;">Kelas/Jurusan</th>
                <th style="padding: 10px; text-align:center;">Hadir</th>
                <th style="padding: 10px; text-align:center;">WFA</th>
                <th style="padding: 10px; text-align:center;">Sakit</th>
                <th style="padding: 10px; text-align:center;">Izin</th>
                <th style="padding: 10px; text-align:center;">Alpha</th>
                <th style="padding: 10px; text-align:center;">Jurnal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dataRekap)): ?>
            <tr>
                <td colspan="8" style="padding: 20px; text-align: center; color: var(--text-muted);">Belum ada data</td>
            </tr>
            <?php else: ?>
                <?php foreach ($dataRekap as $r): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 10px;">
                        <div style="font-weight: 600;"><?= $r['nama'] ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">NIS: <?= $r['nis'] ?></div>
                    </td>
                    <td style="padding: 10px;">
                        <div style="font-size: 0.85rem;"><?= $r['kelas'] ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $r['jurusan_nama'] ?></div>
                    </td>
                    <td style="padding: 10px; text-align:center;"><span style="background:var(--success); color:white; padding:2px 8px; border-radius:12px; font-size:0.75rem;"><?= $r['total_hadir'] ?></span></td>
                    <td style="padding: 10px; text-align:center;"><span style="background:var(--primary); color:white; padding:2px 8px; border-radius:12px; font-size:0.75rem;"><?= $r['total_wfa'] ?></span></td>
                    <td style="padding: 10px; text-align:center;"><span style="background:var(--warning); color:white; padding:2px 8px; border-radius:12px; font-size:0.75rem;"><?= $r['total_sakit'] ?></span></td>
                    <td style="padding: 10px; text-align:center;"><span style="background:#3b82f6; color:white; padding:2px 8px; border-radius:12px; font-size:0.75rem;"><?= $r['total_izin'] ?></span></td>
                    <td style="padding: 10px; text-align:center;"><span style="background:var(--error); color:white; padding:2px 8px; border-radius:12px; font-size:0.75rem;"><?= $r['total_alpha'] ?></span></td>
                    <td style="padding: 10px; text-align:center;"><span style="font-weight:600;"><?= $r['total_jurnal'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
