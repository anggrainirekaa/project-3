<?php
require __DIR__ . '/../../config/init.php';
require_login();
$pdo = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$sups = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();

$data = ['sku' => '', 'name' => '', 'category_id' => '', 'price' => '', 'min_stock' => '5', 'stock' => 0];
$selSup = [];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $data = $st->fetch() ?: redirect('products/index.php');
    $ss = $pdo->prepare('SELECT supplier_id FROM product_suppliers WHERE product_id = ?');
    $ss->execute([$id]);
    $selSup = array_map('intval', $ss->fetchAll(PDO::FETCH_COLUMN));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $data['sku']         = strtoupper(trim($_POST['sku'] ?? ''));
    $data['name']        = trim($_POST['name'] ?? '');
    $data['category_id'] = $_POST['category_id'] ?? '';
    $data['price']       = trim($_POST['price'] ?? '');
    $data['min_stock']   = trim($_POST['min_stock'] ?? '');
    $selSup = array_map('intval', $_POST['suppliers'] ?? []);

    $errors = collect_errors([
        v_required('SKU', $data['sku']), v_maxlen('SKU', $data['sku'], 40),
        v_required('Nama produk', $data['name']), v_maxlen('Nama produk', $data['name'], 150),
        v_required('Kategori', (string) $data['category_id']),
        v_number_min('Harga', $data['price'], 0),
        v_int_min('Stok minimum', $data['min_stock'], 0),
    ]);
    // SKU harus unik (validasi server)
    if ($data['sku'] !== '') {
        $u = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
        $u->execute([$data['sku'], $id]);
        if ($u->fetch()) { $errors[] = 'SKU "' . $data['sku'] . '" sudah digunakan produk lain.'; }
    }
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($id) {
                $pdo->prepare('UPDATE products SET sku=?, name=?, category_id=?, price=?, min_stock=? WHERE id=?')
                    ->execute([$data['sku'], $data['name'], (int)$data['category_id'], $data['price'], (int)$data['min_stock'], $id]);
            } else {
                $pdo->prepare('INSERT INTO products (sku, name, category_id, price, stock, min_stock) VALUES (?,?,?,?,0,?)')
                    ->execute([$data['sku'], $data['name'], (int)$data['category_id'], $data['price'], (int)$data['min_stock']]);
                $id = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM product_suppliers WHERE product_id = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO product_suppliers (product_id, supplier_id) VALUES (?,?)');
            foreach (array_unique($selSup) as $sid) { $ins->execute([$id, $sid]); }
            $pdo->commit();
            flash('Produk berhasil disimpan.');
            redirect('products/index.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Gagal menyimpan produk (kemungkinan SKU duplikat atau relasi tidak valid).';
        }
    }
}
$title = $id ? 'Edit Produk' : 'Tambah Produk';
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($title) ?></h1>
<p class="sub">Stok awal dikelola lewat menu Barang Masuk agar tercatat di histori.</p>
<div class="card">
<?php if ($errors): ?><div class="errors"><strong>Periksa kembali input:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="row">
    <div><label>SKU</label><input name="sku" value="<?= e($data['sku']) ?>"></div>
    <div><label>Nama Produk</label><input name="name" value="<?= e($data['name']) ?>"></div>
  </div>
  <div class="row">
    <div><label>Kategori</label>
      <select name="category_id"><option value="">-- pilih --</option>
        <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$data['category_id'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div><label>Harga (Rp)</label><input name="price" value="<?= e($data['price']) ?>"></div>
  </div>
  <label>Stok Minimum</label><input name="min_stock" value="<?= e($data['min_stock']) ?>">
  <label>Supplier (many-to-many)</label>
  <div class="checks">
    <?php foreach ($sups as $s): ?>
      <label><input type="checkbox" name="suppliers[]" value="<?= (int)$s['id'] ?>" <?= in_array((int)$s['id'], $selSup, true) ? 'checked' : '' ?>> <?= e($s['name']) ?></label>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:18px"><button class="btn">Simpan</button> <a class="btn gray" href="index.php">Batal</a></p>
</form>
</div>
<?php require __DIR__ . '/../../views/footer.php';
