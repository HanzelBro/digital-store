<?php
require_once 'config/db.php';
$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: index.php'); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
$stmt->execute([$slug]);
$p = $stmt->fetch();
if (!$p) { header('Location: index.php'); exit; }

$durations = getProductDurations($p['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
    $durationId = (int)($_POST['duration_id'] ?? 0);
    $selected = null;
    foreach ($durations as $d) {
        if ($d['id'] == $durationId) { $selected = $d; break; }
    }
    if ($selected) {
        addToCart($p['id'], $p['name'], $selected['id'], $selected['label'], $selected['price']);
        header('Location: product.php?slug=' . urlencode($slug) . '&msg=added');
        exit;
    }
}

$pageTitle = $p['name'] . ' - HanzelStore';
require_once 'includes/header.php';

$features = $p['features'] ? explode('|', $p['features']) : [];
$bcls     = 'b-' . strtolower(str_replace(' ', '', $p['badge'] ?? ''));
$msg      = $_GET['msg'] ?? '';
?>

<div class="container" style="padding:36px 24px 72px">
    <nav style="font-size:.82rem;color:var(--dim);margin-bottom:24px">
        <a href="index.php" style="color:var(--muted)">Produk</a>
        <span style="margin:0 8px">›</span>
        <span style="color:var(--muted)"><?= htmlspecialchars($p['category']) ?></span>
        <span style="margin:0 8px">›</span>
        <?= htmlspecialchars($p['name']) ?>
    </nav>

    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success">✅ Berhasil ditambahkan ke keranjang. <a href="cart.php" style="font-weight:600;text-decoration:underline">Buka Keranjang</a></div>
    <?php endif; ?>

    <div class="two-col">
        <div>
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px">
                <div class="p-icon" style="width:68px;height:68px;font-size:2.4rem">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" width="68" height="68" style="object-fit:cover;border-radius:var(--rs)">
                    <?php else: ?>
                        <?= htmlspecialchars($p['icon']) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($p['badge']): ?>
                        <span class="badge <?= $bcls ?>" style="margin-bottom:7px;display:inline-block"><?= $p['badge'] ?></span>
                    <?php endif; ?>
                    <span class="p-cat" style="display:block"><?= htmlspecialchars($p['category']) ?></span>
                </div>
            </div>

            <h1 style="font-size:1.9rem;margin-bottom:24px"><?= htmlspecialchars($p['name']) ?></h1>

            <p style="color:var(--muted);line-height:1.8;font-size:.95rem;margin-bottom:28px">
                <?= nl2br(htmlspecialchars($p['description'])) ?>
            </p>

            <?php if (!empty($features)): ?>
            <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:22px">
                <h3 style="font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;color:var(--dim);margin-bottom:14px">Fitur Unggulan</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px">
                    <?php foreach ($features as $f): ?>
                    <div style="display:flex;align-items:center;gap:9px;font-size:.86rem">
                        <span class="tag-green">✓</span>
                        <span style="color:var(--muted)"><?= htmlspecialchars($f) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="summary">
                <div class="s-title">Pilih Paket Durasi</div>

                <?php if (empty($durations)): ?>
                    <p style="color:var(--dim);font-size:.86rem">Belum ada paket tersedia untuk produk ini.</p>
                <?php else: ?>
                <form method="POST">
                    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:18px">
                        <?php foreach ($durations as $i => $d):
                            $origPrice = (float)$d['original_price'];
                            $disc = $origPrice > $d['price'] ? round((1 - $d['price']/$origPrice)*100) : 0;
                            $already = isInCart($p['id'], $d['id']);
                        ?>
                        <label class="pay-opt" style="align-items:flex-start">
                            <input type="radio" name="duration_id" value="<?= $d['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> required style="margin-top:4px">
                            <div style="flex:1">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <span class="pay-label">📅 <?= htmlspecialchars($d['label']) ?></span>
                                    <?php if ($disc > 0): ?>
                                        <span class="price-disc">-<?= $disc ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
                                    <span style="font-weight:800;font-size:1.05rem;color:var(--text)"><?= formatPrice($d['price']) ?></span>
                                    <?php if ($origPrice > 0): ?>
                                        <span class="price-ori"><?= formatPrice($origPrice) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($already): ?>
                                    <div style="font-size:.75rem;color:var(--green);margin-top:3px">✓ Sudah ada di keranjang</div>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="background:rgba(63,185,80,.06);border:1px solid rgba(63,185,80,.2);border-radius:var(--rs);padding:11px 13px;margin-bottom:16px;font-size:.8rem;color:var(--green)">
                        ✓ Aktif sesuai durasi &nbsp;·&nbsp; ✓ Bisa upgrade durasi kapan saja
                    </div>

                    <input type="hidden" name="add_cart" value="1">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Tambah ke Keranjang</button>
                </form>
                <a href="cart.php" class="btn btn-outline btn-block" style="margin-top:9px;text-align:center">Buka Keranjang</a>
                <?php endif; ?>

                <div style="margin-top:18px;padding-top:14px;border-top:1px solid var(--border);font-size:.78rem;color:var(--dim);text-align:center;line-height:1.8">
                    🔒 Pembayaran aman · 📦 Produk digital
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
