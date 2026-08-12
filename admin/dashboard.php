<?php
$pageTitle = 'Dashboard - Admin HanzelStore';
require_once 'includes/admin_header.php';

$pdo = getDB();

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();
$revenue       = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status = 'confirmed'")->fetchColumn();

$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<div class="topbar">
    <div>
        <h1>📊 Dashboard</h1>
        <p>Selamat datang kembali, <?= htmlspecialchars($_SESSION['admin_username']) ?>!</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="ico">📦</div>
        <div class="num"><?= $totalProducts ?></div>
        <div class="lbl">Total Produk</div>
    </div>
    <div class="stat-card">
        <div class="ico">🧾</div>
        <div class="num"><?= $totalOrders ?></div>
        <div class="lbl">Total Pesanan</div>
    </div>
    <div class="stat-card">
        <div class="ico">⏳</div>
        <div class="num" style="color:var(--yellow)"><?= $pendingOrders ?></div>
        <div class="lbl">Menunggu Verifikasi</div>
    </div>
    <div class="stat-card">
        <div class="ico">💰</div>
        <div class="num" style="color:var(--green)"><?= formatPrice($revenue) ?></div>
        <div class="lbl">Total Pendapatan (Confirmed)</div>
    </div>
</div>

<h2 style="font-size:1.1rem;margin-bottom:14px">Pesanan Terbaru</h2>
<div class="table-wrap">
    <?php if (empty($recentOrders)): ?>
        <div class="empty">Belum ada pesanan masuk.</div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Pelanggan</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr>
                <td class="mono"><?= htmlspecialchars($o['order_code']) ?></td>
                <td><?= htmlspecialchars($o['customer_name']) ?></td>
                <td><?= formatPrice($o['total_amount']) ?></td>
                <td><span class="status status-<?= htmlspecialchars($o['payment_status']) ?>"><?= htmlspecialchars($o['payment_status']) ?></span></td>
                <td style="color:var(--muted)"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                <td><a href="order_detail.php?code=<?= urlencode($o['order_code']) ?>" class="btn btn-outline btn-sm">Detail</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
