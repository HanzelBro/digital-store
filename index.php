<?php
require_once 'config/db.php';

$pageTitle = 'HanzelStore - Software & Tools Premium';
require_once 'includes/header.php';

$pdo    = getDB();
$search = trim($_GET['search'] ?? '');
$cat    = trim($_GET['cat'] ?? '');
$where  = '1=1'; $params = [];

if ($search) {
    $where .= ' AND (name LIKE ? OR short_desc LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($cat) { $where .= ' AND category = ?'; $params[] = $cat; }

$stmt = $pdo->prepare("SELECT * FROM products WHERE $where ORDER BY id ASC");
$stmt->execute($params);
$products = $stmt->fetchAll();
$cats = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="eyebrow"> Premium Digital Products by HanzelStore</div>
            <h1>Software &amp; Tools Premium <br> untuk <span> Membantu Anda </span></h1>
            <p class="hero-sub">Temukan produk digital premium yang siap membantu kamu kerja lebih cepat &amp; kreatif.</p>
            <form method="GET" class="search-row">
                <input type="text" name="search" class="form-control" placeholder="Cari produk…" value="<?= htmlspecialchars($search) ?>" style="flex:1">
                <button type="submit" class="btn btn-primary" style="flex:none">🔍 Cari</button>
                <?php if ($search || $cat): ?>
                    <a href="index.php" class="btn btn-outline" style="flex:none">✕</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;align-items:center">
            <span style="font-size:.8rem;color:var(--dim)">Filter:</span>
            <a href="index.php<?= $search ? '?search='.urlencode($search) : '' ?>"
               class="btn btn-sm <?= !$cat ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
            <?php foreach ($cats as $c): ?>
                <a href="index.php?cat=<?= urlencode($c) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="btn btn-sm <?= $cat === $c ? 'btn-primary' : 'btn-outline' ?>"><?= htmlspecialchars($c) ?></a>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:28px">
            <h2><?= $search ? 'Hasil: "'.htmlspecialchars($search).'"' : 'Semua Produk' ?></h2>
            <p style="color:var(--muted);margin-top:5px"><?= count($products) ?> produk ditemukan</p>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty">
                <span class="empty-ico">🔍</span>
                <h3>Produk tidak ditemukan</h3>
                <p>Coba kata kunci lain atau hapus filter.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:16px">Lihat Semua</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p):
                    $bcls      = 'b-' . strtolower(str_replace(' ', '', $p['badge'] ?? ''));
                    $lowest    = getLowestPrice($p['id']);
                ?>
                <div class="card">
                    <div class="card-head">
                        <div class="p-icon">
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <?= htmlspecialchars($p['icon']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($p['badge']): ?>
                            <span class="badge <?= htmlspecialchars($bcls) ?>"><?= htmlspecialchars($p['badge']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <span class="p-cat"><?= htmlspecialchars($p['category']) ?></span>
                        <div class="p-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="p-desc"><?= htmlspecialchars($p['short_desc']) ?></div>
                    </div>
                    <div class="card-foot">
                        <div class="price-row">
                            <div>
                                <div style="font-size:.72rem;color:var(--dim);margin-bottom:2px">Mulai dari</div>
                                <div class="price-main"><?= $lowest > 0 ? formatPrice($lowest) : '-' ?></div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-primary btn-block">Lihat Paket & Beli</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
