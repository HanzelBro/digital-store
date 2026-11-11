<?php
require_once __DIR__ . '/../config/db.php';

if (isAdminLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
$rateCheck = checkLoginRateLimit(5, 15);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$rateCheck['allowed']) {
        $error = "Terlalu banyak percobaan login gagal. Akses dikunci sementara. Silakan coba lagi dalam {$rateCheck['remaining_minutes']} menit.";
    } elseif (!verifyCsrfToken()) {
        $error = 'Sesi form telah kedaluwarsa. Silakan refresh halaman dan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            recordLoginAttempt(true);
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            recordLoginAttempt(false);
            $checkAgain = checkLoginRateLimit(5, 15);
            if (!$checkAgain['allowed']) {
                $error = "Terlalu banyak percobaan login gagal. Akses dikunci sementara selama {$checkAgain['remaining_minutes']} menit.";
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
} elseif (!$rateCheck['allowed']) {
    $error = "Terlalu banyak percobaan login gagal. Akses dikunci sementara. Silakan coba lagi dalam {$rateCheck['remaining_minutes']} menit.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - HanzelStore</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'Segoe UI',system-ui,sans-serif;background:#0D1117;color:#F0F0F5;
    min-height:100vh;display:flex;align-items:center;justify-content:center;
}
.login-box{
    background:#161B22;border:1px solid #262B34;border-radius:10px;
    padding:36px 32px;width:100%;max-width:380px;box-shadow:0 12px 32px rgba(0,0,0,.45);
}
.login-brand{
    text-align:center;font-family:'Cascadia Code',monospace;font-weight:700;
    font-size:1.25rem;margin-bottom:6px;
}
.login-brand span{color:#6C63FF}
.login-sub{text-align:center;color:#9BA1B6;font-size:.85rem;margin-bottom:26px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.84rem;font-weight:600;color:#9BA1B6;margin-bottom:7px}
.form-control{
    width:100%;background:#0D1117;border:1px solid #353B47;color:#F0F0F5;
    border-radius:6px;padding:10px 14px;font-size:.9rem;font-family:inherit;
}
.form-control:focus{outline:none;border-color:#6C63FF}
.btn-login{
    width:100%;background:#6C63FF;color:#fff;border:none;border-radius:6px;
    padding:11px;font-size:.92rem;font-weight:600;cursor:pointer;margin-top:6px;
    transition:.15s ease;
}
.btn-login:hover{background:#7E76FF}
.alert-danger{
    background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:#F85149;
    padding:11px 14px;border-radius:6px;font-size:.85rem;margin-bottom:18px;
}
.back-link{display:block;text-align:center;margin-top:20px;color:#9BA1B6;font-size:.82rem;text-decoration:none}
.back-link:hover{color:#F0F0F5}
</style>
</head>
<body>
    <div class="login-box">
        <div class="login-brand"><span>‖</span> HanzelStore <span>Admin</span></div>
        <p class="login-sub">Masuk untuk mengelola toko</p>

        <?php if ($error): ?>
            <div class="alert-danger">⚠ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Masuk</button>
        </form>
        <a href="../index.php" class="back-link">← Kembali ke Toko</a>
    </div>
</body>
</html>
