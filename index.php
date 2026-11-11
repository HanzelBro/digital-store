<?php
require_once 'config/db.php';

$pageTitle = 'HanzelStore - Software & Akun Premium';
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
            <h1>Lisensi Software &amp; Akun Premium <br><span>Siap Digunakan Langsung</span></h1>
            <p class="hero-sub">Layanan resmi akun berlangganan desain, video, dan hiburan dengan aktivasi cepat serta garansi transaksi aman.</p>
            <form method="GET" class="search-row" role="search">
                <input type="text" name="search" class="form-control" placeholder="Cari nama software atau tools…" value="<?= htmlspecialchars($search) ?>" aria-label="Cari produk digital" style="flex:1">
                <button type="submit" class="btn btn-primary" style="flex:none">Cari</button>
                <?php if ($search || $cat): ?>
                    <a href="index.php" class="btn btn-outline" style="flex:none" aria-label="Hapus filter pencarian">✕</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:28px;align-items:center" role="navigation" aria-label="Filter Kategori">
            <span style="font-size:.82rem;font-weight:600;color:var(--dim)">Kategori:</span>
            <a href="index.php<?= $search ? '?search='.urlencode($search) : '' ?>"
               class="btn btn-sm <?= !$cat ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
            <?php foreach ($cats as $c): ?>
                <a href="index.php?cat=<?= urlencode($c) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="btn btn-sm <?= $cat === $c ? 'btn-primary' : 'btn-outline' ?>"><?= htmlspecialchars($c) ?></a>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:24px">
            <h2><?= $search ? 'Hasil Pencarian: "'.htmlspecialchars($search).'"' : 'Katalog Pilihan' ?></h2>
            <p style="color:var(--muted);font-size:.9rem;margin-top:4px"><?= count($products) ?> produk tersedia</p>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty">
                <span class="empty-ico" aria-hidden="true">🔍</span>
                <h3>Produk Tidak Ditemukan</h3>
                <p>Tidak ada produk yang cocok dengan pencarian Anda saat ini.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top:16px">Tampilkan Semua Produk</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p):
                    $bcls      = 'b-' . strtolower(str_replace(' ', '', $p['badge'] ?? ''));
                    $lowest    = getLowestPrice($p['id']);
                ?>
                <article class="card">
                    <div class="card-head">
                        <div class="p-icon" aria-hidden="true">
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" width="54" height="54" loading="lazy" style="object-fit:cover;border-radius:var(--rs)">
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
                        <h3 class="p-name"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="p-desc"><?= htmlspecialchars($p['short_desc']) ?></p>
                    </div>
                    <div class="card-foot">
                        <div class="price-row">
                            <div>
                                <div style="font-size:.75rem;color:var(--dim);margin-bottom:2px">Mulai dari</div>
                                <div class="price-main"><?= $lowest > 0 ? formatPrice($lowest) : '-' ?></div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="product.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-primary btn-block">Pilih Paket &amp; Beli</a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
