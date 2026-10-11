<?php
require __DIR__ . '/../config/init.php';
require_login();
$pdo = db();

$s = $pdo->query('SELECT COUNT(*) AS products, COALESCE(SUM(stock),0) AS units, COALESCE(SUM(price*stock),0) AS value,
                         COALESCE(SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END),0) AS low FROM products')->fetch();
$t = $pdo->query("SELECT COALESCE(SUM(CASE WHEN type='in' THEN quantity END),0) AS tin,
                         COALESCE(SUM(CASE WHEN type='out' THEN quantity END),0) AS tout FROM stock_transactions")->fetch();
$counts = $pdo->query('SELECT (SELECT COUNT(*) FROM categories) AS cats, (SELECT COUNT(*) FROM suppliers) AS sups')->fetch();

$perCat = $pdo->query('SELECT c.name, COUNT(p.id) AS n, COALESCE(SUM(p.stock),0) AS units, COALESCE(SUM(p.price*p.stock),0) AS value
                       FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id, c.name ORDER BY units DESC')->fetchAll();
$low = $pdo->query('SELECT sku, name, stock, min_stock FROM products WHERE stock <= min_stock ORDER BY stock ASC LIMIT 6')->fetchAll();
$recent = $pdo->query('SELECT st.created_at, st.type, st.quantity, p.name AS product, u.name AS user
                       FROM stock_transactions st JOIN products p ON p.id = st.product_id JOIN users u ON u.id = st.user_id
                       ORDER BY st.id DESC LIMIT 6')->fetchAll();

$title = 'Dashboard';
require __DIR__ . '/../views/header.php';
?>
<h1>Dashboard</h1>
<p class="sub">Statistik dan laporan inventory. Selamat datang, <?= e(current_user()['name']) ?>.</p>

<div class="grid g4">
  <div class="stat"><div class="n"><?= (int)$s['products'] ?></div><div class="l">Total Produk</div></div>
  <div class="stat"><div class="n"><?= number_format((int)$s['units'], 0, ',', '.') ?></div><div class="l">Total Unit Stok</div></div>
  <div class="stat ok"><div class="n"><?= e(rupiah($s['value'])) ?></div><div class="l">Nilai Persediaan</div></div>
  <div class="stat warn"><div class="n"><?= (int)$s['low'] ?></div><div class="l">Produk Stok Menipis</div></div>
  <div class="stat"><div class="n"><?= (int)$counts['cats'] ?></div><div class="l">Kategori</div></div>
  <div class="stat"><div class="n"><?= (int)$counts['sups'] ?></div><div class="l">Supplier</div></div>
  <div class="stat ok"><div class="n"><?= number_format((int)$t['tin'], 0, ',', '.') ?></div><div class="l">Total Barang Masuk</div></div>
  <div class="stat warn"><div class="n"><?= number_format((int)$t['tout'], 0, ',', '.') ?></div><div class="l">Total Barang Keluar</div></div>
</div>

<div class="grid g2" style="margin-top:18px">
  <div class="card">
    <h2>Laporan Stok per Kategori</h2>
    <table>
      <tr><th>Kategori</th><th class="right">Produk</th><th class="right">Unit</th><th class="right">Nilai</th></tr>
      <?php foreach ($perCat as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td class="right"><?= (int)$r['n'] ?></td><td class="right"><?= (int)$r['units'] ?></td><td class="right"><?= e(rupiah($r['value'])) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="card">
    <h2>Peringatan Stok Menipis</h2>
    <table>
      <tr><th>SKU</th><th>Produk</th><th class="right">Stok</th><th class="right">Min</th></tr>
      <?php foreach ($low as $r): ?>
      <tr><td><?= e($r['sku']) ?></td><td><?= e($r['name']) ?></td><td class="right"><span class="badge low"><?= (int)$r['stock'] ?></span></td><td class="right"><?= (int)$r['min_stock'] ?></td></tr>
      <?php endforeach; if (!$low): ?><tr><td colspan="4" class="muted">Semua stok aman.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<div class="card">
  <h2>Transaksi Terbaru</h2>
  <table>
    <tr><th>Waktu</th><th>Produk</th><th>Jenis</th><th class="right">Qty</th><th>Oleh</th></tr>
    <?php foreach ($recent as $r): ?>
    <tr><td><?= e($r['created_at']) ?></td><td><?= e($r['product']) ?></td>
        <td><span class="badge <?= e($r['type']) ?>"><?= $r['type'] === 'in' ? 'Masuk' : 'Keluar' ?></span></td>
        <td class="right"><?= (int)$r['quantity'] ?></td><td><?= e($r['user']) ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/../views/footer.php';
