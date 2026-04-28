<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple Router
$route = $_GET['route'] ?? '';
$route = ltrim($route, '/'); // Mendukung Nginx try_files $uri yang membawa slash di depan

if (!$route || $route === 'login') {
    if (isLoggedIn()) {
        if ($_SESSION['user_role'] === 'siswa') {
            redirect(APP_URL . '/dashboard');
        } else {
            redirect(APP_URL . '/admin_dashboard');
        }
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM sekolah WHERE id = ?");
    $stmt->execute([SCHOOL_ID]);
    $sekolahInfo = $stmt->fetch();
    $sekolahLogo = $sekolahInfo['logo'] ?? '';

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['credential'])) {
            // Handle Google Login
            $credential = $_POST['credential'];
            $response = file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . $credential);
            $payload = json_decode($response, true);
            
            if ($payload && isset($payload['email'])) {
                $email = sanitize($payload['email']);
                $db = getDB();
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
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
                    $error = 'Email ' . htmlspecialchars($email) . ' tidak terdaftar dalam sistem.';
                }
            } else {
                $error = 'Gagal memverifikasi login Google.';
            }
        } else {
            // Normal Login
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
    <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== ''): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
</head>
<body class="login-container">
    <div class="animate-fade-in">
        <div class="login-brand">
            <?php if (!empty($sekolahLogo)): ?>
                <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($sekolahLogo) ?>" alt="Logo Sekolah" style="max-height: 80px; margin-bottom: 10px; display: inline-block;">
            <?php else: ?>
                <span class="brand-icon">🎓</span>
            <?php endif; ?>
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
                <input type="text" name="email" class="form-control" placeholder="user@email.com" required value="<?= isset($_POST['email']) && !isset($_POST['credential']) ? sanitize($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                Masuk Ke Aplikasi
            </button>
        </form>

        <?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== ''): ?>
        <div style="text-align: center; margin: 20px 0;">
            <span style="color: var(--text-muted); font-size: 0.85rem; background: var(--background); padding: 0 10px; position: relative; z-index: 1;">ATAU</span>
            <hr style="border: none; border-top: 1px solid var(--border); margin-top: -10px;">
        </div>
        
        <div id="g_id_onload"
             data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>"
             data-context="signin"
             data-ux_mode="popup"
             data-login_uri="<?= APP_URL ?>/index.php"
             data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="left"
             style="display: flex; justify-content: center; margin-bottom: 20px;">
        </div>
        <?php endif; ?>

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
        'guru/rekap'        => 'pages/guru/rekap.php',
        // Admin
        'admin_dashboard'   => 'pages/admin_dashboard.php',
        'admin/users'       => 'pages/admin/users.php',
        'admin/import'      => 'pages/admin/import.php',
        'admin/tempat_pkl'  => 'pages/admin/tempat_pkl.php',
        'admin/rekap'       => 'pages/admin/rekap.php',
        'admin/pengaturan'  => 'pages/admin/pengaturan.php',
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
