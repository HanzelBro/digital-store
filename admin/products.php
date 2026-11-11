<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();

// Hapus produk (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    if (!verifyCsrfToken()) {
        die('Akses ditolak: CSRF token tidak valid.');
    }
    $id = (int)($_POST['product_id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if ($prod && $prod['image']) {
            $path = __DIR__ . '/../assets/img/' . $prod['image'];
            if (file_exists($path)) @unlink($path);
        }
        $pdo->prepare("DELETE FROM product_durations WHERE product_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        header('Location: products.php?deleted=1');
        exit;
    }
}

$pageTitle = 'Produk - Admin HanzelStore';
require_once 'includes/admin_header.php';

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>

<div class="topbar">
    <div>
        <h1>📦 Kelola Produk</h1>
        <p><?= count($products) ?> produk terdaftar</p>
    </div>
    <a href="product_form.php" class="btn btn-primary">+ Tambah Produk</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">✅ Produk berhasil dihapus.</div>
<?php elseif (isset($_GET['saved'])): ?>
    <div class="alert alert-success">✅ Produk berhasil disimpan.</div>
<?php endif; ?>

<div class="table-wrap">
    <?php if (empty($products)): ?>
        <div class="empty">Belum ada produk. <a href="product_form.php">Tambah produk pertama</a></div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Kategori</th>
                <th>Paket Durasi</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <div class="flex-g">
                        <div class="thumb">
                            <?php if ($p['image']): ?>
                                <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($p['image']) ?>" alt="">
                            <?php else: ?>
                                <?= $p['icon'] ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-size:.76rem;color:var(--dim)"><?= htmlspecialchars($p['slug']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="color:var(--muted)"><?= htmlspecialchars($p['category']) ?></td>
                <td>
                    <?php
                    $durs = getProductDurations($p['id']);
                    if (empty($durs)):
                    ?>
                        <span style="color:var(--dim);font-size:.82rem">Belum ada paket</span>
                    <?php else: ?>
                        <span style="color:var(--muted);font-size:.82rem"><?= count($durs) ?> paket · mulai <?= formatPrice(min(array_column($durs,'price'))) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏ Edit</a>
                        <form method="POST" onsubmit="return confirm('Yakin hapus produk ini?');" style="margin:0;display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" name="delete_product" value="1" class="btn btn-danger btn-sm" title="Hapus Produk">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
