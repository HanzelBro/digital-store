<?php
// =============================================
// Konfigurasi Database - TEMPLATE
// =============================================
// Copy file ini ke config/db.php lalu isi kredensial Anda

date_default_timezone_set('Asia/Jakarta');

// Ubah ke 'production' saat website sudah di-hosting
define('ENVIRONMENT', 'development'); 

// Konfigurasi Database
define('DB_HOST', 'localhost');          // Host database (misal: localhost / sqlXXX.infinityfree.com)
define('DB_USER', 'root');               // Username database
define('DB_PASS', '');                   // Password database
define('DB_NAME', 'digital_store');      // Nama database

// Konfigurasi Situs
define('SITE_NAME', 'HanzelStore');
define('SITE_URL', 'http://localhost/digital-store'); // URL situs (tanpa slash di akhir, gunakan https:// saat production)
define('CURRENCY', 'Rp');

// =============================================
// Keamanan Session & Cookie
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Muat modul security & kirim header keamanan HTTP
require_once __DIR__ . '/../includes/security.php';
sendSecurityHeaders();

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                http_response_code(500);
                die('<div style="font-family:sans-serif;max-width:500px;margin:50px auto;padding:24px;background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);text-align:center;">
                    <h3 style="color:#d9534f;margin-bottom:8px">Layanan Sedang Mengalami Kendala</h3>
                    <p style="color:#666;font-size:0.95rem;line-height:1.5">Sistem database sedang dalam pemeliharaan atau tidak dapat dijangkau. Silakan coba kembali dalam beberapa saat.</p>
                </div>');
            }
            die('<div style="font-family:monospace;padding:20px;background:#1a0000;color:#ff6b6b;border:1px solid #ff0000;">
                <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                <br><small>Pastikan MySQL sudah berjalan dan database sudah dibuat.</small>
            </div>');
        }
    }
    return $pdo;
}

function formatPrice($amount) {
    return CURRENCY . ' ' . number_format($amount, 0, ',', '.');
}

function generateOrderCode() {
    return 'DH-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function generateSlug($text) {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// ===== Durasi produk helpers =====
function getProductDurations($productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM product_durations WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function getLowestPrice($productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT MIN(price) FROM product_durations WHERE product_id = ?");
    $stmt->execute([$productId]);
    return (float)$stmt->fetchColumn();
}

// ===== Cart helpers =====
function getCart() {
    return $_SESSION['cart'] ?? [];
}

function addToCart($productId, $productName, $durationId, $durationLabel, $price) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $cartKey = $productId . '-' . $durationId;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['cart_key'] === $cartKey) return false;
    }
    $_SESSION['cart'][] = [
        'cart_key'       => $cartKey,
        'product_id'     => $productId,
        'duration_id'    => $durationId,
        'name'           => $productName,
        'duration_label' => $durationLabel,
        'price'          => $price,
    ];
    return true;
}

function removeFromCart($cartKey) {
    if (!isset($_SESSION['cart'])) return;
    $_SESSION['cart'] = array_filter($_SESSION['cart'], fn($i) => $i['cart_key'] !== $cartKey);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

function isInCart($productId, $durationId) {
    foreach (getCart() as $item) {
        if ($item['product_id'] == $productId && $item['duration_id'] == $durationId) return true;
    }
    return false;
}

function cartTotal() {
    $total = 0;
    foreach (getCart() as $item) $total += $item['price'];
    return $total;
}

function cartCount() {
    return count(getCart());
}

// ===== Admin helpers =====
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
