<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$code = $_GET['code'] ?? '';
if (!$code) { header('Location: orders.php'); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$code]);
$order = $stmt->fetch();
if (!$order) { header('Location: orders.php'); exit; }

// Update status or delete order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_order'])) {
        $stmt2 = $pdo->prepare("DELETE FROM payment_confirmations WHERE order_id = ?");
        $stmt2->execute([$order['id']]);
        $stmt2 = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt2->execute([$order['id']]);
        $stmt2 = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt2->execute([$order['id']]);
        header('Location: orders.php?deleted=1');
        exit;
    }
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        if (in_array($newStatus, ['pending','confirmed','rejected'])) {
            $confirmedAt = $newStatus === 'confirmed' ? date('Y-m-d H:i:s') : null;
            $stmt2 = $pdo->prepare("UPDATE orders SET payment_status = ?, confirmed_at = ? WHERE id = ?");
            $stmt2->execute([$newStatus, $confirmedAt, $order['id']]);
            header('Location: order_detail.php?code=' . urlencode($code) . '&updated=1');
            exit;
        }
    }
}

$pageTitle = 'Detail Pesanan - Admin HanzelStore';
require_once 'includes/admin_header.php';

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$conf = $pdo->prepare("SELECT * FROM payment_confirmations WHERE order_id = ?");
$conf->execute([$order['id']]);
$confirmation = $conf->fetch();
?>

<div class="topbar">
    <div>
        <h1>🧾 Pesanan #<?= htmlspecialchars($order['order_code']) ?></h1>
        <p>Dibuat pada <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="orders.php" class="btn btn-outline">← Kembali</a>
        <form method="POST" onsubmit="return confirm('Hapus pesanan ini beserta semua detailnya?');" style="margin:0">
            <button type="submit" name="delete_order" value="1" class="btn btn-danger">🗑 Hapus Pesanan</button>
        </form>
    </div>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Status pesanan berhasil diperbarui.</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <div>
        <!-- Info pelanggan -->
        <div class="form-card" style="margin-bottom:18px;max-width:none">
            <h3 style="font-size:.9rem;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">Info Pelanggan</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:.88rem">
                <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Nama</div><?= htmlspecialchars($order['customer_name']) ?></div>
                <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Email</div><?= htmlspecialchars($order['customer_email']) ?></div>
                <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">No. WhatsApp</div><?= htmlspecialchars($order['customer_phone'] ?: '-') ?></div>
                <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Metode Bayar</div><?= htmlspecialchars(ucwords(str_replace('_',' ',$order['payment_method']))) ?></div>
            </div>
            <?php if ($order['notes']): ?>
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                    <div style="color:var(--dim);font-size:.78rem;margin-bottom:5px">Catatan</div>
                    <div style="font-size:.86rem;color:var(--muted)"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Produk dibeli -->
        <div class="table-wrap" style="margin-bottom:18px">
            <table>
                <thead><tr><th>Produk</th><th>Durasi</th><th>Harga</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['product_name']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($it['duration_label']) ?></td>
                        <td><?= formatPrice($it['price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="font-weight:700">Total</td>
                        <td></td>
                        <td style="font-weight:700"><?= formatPrice($order['total_amount']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Bukti konfirmasi transfer -->
        <div class="form-card" style="max-width:none">
            <h3 style="font-size:.9rem;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">Konfirmasi Transfer dari Pelanggan</h3>
            <?php if (!$confirmation): ?>
                <p style="color:var(--dim);font-size:.86rem">Pelanggan belum mengirim konfirmasi pembayaran.</p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:.88rem">
                    <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Nama Pengirim</div><?= htmlspecialchars($confirmation['sender_name']) ?></div>
                    <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Sumber Pembayaran</div><?= htmlspecialchars($confirmation['bank_name']) ?></div>
                    <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Jumlah Transfer</div><?= formatPrice($confirmation['transfer_amount']) ?></div>
                    <div><div style="color:var(--dim);font-size:.78rem;margin-bottom:3px">Tanggal Transfer</div><?= date('d M Y', strtotime($confirmation['transfer_date'])) ?></div>
                </div>
                <?php if ($confirmation['notes']): ?>
                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                        <div style="color:var(--dim);font-size:.78rem;margin-bottom:5px">Catatan Pelanggan</div>
                        <div style="font-size:.86rem;color:var(--muted)"><?= nl2br(htmlspecialchars($confirmation['notes'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (abs($confirmation['transfer_amount'] - $order['total_amount']) > 0.01): ?>
                    <div class="alert alert-danger" style="margin-top:14px;margin-bottom:0">
                        ⚠ Jumlah transfer tidak sama dengan total pesanan! Periksa kembali sebelum konfirmasi.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Aksi status -->
    <div class="form-card" style="position:sticky;top:20px">
        <h3 style="font-size:.9rem;margin-bottom:16px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em">Status Pesanan</h3>
        <div style="margin-bottom:18px">
            <span class="status status-<?= htmlspecialchars($order['payment_status']) ?>" style="font-size:.8rem;padding:6px 14px"><?= htmlspecialchars($order['payment_status']) ?></span>
        </div>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <div class="form-group">
                <label class="form-label">Ubah Status</label>
                <select name="status" class="form-control">
                    <option value="pending" <?= $order['payment_status']==='pending'?'selected':'' ?>>Pending</option>
                    <option value="confirmed" <?= $order['payment_status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                    <option value="rejected" <?= $order['payment_status']==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">💾 Simpan Status</button>
        </form>

        <?php if ($order['confirmed_at']): ?>
            <p style="font-size:.78rem;color:var(--dim);margin-top:14px">
                Dikonfirmasi pada <?= date('d M Y, H:i', strtotime($order['confirmed_at'])) ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
