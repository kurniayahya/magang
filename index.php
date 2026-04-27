<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple Router
$route = $_GET['route'] ?? '';

if (!$route || $route === 'login') {
    if (isLoggedIn()) {
        if ($_SESSION['user_role'] === 'siswa') {
            redirect(APP_URL . '/dashboard');
        } else {
            redirect(APP_URL . '/admin_dashboard');
        }
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['aktif'] == 0) {
                $error = 'Akun Anda tidak aktif. Silakan hubungi admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_nama'] = $user['nama'];
                
                $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                if ($user['role'] === 'siswa') {
                    redirect(APP_URL . '/dashboard');
                } elseif ($user['role'] === 'guru') {
                    redirect(APP_URL . '/guru_dashboard');
                } else {
                    redirect(APP_URL . '/admin_dashboard');
                }
            }
        } else {
            $error = 'Email atau password salah.';
        }
    }

    $pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?> | <?= SCHOOL_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="login-container">
    <div class="animate-fade-in">
        <div class="login-brand">
            <span class="brand-icon">🎓</span>
            <h1>MOPI</h1>
            <p>Mobile Praktik Kerja Lapangan</p>
            <div style="margin-top:12px;background:rgba(30,111,217,0.08);border:1px solid rgba(30,111,217,0.2);border-radius:20px;padding:6px 16px;display:inline-block;">
                <span style="font-size:0.82rem;font-weight:600;color:var(--primary);"><i class="fas fa-school" style="margin-right:5px;"></i><?= SCHOOL_NAME ?></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="user@email.com" required value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                Masuk Ke Aplikasi
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; color: var(--text-muted); font-size: 0.85rem;">
            <p>&copy; <?= date('Y') ?> MOPI App. All rights reserved.</p>
            <p style="margin-top: 10px;">Lupa password? Hubungi Admin Sekolah.</p>
        </div>
    </div>
</body>
</html>
<?php
} else {
    // Route mapping
    $routes = [
        // Siswa
        'dashboard'         => 'pages/dashboard.php',
        'presensi'          => 'pages/presensi.php',
        'jurnal'            => 'pages/jurnal.php',
        'laporan'           => 'pages/laporan.php',
        'portofolio'        => 'pages/portofolio.php',
        'profil'            => 'pages/profil.php',
        'pengaturan'        => 'pages/pengaturan.php',
        'chat'              => 'pages/chat.php',
        'notifikasi'        => 'pages/notifikasi.php',
        // Guru
        'guru_dashboard'    => 'pages/guru_dashboard.php',
        'guru/validasi'     => 'pages/guru/validasi_jurnal.php',
        'guru/peta'         => 'pages/guru/tempat_pkl_map.php',
        // Admin
        'admin_dashboard'   => 'pages/admin_dashboard.php',
        'admin/users'       => 'pages/admin/users.php',
        'admin/import'      => 'pages/admin/import.php',
        'admin/tempat_pkl'  => 'pages/admin/tempat_pkl.php',
        // Shared
        'logout'            => 'logout.php',
    ];

    if (array_key_exists($route, $routes)) {
        require_once __DIR__ . '/' . $routes[$route];
    } else {
        http_response_code(404);
        die("404 - Halaman Tidak Ditemukan");
    }
}
?>
