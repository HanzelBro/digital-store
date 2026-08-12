<?php
require_once 'config/db.php';

$orderCode = strtoupper(trim($_GET['order'] ?? ''));
$order = null;
$error = '';
$confirmation = null;
$statusLabels = [
    'pending'   => 'Menunggu Verifikasi',
    'confirmed' => 'Terkonfirmasi',
    'rejected'  => 'Ditolak',
];

if ($orderCode !== '') {
    if (!preg_match('/^[A-Z0-9-]+$/', $orderCode)) {
        $error = 'Kode pesanan tidak valid. Silakan cek kembali.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = 'Pesanan tidak ditemukan. Pastikan kode pesanan sudah benar.';
        } else {
            $stmtC = $pdo->prepare("SELECT * FROM payment_confirmations WHERE order_id = ?");
            $stmtC->execute([$order['id']]);
            $confirmation = $stmtC->fetch();
        }
    }
}

$pageTitle = 'Lacak Pesanan - HanzelStore';
require_once 'includes/header.php';
?>

<div class="container" style="padding-top:36px;padding-bottom:72px">
    <div class="page-head">
        <h1>🔎 Lacak Pesanan</h1>
        <p style="color:var(--muted);margin-top:5px">Masukkan kode pesanan untuk melihat status terbaru.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-section" style="max-width:560px;margin:0 auto;">
        <form method="GET" action="track.php">
            <div class="form-group">
                <label class="form-label">Kode Pesanan</label>
                <input type="text" name="order" class="form-control" placeholder="Contoh: DH-1A2B3C4D" value="<?= htmlspecialchars($orderCode) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Lihat Status</button>
        </form>
    </div>

    <?php if ($order): ?>
        <div class="summary" style="margin-top:28px;max-width:700px;margin-left:auto;margin-right:auto;">
            <div class="s-title">Status Pesanan</div>
            <div class="s-row"><span class="s-label">Kode</span><span class="mono"><?= htmlspecialchars($order['order_code']) ?></span></div>
            <div class="s-row"><span class="s-label">Nama</span><span><?= htmlspecialchars($order['customer_name']) ?></span></div>
            <div class="s-row"><span class="s-label">Email</span><span><?= htmlspecialchars($order['customer_email']) ?></span></div>
            <div class="s-row"><span class="s-label">Total</span><span><?= formatPrice($order['total_amount']) ?></span></div>
            <div class="s-row"><span class="s-label">Metode Bayar</span><span><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span></div>
            <div class="s-row">
                <span class="s-label">Status</span>
                <span style="font-weight:700;text-transform:capitalize;<?= $order['payment_status'] === 'confirmed' ? 'color:var(--green)' : ($order['payment_status'] === 'rejected' ? 'color:var(--yellow)' : 'color:var(--muted)') ?>">
                    <?= htmlspecialchars($statusLabels[$order['payment_status']] ?? $order['payment_status']) ?>
                </span>
            </div>
            <div class="s-row"><span class="s-label">Dibuat pada</span><span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></div>
            <?php if ($order['confirmed_at']): ?>
                <div class="s-row"><span class="s-label">Dikonfirmasi pada</span><span><?= date('d M Y, H:i', strtotime($order['confirmed_at'])) ?></span></div>
            <?php endif; ?>

            <?php if ($confirmation): ?>
                <div style="margin-top:18px;padding:18px;background:rgba(13,17,23,.8);border:1px solid var(--border);border-radius:var(--r);">
                    <div class="form-title" style="margin-bottom:12px">Detail Konfirmasi Pembayaran</div>
                    <div class="s-row"><span class="s-label">Nama Pengirim</span><span><?= htmlspecialchars($confirmation['sender_name']) ?></span></div>
                    <div class="s-row"><span class="s-label">Sumber Pembayaran</span><span><?= htmlspecialchars($confirmation['bank_name']) ?></span></div>
                    <div class="s-row"><span class="s-label">Jumlah Transfer</span><span><?= formatPrice($confirmation['transfer_amount']) ?></span></div>
                    <div class="s-row"><span class="s-label">Tanggal Transfer</span><span><?= date('d M Y', strtotime($confirmation['transfer_date'])) ?></span></div>
                    <?php if ($confirmation['notes']): ?>
                        <div class="s-row" style="align-items:flex-start"><span class="s-label">Catatan</span><span style="white-space:pre-wrap;color:var(--muted);font-size:.9rem;"><?= htmlspecialchars($confirmation['notes']) ?></span></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-top:18px">
                    Belum ada konfirmasi pembayaran untuk pesanan ini. Jika sudah transfer, silakan kirim konfirmasi melalui <a href="confirm.php?order=<?= urlencode($order['order_code']) ?>" style="color:var(--alight);text-decoration:underline">halaman konfirmasi</a>.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
