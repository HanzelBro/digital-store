<?php
require_once 'config/db.php';

$orderCode = $_GET['order'] ?? '';
if (!$orderCode) { header('Location: index.php'); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$orderCode]);
$order = $stmt->fetch();
if (!$order) { header('Location: index.php'); exit; }

$pageTitle = 'Konfirmasi Pembayaran - HanzelStore';
require_once 'includes/header.php';

$stmtC = $pdo->prepare("SELECT * FROM payment_confirmations WHERE order_code = ?");
$stmtC->execute([$orderCode]);
$existing = $stmtC->fetch();

$errors = [];
$success = false;
$transferDate = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    if (!verifyCsrfToken()) {
        $errors[] = 'Sesi form telah kedaluwarsa. Silakan refresh halaman dan coba lagi.';
    } else {
        $senderName    = trim($_POST['sender_name'] ?? '');
        $paymentSource = trim($_POST['payment_source'] ?? '');
        $transferAmt   = (float)($_POST['transfer_amount'] ?? 0);
        $transferDate  = $_POST['transfer_date'] ?? date('Y-m-d');
        $notes         = trim($_POST['notes'] ?? '');

        if (!$senderName)      $errors[] = 'Nama pengirim wajib diisi.';
        if (!$paymentSource)   $errors[] = 'Sumber pembayaran wajib diisi.';
        if ($transferAmt < 1)  $errors[] = 'Jumlah pembayaran tidak valid.';
        if (!$transferDate)    $errors[] = 'Tanggal pembayaran wajib diisi.';

        if (empty($errors)) {
            $stmt2 = $pdo->prepare("INSERT INTO payment_confirmations (order_id,order_code,sender_name,bank_name,transfer_amount,transfer_date,notes) VALUES (?,?,?,?,?,?,?)");
            $stmt2->execute([$order['id'], $orderCode, $senderName, $paymentSource, $transferAmt, $transferDate, $notes]);
            $pdo->prepare("UPDATE orders SET payment_status='pending' WHERE id=?")->execute([$order['id']]);
            $success = true;
        }
    }
}

$paymentInstructions = [
    'qris' => [
        'title' => 'QRIS',
        'description' => 'Pindai kode QR di bawah ini untuk melakukan pembayaran.',
        'image' => SITE_URL . '/assets/img/qris.png',
        'image_exists' => file_exists(__DIR__ . '/assets/img/qris.png'),
    ],
    'e_wallet' => [
        'title' => 'E-Wallet',
        'description' => 'Selesaikan pembayaran lewat e-wallet pilihanmu, kemudian kirim konfirmasi di bawah.',
    ],
];

$paymentSourceOptions = [
    'qris' => ['QRIS'],
    'e_wallet' => ['GoPay', 'DANA', 'OVO', 'ShopeePay', 'LinkAja', 'Lainnya'],
];

$sourceLabel = $order['payment_method'] === 'e_wallet' ? 'E-Wallet yang digunakan *' : 'Sumber Pembayaran *';
?>

<div class="container" style="padding-top:36px;padding-bottom:72px">

<?php if ($success): ?>
    <div class="success-wrap">
        <span class="success-ico">🎉</span>
        <h2>Konfirmasi Terkirim!</h2>
        <p style="color:var(--muted);margin-top:10px">Terima kasih! Tim kami akan memverifikasi dalam <strong>1×24 jam</strong>.</p>
        <div class="order-code"><?= htmlspecialchars($orderCode) ?></div>
        <p style="font-size:.82rem;color:var(--dim)">Simpan kode ini untuk pelacakan</p>
        <a href="index.php" class="btn btn-primary btn-lg" style="margin-top:22px">Kembali Belanja</a>
    </div>

<?php elseif ($existing): ?>
    <div class="success-wrap">
        <span class="success-ico">✅</span>
        <h2>Sudah Dikonfirmasi</h2>
        <p style="color:var(--muted);margin-top:10px">Konfirmasi untuk pesanan ini sudah pernah dikirim.</p>
        <div class="order-code"><?= htmlspecialchars($orderCode) ?></div>
        <p style="font-size:.82rem;color:var(--yellow)">Status: Menunggu Verifikasi</p>
        <a href="index.php" class="btn btn-outline" style="margin-top:20px">Kembali ke Toko</a>
    </div>

<?php else: ?>
    <div class="page-head">
        <h1>Konfirmasi Pembayaran</h1>
        <p style="color:var(--muted);margin-top:5px">Kode Pesanan: #<?= htmlspecialchars($orderCode) ?></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo "⚠ $e<br>"; ?></div>
    <?php endif; ?>

    <div class="three-col">
        <div>
            <div class="form-section">
                <div class="form-title">Instruksi Pembayaran</div>
                <div class="alert alert-info" style="margin-bottom:0">
                    💡 Total yang harus dibayarkan: <strong><?= formatPrice($order['total_amount']) ?></strong>
                </div>
                <?php $instruction = $paymentInstructions[$order['payment_method']] ?? null; ?>
                <?php if ($instruction): ?>
                    <div style="margin-top:16px;padding:18px;background:rgba(13,17,23,.84);border:1px solid var(--border);border-radius:var(--r);">
                        <h4 style="margin-top:0;margin-bottom:10px;font-size:1rem"><?= htmlspecialchars($instruction['title']) ?></h4>
                        <p style="color:var(--muted);margin-bottom:16px;line-height:1.75"><?= htmlspecialchars($instruction['description']) ?></p>
                        <?php if ($order['payment_method'] === 'qris'): ?>
                            <?php if ($instruction['image_exists']): ?>
                                <img src="<?= htmlspecialchars($instruction['image']) ?>" alt="QRIS Pembayaran HanzelStore" width="320" height="320" style="width:100%;max-width:320px;border-radius:var(--r);border:1px solid var(--border)">
                                <p style="color:var(--dim);font-size:.82rem;margin-top:12px">Pindai kode QR di atas menggunakan aplikasi mobile banking atau e-wallet.</p>
                            <?php else: ?>
                                <div class="alert alert-danger" style="margin-top:0">
                                    ⚠ Gambar QRIS belum tersedia. Silakan unggah file QRIS ke <code>assets/img/qris.png</code>.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top:18px">Silakan gunakan metode pembayaran yang tersedia. Setelah pembayaran selesai, kirim konfirmasi di bawah.</div>
                <?php endif; ?>
            </div>

            <div class="form-section">
                <div class="form-title">Form Konfirmasi</div>
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label class="form-label">Nama Pengirim *</label>
                        <input type="text" name="sender_name" class="form-control" placeholder="Nama di rekening atau aplikasi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($sourceLabel) ?></label>
                        <select name="payment_source" class="form-control" required>
                            <option value="">- Pilih Sumber Pembayaran -</option>
                            <?php foreach ($paymentSourceOptions[$order['payment_method']] ?? ['Lainnya'] as $source): ?>
                                <option value="<?= htmlspecialchars($source) ?>" <?= (isset($paymentSource) && $paymentSource === $source) ? 'selected' : '' ?>><?= htmlspecialchars($source) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Transfer *</label>
                        <input type="number" name="transfer_amount" class="form-control" value="<?= $order['total_amount'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Transfer *</label>
                        <input type="date" name="transfer_date" class="form-control" value="<?= htmlspecialchars($transferDate) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Mis: transfer via m-banking jam 10 pagi"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block btn-lg">Kirim Konfirmasi Pembayaran</button>
                </form>
            </div>
        </div>

        <div>
            <div class="summary">
                <div class="s-title">Ringkasan Pesanan</div>
                <div class="bank-box" style="margin-top:0;margin-bottom:14px">
                    <h4>Kode Pesanan</h4>
                    <div style="font-family:monospace;font-size:1.05rem;font-weight:700;color:var(--alight);letter-spacing:.08em"><?= htmlspecialchars($orderCode) ?></div>
                </div>
                <div class="s-row"><span class="s-label">Nama</span><span><?= htmlspecialchars($order['customer_name']) ?></span></div>
                <div class="s-row"><span class="s-label">Email</span><span style="font-size:.8rem"><?= htmlspecialchars($order['customer_email']) ?></span></div>
                <div class="s-row"><span class="s-label">Bayar via</span><span><?= ucwords(str_replace('_',' ',$order['payment_method'])) ?></span></div>
                <div class="s-row total"><span>Total</span><span><?= formatPrice($order['total_amount']) ?></span></div>
                <div style="margin-top:14px;padding:11px;background:rgba(63,185,80,.06);border:1px solid rgba(63,185,80,.2);border-radius:var(--rs);font-size:.78rem;color:var(--muted);line-height:1.8">
                    ⏱ Verifikasi <strong class="tag-green">1×24 jam</strong><br>
                    📧 Konfirmasi dikirim ke email<br>
                    💬 Hubungi kami jika ada kendala
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
