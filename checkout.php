<?php
require_once 'config/db.php';

$cart  = getCart();
$total = cartTotal();
if (empty($cart)) { header('Location: cart.php'); exit; }

$errors = []; $input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['name']    = trim($_POST['name'] ?? '');
    $input['email']   = trim($_POST['email'] ?? '');
    $input['phone']   = trim($_POST['phone'] ?? '');
    $input['payment'] = $_POST['payment'] ?? 'qris';
    $input['notes']   = trim($_POST['notes'] ?? '');

    if (!$input['name']) $errors[] = 'Nama lengkap wajib diisi.';
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    if (empty($errors)) {
        $pdo  = getDB();
        $code = generateOrderCode();
        $stmt = $pdo->prepare("INSERT INTO orders (order_code,customer_name,customer_email,customer_phone,total_amount,payment_method,notes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$code, $input['name'], $input['email'], $input['phone'], $total, $input['payment'], $input['notes']]);
        $orderId = $pdo->lastInsertId();
        $stmt2 = $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_name,duration_label,price) VALUES (?,?,?,?,?)");
        foreach ($cart as $item) {
            $stmt2->execute([$orderId, $item['product_id'], $item['name'], $item['duration_label'], $item['price']]);
        }
        $_SESSION['last_order_id']   = $orderId;
        $_SESSION['last_order_code'] = $code;
        $_SESSION['cart'] = [];
        header("Location: confirm.php?order=$code"); exit;
    }
}

$pageTitle = 'Checkout - HanzelStore';
require_once 'includes/header.php';

$methods = [
    'qris'     => ['🔲','QRIS','Pindai kode QR untuk membayar'],
    'e_wallet' => ['💳','E-Wallet','Bayar lewat dompet digital pilihanmu'],
];
?>

<div class="container">
    <div class="page-head">
        <h1>💳 Checkout</h1>
        <p style="color:var(--muted);margin-top:5px">Lengkapi data pembelian kamu</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e) echo "⚠ $e<br>"; ?></div>
    <?php endif; ?>

    <div class="three-col">
        <div>
            <div class="form-section">
                <div class="form-title">Informasi Pembeli</div>
                <form method="POST" id="coForm">
                    <div class="form-group">
                        <label class="form-label">Nama *</label>
                        <input type="text" name="name" class="form-control" placeholder="Nama" value="<?= htmlspecialchars($input['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat Email *</label>
                        <input type="email" name="email" class="form-control" placeholder="email@domain.com" value="<?= htmlspecialchars($input['email'] ?? '') ?>" required>
                        <small style="color:var(--dim);font-size:.76rem;margin-top:4px;display:block">Link download dikirim ke sini setelah pembayaran dikonfirmasi.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. WhatsApp (opsional)</label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($input['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ada yang ingin disampaikan?"><?= htmlspecialchars($input['notes'] ?? '') ?></textarea>
                    </div>
                </form>
            </div>

            <div class="form-section">
                <div class="form-title">Metode Pembayaran</div>
                <?php foreach ($methods as $val => [$ico, $label, $desc]): ?>
                <label class="pay-opt">
                    <input type="radio" name="payment" value="<?= $val ?>" form="coForm"
                           <?= (($input['payment'] ?? 'qris') === $val) ? 'checked' : '' ?>>
                    <span style="font-size:1.25rem"><?= $ico ?></span>
                    <div>
                        <div class="pay-label"><?= $label ?></div>
                        <div class="pay-desc"><?= $desc ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
                <button type="submit" form="coForm" class="btn btn-primary btn-block btn-lg" style="margin-top:20px">
                    🔒 Konfirmasi Pesanan - <?= formatPrice($total) ?>
                </button>
            </div>
        </div>

        <div>
            <div class="summary">
                <div class="s-title">Pesanan Kamu</div>
                <?php
                $pdo = getDB();
                foreach ($cart as $item):
                    $s = $pdo->prepare("SELECT icon FROM products WHERE id = ?");
                    $s->execute([$item['product_id']]);
                    $prod = $s->fetch();
                    $icon = $prod ? $prod['icon'] : '📦';
                ?>
                <div class="co-item">
                    <span style="font-size:1.2rem"><?= $icon ?></span>
                    <span class="co-name">
                        <?= htmlspecialchars($item['name']) ?>
                        <span style="color:var(--alight)">(<?= htmlspecialchars($item['duration_label']) ?>)</span>
                    </span>
                    <span style="font-weight:700"><?= formatPrice($item['price']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="s-row" style="margin-top:6px">
                    <span class="s-label">Subtotal</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <div class="s-row">
                    <span class="s-label">Biaya Layanan</span>
                    <span class="tag-green">Gratis</span>
                </div>
                <div class="s-row total">
                    <span>Total</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <div style="margin-top:14px;font-size:.76rem;color:var(--dim);text-align:center;line-height:1.8">
                    Dengan melanjutkan, kamu menyetujui syarat & ketentuan HanzelStore.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
