<?php
// ==============================================================================
// Security Framework & Helper Module
// ==============================================================================

if (!defined('SITE_NAME')) {
    // Pastikan tidak diakses langsung jika config belum dimuat
    exit('Akses langsung tidak diperbolehkan.');
}

/**
 * Mengirimkan HTTP Security Headers standar
 */
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

/**
 * Escape string untuk output HTML aman (mencegah XSS)
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

// ==============================================================================
// CSRF (Cross-Site Request Forgery) Protection
// ==============================================================================

/**
 * Mendapatkan atau membuat CSRF token untuk sesi saat ini
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan input hidden CSRF token untuk form HTML
 */
function csrfField() {
    $token = e(getCsrfToken());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Memvalidasi apakah CSRF token dari request cocok dengan session token
 */
function verifyCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken) || empty($token)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

// ==============================================================================
// Rate Limiter / Brute-Force Protection
// ==============================================================================

/**
 * Memeriksa apakah IP klien sedang terkena limit percobaan login
 */
function checkLoginRateLimit($maxAttempts = 5, $lockoutMinutes = 15) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $hash = md5($ip . '_store_admin_login');
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate_' . $hash . '.json';

    if (file_exists($tempFile)) {
        $data = json_decode(@file_get_contents($tempFile), true);
        if ($data && isset($data['count'], $data['last_attempt'])) {
            $elapsed = time() - $data['last_attempt'];
            if ($data['count'] >= $maxAttempts) {
                if ($elapsed < ($lockoutMinutes * 60)) {
                    $remaining = ceil((($lockoutMinutes * 60) - $elapsed) / 60);
                    return ['allowed' => false, 'remaining_minutes' => $remaining];
                } else {
                    @unlink($tempFile);
                }
            }
        }
    }
    return ['allowed' => true, 'remaining_minutes' => 0];
}

/**
 * Mencatat hasil percobaan login (sukses = reset, gagal = tambah hitungan)
 */
function recordLoginAttempt($success) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $hash = md5($ip . '_store_admin_login');
    $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate_' . $hash . '.json';

    if ($success) {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
        return;
    }

    $count = 1;
    if (file_exists($tempFile)) {
        $existing = json_decode(@file_get_contents($tempFile), true);
        if ($existing && isset($existing['count'])) {
            $count = $existing['count'] + 1;
        }
    }
    @file_put_contents($tempFile, json_encode(['count' => $count, 'last_attempt' => time()]), LOCK_EX);
}

// ==============================================================================
// Data Masking (PII Protection)
// ==============================================================================

/**
 * Menyamarkan alamat email untuk privasi (misal: ha***@gmail.com)
 */
function maskEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '***';
    }
    $parts = explode('@', $email, 2);
    $user = $parts[0];
    $domain = $parts[1];
    $len = strlen($user);
    if ($len <= 2) {
        $maskedUser = substr($user, 0, 1) . '***';
    } else {
        $maskedUser = substr($user, 0, 2) . str_repeat('*', max(3, $len - 2));
    }
    return $maskedUser . '@' . $domain;
}

// ==============================================================================
// Upload Image Validator (MIME, Extension, Image Size Check)
// ==============================================================================

/**
 * Memvalidasi file gambar yang diunggah
 */
function validateUploadedImage($file, $maxSizeMb = 2) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Terjadi kesalahan saat upload file.'];
    }
    if ($file['size'] > $maxSizeMb * 1024 * 1024) {
        return ['valid' => false, 'error' => "Ukuran gambar maksimal {$maxSizeMb}MB."];
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return ['valid' => false, 'error' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.'];
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return ['valid' => false, 'error' => 'Tipe konten file bukan gambar yang valid.'];
        }
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'File rusak atau bukan file gambar asli.'];
    }

    return ['valid' => true, 'ext' => $ext];
}
