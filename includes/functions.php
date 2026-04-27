<?php
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, 
        CASE 
            WHEN u.role = 'siswa' THEN s.id 
            ELSE NULL 
        END as siswa_id,
        CASE 
            WHEN u.role = 'siswa' THEN s.nis 
            ELSE NULL 
        END as nis,
        CASE 
            WHEN u.role = 'siswa' THEN s.kelas 
            ELSE NULL 
        END as kelas,
        CASE 
            WHEN u.role = 'siswa' THEN s.tempat_pkl_id 
            ELSE NULL 
        END as tempat_pkl_id,
        CASE 
            WHEN u.role = 'siswa' THEN s.tanggal_mulai 
            ELSE NULL 
        END as tanggal_mulai,
        CASE 
            WHEN u.role = 'siswa' THEN s.total_hari_pkl 
            ELSE NULL 
        END as total_hari_pkl,
        CASE 
            WHEN u.role = 'siswa' THEN s.guru_pembimbing_id 
            ELSE NULL 
        END as guru_pembimbing_id
        FROM users u
        LEFT JOIN siswa s ON u.id = s.user_id AND u.role = 'siswa'
        WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getSiswaInfo($user_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, u.nama, u.email, u.foto, u.telepon,
               j.nama as jurusan_nama, j.kode as jurusan_kode,
               sk.nama as sekolah_nama,
               tp.nama as tempat_pkl_nama, tp.alamat as tempat_pkl_alamat,
               tp.latitude, tp.longitude, tp.radius_meter,
               tp.nama_pembimbing as nama_pembimbing_industri,
               ug.nama as nama_guru_pembimbing
        FROM siswa s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN jurusan j ON s.jurusan_id = j.id
        LEFT JOIN sekolah sk ON s.sekolah_id = sk.id
        LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
        LEFT JOIN users ug ON s.guru_pembimbing_id = ug.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function getHariKePKL($tanggal_mulai) {
    if (!$tanggal_mulai) return 0;
    $mulai = new DateTime($tanggal_mulai);
    $sekarang = new DateTime();
    $diff = $mulai->diff($sekarang);
    $hari = 0;
    $temp = clone $mulai;
    while ($temp <= $sekarang) {
        $dow = $temp->format('N');
        if ($dow < 6) $hari++; // Senin-Jumat
        $temp->modify('+1 day');
    }
    return max(0, $hari);
}

function getUnreadNotifCount($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND dibaca = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function getUnreadChatCount($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM chat WHERE ke_user_id = ? AND dibaca = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function formatTanggal($tanggal, $format = 'd M Y') {
    if (!$tanggal) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $bulan_panjang = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = new DateTime($tanggal);
    $result = $d->format($format);
    if ($format === 'd M Y') {
        $result = $d->format('d') . ' ' . $bulan[(int)$d->format('m')] . ' ' . $d->format('Y');
    } elseif ($format === 'd F Y') {
        $result = $d->format('d') . ' ' . $bulan_panjang[(int)$d->format('m')] . ' ' . $d->format('Y');
    }
    return $result;
}

function getHariNama($tanggal) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $hari[date('w', strtotime($tanggal))];
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getAvatarUrl($foto, $nama) {
    if ($foto && file_exists(UPLOAD_PATH . $foto)) {
        return UPLOAD_URL . $foto;
    }
    $initials = strtoupper(substr($nama, 0, 1));
    return 'https://ui-avatars.com/api/?name=' . urlencode($nama) . '&background=1E6FD9&color=fff&size=128';
}

// CSRF Protection
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed.');
        }
    }
}
