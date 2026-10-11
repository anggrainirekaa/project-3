<?php
require __DIR__ . '/../../config/init.php';
require_login();
$pdo = db();
$type = in_array($_GET['type'] ?? '', ['in', 'out'], true) ? $_GET['type'] : '';
$q    = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = []; $params = [];
if ($type) { $where[] = 'st.type = ?'; $params[] = $type; }
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$wsql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$base = "FROM stock_transactions st JOIN products p ON p.id = st.product_id JOIN users u ON u.id = st.user_id $wsql";

$c = $pdo->prepare("SELECT COUNT(*) $base"); $c->execute($params);
$total = (int) $c->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = min($page, $pages);
$off = ($page - 1) * PER_PAGE;
$s = $pdo->prepare("SELECT st.*, p.sku, p.name AS product, u.name AS user $base ORDER BY st.id DESC LIMIT " . PER_PAGE . " OFFSET $off");
$s->execute($params);
$rows = $s->fetchAll();
$query = ['type' => $type, 'q' => $q];
$title = 'Histori Stok';
require __DIR__ . '/../../views/header.php';
?>
<h1>Histori Transaksi Stok</h1>
<p class="sub">Seluruh barang masuk dan keluar tersimpan permanen. Total: <?= $total ?> transaksi.</p>
<form class="toolbar" method="get">
  <input name="q" placeholder="Cari produk / SKU..." value="<?= e($q) ?>">
  <select name="type"><option value="">Semua jenis</option><option value="in" <?= $type==='in'?'selected':'' ?>>Masuk</option><option value="out" <?= $type==='out'?'selected':'' ?>>Keluar</option></select>
  <button class="btn">Terapkan</button><a class="btn gray" href="history.php">Reset</a>
  <span style="flex:1"></span>
  <a class="btn green" href="in.php">+ Barang Masuk</a><a class="btn danger" href="out.php">- Barang Keluar</a>
</form>
<table>
  <tr><th>Waktu</th><th>SKU</th><th>Produk</th><th>Jenis</th><th class="right">Qty</th><th>Catatan</th><th>Oleh</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['created_at']) ?></td><td><?= e($r['sku']) ?></td><td><?= e($r['product']) ?></td>
      <td><span class="badge <?= e($r['type']) ?>"><?= $r['type']==='in'?'Masuk':'Keluar' ?></span></td>
      <td class="right"><?= (int)$r['quantity'] ?></td><td class="muted"><?= e($r['note'] ?? '-') ?></td><td><?= e($r['user']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="muted">Belum ada transaksi.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../../views/pagination.php'; ?>
<?php require __DIR__ . '/../../views/footer.php';
