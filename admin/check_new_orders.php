<?php
require_once __DIR__ . '/../config/db.php';

// Keamanan: Hanya admin yang bisa akses
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = getDB();
    
    // Ambil ID pesanan tertinggi (paling baru) saat ini
    $maxStmt = $pdo->query("SELECT MAX(id) FROM orders");
    $maxId = (int)$maxStmt->fetchColumn();

    // Param last_id dikirim dari JavaScript browser admin
    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

    $newOrders = [];
    if ($lastId > 0 && $maxId > $lastId) {
        // Ambil order baru yang ID-nya lebih besar dari last_id
        $stmt = $pdo->prepare("SELECT id, order_code, customer_name, total_amount, created_at FROM orders WHERE id > ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$lastId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as $o) {
            $newOrders[] = [
                'id'            => $o['id'],
                'order_code'    => $o['order_code'],
                'customer_name' => $o['customer_name'],
                'total_formatted' => formatPrice($o['total_amount']),
                'created_at'    => date('H:i', strtotime($o['created_at']))
            ];
        }
    }

    // Ambil jumlah pending orders untuk statistik/badge
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'");
    $pendingCount = (int)$pendingStmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'max_id'        => $maxId,
        'has_new'       => count($newOrders) > 0,
        'new_orders'    => $newOrders,
        'pending_count' => $pendingCount
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
