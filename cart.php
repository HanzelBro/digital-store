<?php
require_once 'config/db.php';

if (isset($_GET['remove'])) {
    removeFromCart($_GET['remove']);
    header('Location: cart.php'); exit;
}

$pageTitle = 'Keranjang - HanzelStore';
require_once 'includes/header.php';

$cart  = getCart();
$total = cartTotal();
?>

<div class="container">
    <div class="page-head">
        <h1>🛒 Keranjang Belanja</h1>
        <p style="color:var(--muted);margin-top:5px"><?= count($cart) ?> item dipilih</p>
    </div>

    <?php if (empty($cart)): ?>
        <div class="empty">
            <span class="empty-ico">🛒</span>
            <h3>Keranjang Kamu Kosong</h3>
            <p>Belum ada produk yang dipilih.</p>
            <a href="index.php" class="btn btn-primary btn-lg" style="margin-top:18px">Lihat Semua Produk</a>
        </div>
    <?php else: ?>
        <div class="two-col">
            <div>
                <?php
                $pdo = getDB();
                foreach ($cart as $item):
                    $s = $pdo->prepare("SELECT icon, image FROM products WHERE id = ?");
                    $s->execute([$item['product_id']]);
                    $prod  = $s->fetch();
                    $icon  = $prod ? $prod['icon'] : '📦';
                    $image = $prod ? $prod['image'] : null;
                ?>
                <div class="cart-item">
                    <div class="ci-icon">
                        <?php if (!empty($image)): ?>
                            <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($image) ?>" alt="">
                        <?php else: ?>
                            <?= htmlspecialchars($icon) ?>
                        <?php endif; ?>
                    </div>
                    <div class="ci-info">
                        <div class="ci-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="ci-price"><?= formatPrice($item['price']) ?></div>
                        <div style="font-size:.78rem;color:var(--alight);margin-top:3px">📅 Durasi: <?= htmlspecialchars($item['duration_label']) ?></div>
                    </div>
                    <a href="cart.php?remove=<?= urlencode($item['cart_key']) ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Hapus item ini?')">✕ Hapus</a>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:14px">
                    <a href="index.php" class="btn btn-ghost">← Lanjut Belanja</a>
                </div>
            </div>

            <div class="summary">
                <div class="s-title">Ringkasan Pesanan</div>
                <?php foreach ($cart as $item): ?>
                <div class="s-row">
                    <span class="s-label" style="font-size:.8rem">
                        <?php
                        $nameLabel = $item['name'];
                        if (mb_strlen($nameLabel) > 18) {
                            $nameLabel = mb_substr($nameLabel, 0, 18) . '…';
                        }
                        ?>
                        <?= htmlspecialchars($nameLabel) ?>
                        <span style="color:var(--alight)">(<?= htmlspecialchars($item['duration_label']) ?>)</span>
                    </span>
                    <span><?= formatPrice($item['price']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="s-row" style="margin-top:6px">
                    <span class="s-label">Subtotal (<?= count($cart) ?> produk)</span>
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
                <a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-top:18px">
                    Lanjut ke Checkout →
                </a>
                <p style="text-align:center;font-size:.74rem;color:var(--dim);margin-top:10px">🔒 Pembayaran aman</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
