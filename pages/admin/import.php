<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('admin');

// Autoload PhpSpreadsheet
$autoload = __DIR__ . '/../../vendor/autoload.php';
$hasSpreadsheet = file_exists($autoload);
if ($hasSpreadsheet) require_once $autoload;

use PhpOffice\PhpSpreadsheet\IOFactory;

$user = getCurrentUser();
$db   = getDB();
$msg  = '';
$err  = '';
$preview = [];
$importType = $_GET['type'] ?? 'siswa'; // siswa | guru

// --- Download Template ---
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    if (!$hasSpreadsheet) {
        die("PhpSpreadsheet belum terinstall. Jalankan: composer install");
    }
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if ($type === 'siswa') {
        $sheet->setTitle('Template Siswa');
        $headers = ['nama','email','password','nis','kelas','kode_jurusan','tempat_pkl_id','kode_guru','tanggal_mulai','tanggal_selesai','total_hari_pkl','tahun_pkl'];
        $examples = ['Andi Pratama','andi@mopi.id','password','12345','XII TKR 1','TKR','1','G-01','2026-01-06','2026-04-04','90','2024'];
        
        // Add Reference Sheets
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Referensi Guru');
        $sheet2->fromArray([['kode', 'nama']], null, 'A1');
        $gurus = getDB()->query("SELECT guru.kode, users.nama FROM guru JOIN users ON guru.user_id = users.id")->fetchAll(PDO::FETCH_ASSOC);
        $rowIdx = 2;
        foreach($gurus as $g) { $sheet2->fromArray([$g['kode'], $g['nama']], null, "A$rowIdx"); $rowIdx++; }
        $sheet2->getColumnDimension('A')->setAutoSize(true);
        $sheet2->getColumnDimension('B')->setAutoSize(true);
        
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Referensi Tempat PKL');
        $sheet3->fromArray([['id', 'nama']], null, 'A1');
        $tempat = getDB()->query("SELECT id, nama FROM tempat_pkl")->fetchAll(PDO::FETCH_ASSOC);
        $rowIdx = 2;
        foreach($tempat as $t) { $sheet3->fromArray([$t['id'], $t['nama']], null, "A$rowIdx"); $rowIdx++; }
        $sheet3->getColumnDimension('A')->setAutoSize(true);
        $sheet3->getColumnDimension('B')->setAutoSize(true);
        
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Referensi Jurusan');
        $sheet4->fromArray([['kode', 'nama']], null, 'A1');
        $jurusan = getDB()->query("SELECT kode, nama FROM jurusan")->fetchAll(PDO::FETCH_ASSOC);
        $rowIdx = 2;
        foreach($jurusan as $j) { $sheet4->fromArray([$j['kode'], $j['nama']], null, "A$rowIdx"); $rowIdx++; }
        $sheet4->getColumnDimension('A')->setAutoSize(true);
        $sheet4->getColumnDimension('B')->setAutoSize(true);
        
        $spreadsheet->setActiveSheetIndex(0); // Go back to main sheet
    } else {
        $sheet->setTitle('Template Guru');
        $headers = ['nama','email','password','nip','kode'];
        $examples = ['Pak Budi','budi@mopi.id','password','19800101200001001','G-01'];
    }

    $sheet->fromArray([$headers, $examples], null, 'A1');
    // Style header
    $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E6FD9']],
    ]);
    foreach (range(1, count($headers)) as $col) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }

    $filename = "template_{$type}_mopi.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

// --- Upload & Process ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xlsx_file'])) {
    verify_csrf();
    if (!$hasSpreadsheet) {
        $err = "PhpSpreadsheet belum terinstall. Jalankan: <code>composer install</code> di folder project.";
    } elseif ($_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
        $err = "Gagal upload file.";
    } else {
        $tmpPath = $_FILES['xlsx_file']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $headers = array_shift($rows); // Baris pertama = header
            $headers = array_map('strtolower', array_map('trim', $headers));

            $importType = $_POST['import_type'];
            $imported = 0; $skipped = 0; $errors = [];

            foreach ($rows as $i => $row) {
                if (empty(array_filter($row))) continue; // skip baris kosong
                $data = array_combine($headers, $row);

                $email = trim($data['email'] ?? '');
                $nama  = trim($data['nama'] ?? '');
                if (!$email || !$nama) { $skipped++; continue; }

                // Cek duplikat email
                $chk = $db->prepare("SELECT id FROM users WHERE email=?");
                $chk->execute([$email]);
                if ($chk->fetch()) {
                    $errors[] = "Baris " . ($i+2) . ": Email <b>{$email}</b> sudah ada, dilewati.";
                    $skipped++;
                    continue;
                }

                $hash = hashPassword($data['password'] ?? 'password');
                $role = ($importType === 'siswa') ? 'siswa' : 'guru';

                $stmt = $db->prepare("INSERT INTO users (nama,email,password,role,aktif) VALUES (?,?,?,?,1)");
                $stmt->execute([$nama, $email, $hash, $role]);
                $newId = $db->lastInsertId();

                if ($importType === 'siswa') {
                    // Resolve kode_jurusan to id
                    $jurusanId = null;
                    if (!empty($data['kode_jurusan'])) {
                        $stmtJ = $db->prepare("SELECT id FROM jurusan WHERE kode = ?");
                        $stmtJ->execute([$data['kode_jurusan']]);
                        $jurusanId = $stmtJ->fetchColumn() ?: null;
                    }
                    
                    // Resolve kode_guru to id
                    $guruUserId = null;
                    if (!empty($data['kode_guru'])) {
                        $stmtG = $db->prepare("SELECT user_id FROM guru WHERE kode = ?");
                        $stmtG->execute([$data['kode_guru']]);
                        $guruUserId = $stmtG->fetchColumn() ?: null;
                    }

                    $s = $db->prepare("INSERT INTO siswa (user_id,nis,kelas,jurusan_id,sekolah_id,tempat_pkl_id,guru_pembimbing_id,tanggal_mulai,tanggal_selesai,total_hari_pkl,tahun_pkl) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $s->execute([
                        $newId,
                        $data['nis'] ?? null,
                        $data['kelas'] ?? null,
                        $jurusanId,
                        SCHOOL_ID,
                        (int)($data['tempat_pkl_id'] ?? 0) ?: null,
                        $guruUserId,
                        $data['tanggal_mulai'] ?? null,
                        $data['tanggal_selesai'] ?? null,
                        (int)($data['total_hari_pkl'] ?? 90),
                        $data['tahun_pkl'] ?? null
                    ]);
                } else {
                    $g = $db->prepare("INSERT INTO guru (user_id,nip,kode,sekolah_id) VALUES (?,?,?,?)");
                    $g->execute([$newId, $data['nip'] ?? null, $data['kode'] ?? null, SCHOOL_ID]);
                }
                $imported++;
            }

            $msg = "Import selesai: <b>{$imported}</b> berhasil, <b>{$skipped}</b> dilewati.";
            if ($errors) $msg .= "<br>Detail: " . implode("<br>", $errors);

        } catch (\Exception $e) {
            $err = "Gagal membaca file: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Import Data';
$showBack  = true;
include __DIR__ . '/../../includes/header.php';
?>

<?php if ($msg): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $err ?></div>
<?php endif; ?>

<?php if (!$hasSpreadsheet): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <strong>PhpSpreadsheet belum terinstall!</strong><br>
        Jalankan perintah berikut di terminal dalam folder project:<br>
        <code style="background:#1e293b;color:#e2e8f0;padding:6px 12px;border-radius:6px;display:inline-block;margin-top:6px;">
            composer install
        </code>
    </div>
</div>
<?php endif; ?>

<!-- Tab Switch -->
<div style="display:flex;background:var(--border);border-radius:var(--radius-md);padding:4px;margin-bottom:20px;">
    <a href="?route=admin/import&type=siswa"
       style="flex:1;text-align:center;padding:10px;border-radius:12px;font-weight:600;font-size:0.9rem;text-decoration:none;
              background:<?= $importType==='siswa'?'white':'transparent' ?>;
              color:<?= $importType==='siswa'?'var(--primary)':'var(--text-muted)' ?>;
              box-shadow:<?= $importType==='siswa'?'var(--shadow-sm)':'' ?>;">
        <i class="fas fa-user-graduate"></i> Siswa
    </a>
    <a href="?route=admin/import&type=guru"
       style="flex:1;text-align:center;padding:10px;border-radius:12px;font-weight:600;font-size:0.9rem;text-decoration:none;
              background:<?= $importType==='guru'?'white':'transparent' ?>;
              color:<?= $importType==='guru'?'var(--primary)':'var(--text-muted)' ?>;
              box-shadow:<?= $importType==='guru'?'var(--shadow-sm)':'' ?>;">
        <i class="fas fa-chalkboard-teacher"></i> Guru
    </a>
</div>

<!-- Download Template -->
<div class="card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:15px;">
    <div style="display:flex;align-items:center;gap:15px;">
        <i class="fas fa-file-excel" style="font-size:2rem;color:#1D6F42;flex-shrink:0;"></i>
        <div style="flex:1;">
            <div style="font-weight:700;color:var(--primary);">Download Template <?= ucfirst($importType) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px;">
                Template XLSX dengan contoh data dan format yang benar.
            </div>
        </div>
        <a href="?route=admin/import&download=<?= $importType ?>"
           class="btn btn-primary" style="width:auto;padding:10px 16px;font-size:0.85rem;flex-shrink:0;">
            <i class="fas fa-download"></i> Download
        </a>
    </div>
</div>

<!-- Upload Form -->
<div class="card">
    <div class="card-title"><i class="fas fa-file-import" style="color:var(--primary);"></i> Upload File XLSX</div>
    <form method="POST" action="?route=admin/import&type=<?= $importType ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="import_type" value="<?= $importType ?>">
        <div class="upload-area" onclick="document.getElementById('xlsxInput').click()">
            <i class="fas fa-file-excel upload-icon" style="color:#1D6F42;"></i>
            <p style="font-weight:600;font-size:0.9rem;">Pilih File XLSX</p>
            <p style="font-size:0.75rem;color:var(--text-muted);">Format: .xlsx (Microsoft Excel)</p>
            <input type="file" id="xlsxInput" name="xlsx_file" style="display:none;" accept=".xlsx">
        </div>
        <div id="xlsxName" style="text-align:center;margin-top:10px;font-size:0.85rem;color:var(--primary);font-weight:600;"></div>

        <div class="card" style="background:#fff8e1;border:1px solid #ffd54f;margin:15px 0;padding:14px;">
            <div style="font-weight:700;font-size:0.85rem;margin-bottom:8px;color:#e65100;">
                <i class="fas fa-info-circle"></i> Panduan Kolom — <?= ucfirst($importType) ?>
            </div>
            <?php if ($importType === 'siswa'): ?>
            <div style="font-size:0.75rem;line-height:1.8;color:var(--text-muted);">
                <b>Wajib:</b> nama, email, nis, kelas<br>
                <b>ID/Kode Referensi:</b> kode_jurusan, tempat_pkl_id, kode_guru<br>
                <b>Tanggal:</b> tanggal_mulai, tanggal_selesai (format: YYYY-MM-DD)<br>
                <b>Opsional:</b> password (default: <i>password</i>), total_hari_pkl (default: 90), tahun_pkl<br>
                <b>Otomatis:</b> sekolah diisi dari konfigurasi sistem
            </div>
            <?php else: ?>
            <div style="font-size:0.75rem;line-height:1.8;color:var(--text-muted);">
                <b>Wajib:</b> nama, email<br>
                <b>Opsional:</b> password (default: <i>password</i>), nip, kode<br>
                <b>Otomatis:</b> sekolah diisi dari konfigurasi sistem
            </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary" <?= !$hasSpreadsheet?'disabled':'' ?>>
            <i class="fas fa-upload"></i> Import Sekarang
        </button>
    </form>
</div>

<?php $extraScript = "
<script>
document.getElementById('xlsxInput').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        document.getElementById('xlsxName').innerHTML = '<i class=\"fas fa-file-excel\" style=\"color:#1D6F42;\"></i> ' + this.files[0].name;
    }
});
</script>
";
include __DIR__ . '/../../includes/footer.php'; ?>
