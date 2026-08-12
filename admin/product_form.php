<?php
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$pdo = getDB();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p   = null;
$existingDurations = [];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if (!$p) { header('Location: products.php'); exit; }
    $existingDurations = getProductDurations($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $shortDesc   = trim($_POST['short_desc'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $badge       = trim($_POST['badge'] ?? '');
    $icon        = trim($_POST['icon'] ?? '📦');
    $features    = trim($_POST['features'] ?? '');

    $durLabels    = $_POST['duration_label'] ?? [];
    $durPrices    = $_POST['duration_price'] ?? [];
    $durOriginals = $_POST['duration_original'] ?? [];

    if (!$name)     $errors[] = 'Nama produk wajib diisi.';
    if (!$category) $errors[] = 'Kategori wajib diisi.';

    // Validasi minimal 1 paket durasi
    $validDurations = [];
    foreach ($durLabels as $i => $label) {
        $label = trim($label);
        $price = (float)($durPrices[$i] ?? 0);
        $orig  = isset($durOriginals[$i]) && $durOriginals[$i] !== '' ? (float)$durOriginals[$i] : null;
        if ($label !== '' && $price > 0) {
            $validDurations[] = ['label' => $label, 'price' => $price, 'original' => $orig];
        }
    }
    if (empty($validDurations)) {
        $errors[] = 'Minimal isi 1 paket durasi (nama dan harga).';
    }

    // Upload gambar
    $imageName = $p['image'] ?? null;
    $newImageUploaded = false;
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, atau WEBP.';
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 2MB.';
        } else {
            $newName = generateSlug($name) . '-' . time() . '.' . $ext;
            $dest = __DIR__ . '/../assets/img/' . $newName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $newImageUploaded = true;
                $imageName = $newName;
            } else {
                $errors[] = 'Gagal mengupload gambar.';
            }
        }
    }

    if (empty($errors)) {
        // Gambar baru berhasil ter-upload dan produk akan disimpan, aman hapus gambar lama
        if ($newImageUploaded && $p && $p['image'] && file_exists(__DIR__ . '/../assets/img/' . $p['image'])) {
            @unlink(__DIR__ . '/../assets/img/' . $p['image']);
        }
        $slug = generateSlug($name);
        $checkSlug = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $checkSlug->execute([$slug, $id]);
        if ($checkSlug->fetch()) {
            $slug .= '-' . substr(md5(uniqid()), 0, 4);
        }

        if ($id) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, slug=?, description=?, short_desc=?, category=?, badge=?, icon=?, image=?, features=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $shortDesc, $category, $badge, $icon, $imageName, $features, $id]);
            $productId = $id;
            // Hapus semua paket durasi lama, ganti dengan yang baru
            $pdo->prepare("DELETE FROM product_durations WHERE product_id = ?")->execute([$productId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, short_desc, category, badge, icon, image, features) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $slug, $description, $shortDesc, $category, $badge, $icon, $imageName, $features]);
            $productId = $pdo->lastInsertId();
        }

        $stmtDur = $pdo->prepare("INSERT INTO product_durations (product_id, label, price, original_price, sort_order) VALUES (?,?,?,?,?)");
        foreach ($validDurations as $i => $d) {
            $stmtDur->execute([$productId, $d['label'], $d['price'], $d['original'], $i + 1]);
        }

        header('Location: products.php?saved=1');
        exit;
    } else {
        // Kalau gambar baru sudah ter-upload tapi validasi gagal, hapus file sampahnya
        if ($newImageUploaded && $imageName) {
            @unlink(__DIR__ . '/../assets/img/' . $imageName);
            $imageName = $p['image'] ?? null;
        }
        // Update data produk dengan input POST biar form terisi ulang
        if ($p) {
            $p['name']        = $name;
            $p['description'] = $description;
            $p['short_desc']  = $shortDesc;
            $p['category']    = $category;
            $p['badge']       = $badge;
            $p['icon']        = $icon;
            $p['features']    = $features;
            $p['image']       = $imageName;
        }
        // Biar form tetap keisi ulang kalau ada error validasi
        $existingDurations = [];
        foreach ($durLabels as $i => $label) {
            $existingDurations[] = [
                'label' => $label,
                'price' => $durPrices[$i] ?? '',
                'original_price' => $durOriginals[$i] ?? '',
            ];
        }
    }
}

$pageTitle = 'Form Produk - Admin HanzelStore';
require_once 'includes/admin_header.php';

// Kalau belum ada paket sama sekali (produk baru), kasih 1 baris kosong buat mulai
if (empty($existingDurations)) {
    $existingDurations = [['label' => '', 'price' => '', 'original_price' => '']];
}
?>

<div class="topbar">
    <div>
        <h1><?= $id ? '✏ Edit Produk' : '+ Tambah Produk' ?></h1>
        <p><?= $id ? 'Perbarui informasi produk' : 'Isi detail produk baru' ?></p>
    </div>
    <a href="products.php" class="btn btn-outline">← Kembali</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo "⚠ $e<br>"; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="form-card" style="max-width:760px">

    <div class="form-group">
        <label class="form-label">Nama Produk *</label>
        <input type="text" name="name" class="form-control" placeholder="Contoh: NexGen UI Kit Pro"
               value="<?= htmlspecialchars($p['name'] ?? $_POST['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label class="form-label">Deskripsi Singkat</label>
        <input type="text" name="short_desc" class="form-control" placeholder="Ringkasan 1 kalimat"
               value="<?= htmlspecialchars($p['short_desc'] ?? $_POST['short_desc'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Deskripsi Lengkap</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Deskripsi detail produk"><?= htmlspecialchars($p['description'] ?? $_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Kategori *</label>
            <input type="text" name="category" class="form-control" placeholder="Design, Developer Tools, dll"
                   value="<?= htmlspecialchars($p['category'] ?? $_POST['category'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Badge (opsional)</label>
            <input type="text" name="badge" class="form-control" placeholder="BESTSELLER, HOT, NEW, dll"
                   value="<?= htmlspecialchars($p['badge'] ?? $_POST['badge'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Icon Emoji (fallback jika tanpa foto)</label>
        <input type="text" name="icon" class="form-control" placeholder="📦"
               value="<?= htmlspecialchars($p['icon'] ?? $_POST['icon'] ?? '📦') ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Fitur Unggulan (pisahkan dengan tanda | )</label>
        <textarea name="features" class="form-control" rows="2" placeholder="Fitur A|Fitur B|Fitur C"><?= htmlspecialchars($p['features'] ?? $_POST['features'] ?? '') ?></textarea>
        <div class="form-hint">Contoh: Dark mode|Update gratis|Dokumentasi lengkap</div>
    </div>

    <!-- Paket Durasi -->
    <div class="form-group">
        <label class="form-label">Paket Durasi & Harga *</label>
        <div class="form-hint" style="margin-bottom:10px">Tambahkan minimal 1 paket. Harga Coret bersifat opsional (buat nunjukin diskon).</div>

        <div id="durRows">
            <?php foreach ($existingDurations as $d): ?>
            <div class="dur-row" style="background:var(--bg);border:1px solid var(--borderl);border-radius:8px;padding:12px;margin-bottom:10px">
                <div style="display:grid;grid-template-columns:1.6fr 1fr 1fr auto;gap:8px;align-items:center">
                    <input type="text" name="duration_label[]" class="form-control" placeholder="Contoh: 1 Minggu" value="<?= htmlspecialchars($d['label']) ?>">
                    <input type="number" name="duration_price[]" class="form-control" placeholder="Harga (Rp)" value="<?= htmlspecialchars($d['price']) ?>">
                    <input type="number" name="duration_original[]" class="form-control" placeholder="Harga coret (opsional)" value="<?= htmlspecialchars($d['original_price']) ?>">
                    <button type="button" onclick="removeDurRow(this)" class="btn btn-danger btn-sm" style="flex-shrink:0">✕</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addDurRow()" class="btn btn-outline btn-sm" style="margin-top:6px">+ Tambah Paket Durasi</button>
    </div>

    <div class="form-group">
        <label class="form-label">Foto Produk (JPG/PNG/WEBP, maks 2MB)</label>
        <?php if (!empty($p['image'])): ?>
            <div style="margin-bottom:10px">
                <img src="<?= SITE_URL ?>/assets/img/<?= htmlspecialchars($p['image']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                <div class="form-hint">Foto saat ini - upload baru untuk mengganti</div>
            </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px">
        💾 <?= $id ? 'Simpan Perubahan' : 'Tambah Produk' ?>
    </button>
</form>

<script>
function addDurRow() {
    const wrap = document.getElementById('durRows');
    const row = document.createElement('div');
    row.className = 'dur-row';
    row.style.cssText = 'background:var(--bg);border:1px solid var(--borderl);border-radius:8px;padding:12px;margin-bottom:10px';
    row.innerHTML = `
        <div style="display:grid;grid-template-columns:1.6fr 1fr 1fr auto;gap:8px;align-items:center">
            <input type="text" name="duration_label[]" class="form-control" placeholder="Contoh: 1 Bulan">
            <input type="number" name="duration_price[]" class="form-control" placeholder="Harga (Rp)">
            <input type="number" name="duration_original[]" class="form-control" placeholder="Harga coret (opsional)">
            <button type="button" onclick="removeDurRow(this)" class="btn btn-danger btn-sm" style="flex-shrink:0">✕</button>
        </div>
    `;
    wrap.appendChild(row);
}
function removeDurRow(btn) {
    const wrap = document.getElementById('durRows');
    if (wrap.children.length > 1) {
        btn.closest('.dur-row').remove();
    } else {
        alert('Minimal harus ada 1 paket durasi.');
    }
}
</script>

<?php require_once 'includes/admin_footer.php'; ?>
