<?php
require_once __DIR__ . '/../config/db.php';

if (isAdminLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']       = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
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
    background:#161B22;border:1px solid #21262D;border-radius:14px;
    padding:40px 36px;width:100%;max-width:380px;
}
.login-brand{
    text-align:center;font-family:'Cascadia Code',monospace;font-weight:700;
    font-size:1.3rem;margin-bottom:6px;
}
.login-brand span{color:#6C63FF}
.login-sub{text-align:center;color:#8B8FA8;font-size:.85rem;margin-bottom:28px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:.84rem;font-weight:600;color:#8B8FA8;margin-bottom:7px}
.form-control{
    width:100%;background:#0D1117;border:1px solid #30363D;color:#F0F0F5;
    border-radius:8px;padding:11px 14px;font-size:.9rem;font-family:inherit;
}
.form-control:focus{outline:none;border-color:#6C63FF;box-shadow:0 0 0 3px rgba(108,99,255,.35)}
.btn-login{
    width:100%;background:#6C63FF;color:#fff;border:none;border-radius:8px;
    padding:12px;font-size:.92rem;font-weight:700;cursor:pointer;margin-top:6px;
    transition:.2s ease;
}
.btn-login:hover{background:#8B85FF}
.alert-danger{
    background:rgba(248,81,73,.1);border:1px solid rgba(248,81,73,.3);color:#F85149;
    padding:11px 14px;border-radius:8px;font-size:.85rem;margin-bottom:18px;
}
.back-link{display:block;text-align:center;margin-top:20px;color:#8B8FA8;font-size:.82rem;text-decoration:none}
.back-link:hover{color:#F0F0F5}
</style>
</head>
<body>
    <div class="login-box">
        <div class="login-brand">◆ HanzelStore <span>Admin</span></div>
        <p class="login-sub">Masuk untuk mengelola toko</p>

        <?php if ($error): ?>
            <div class="alert-danger">⚠ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
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
