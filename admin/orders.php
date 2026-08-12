<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pageTitle = 'Pesanan - Admin HanzelStore';
$status = $_GET['status'] ?? '';
$pdo    = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId > 0) {
        $stmt = $pdo->prepare("DELETE FROM payment_confirmations WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $redirect = 'orders.php?deleted=1';
        if ($status) {
            $redirect .= '&status=' . urlencode($status);
        }
        header('Location: ' . $redirect);
        exit;
    }
}

require_once 'includes/admin_header.php';

$where = '1=1'; $params = [];
if ($status) { $where .= ' AND payment_status = ?'; $params[] = $status; }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="topbar">
    <div>
        <h1>🧾 Semua Pesanan</h1>
        <p><?= count($orders) ?> pesanan ditemukan</p>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success" style="margin-top:12px;max-width:fit-content">✅ Pesanan berhasil dihapus.</div>
        <?php endif; ?>
    </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px">
    <a href="orders.php" class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
    <a href="orders.php?status=pending" class="btn btn-sm <?= $status==='pending' ? 'btn-primary' : 'btn-outline' ?>">Pending</a>
    <a href="orders.php?status=confirmed" class="btn btn-sm <?= $status==='confirmed' ? 'btn-primary' : 'btn-outline' ?>">Confirmed</a>
    <a href="orders.php?status=rejected" class="btn btn-sm <?= $status==='rejected' ? 'btn-primary' : 'btn-outline' ?>">Rejected</a>
</div>

<div class="table-wrap">
    <?php if (empty($orders)): ?>
        <div class="empty">Belum ada pesanan.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Email</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="mono"><?= htmlspecialchars($o['order_code']) ?></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td style="color:var(--muted)"><?= htmlspecialchars($o['customer_email']) ?></td>
                <td><?= formatPrice($o['total_amount']) ?></td>
                <td style="color:var(--muted)"><?= htmlspecialchars(ucwords(str_replace('_',' ',$o['payment_method']))) ?></td>
                <td><span class="status status-<?= htmlspecialchars($o['payment_status']) ?>"><?= htmlspecialchars($o['payment_status']) ?></span></td>
                <td style="color:var(--muted)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td style="display:flex;gap:6px;justify-content:flex-end;">
                    <a href="order_detail.php?code=<?= urlencode($o['order_code']) ?>" class="btn btn-outline btn-sm">Detail</a>
                    <form method="POST" onsubmit="return confirm('Hapus pesanan <?= htmlspecialchars($o['order_code']) ?>?');" style="margin:0;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" name="delete_order" value="1" class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
