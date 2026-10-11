<?php
/**
 * Form + proses transaksi stok (masuk/keluar). Dipakai oleh in.php dan out.php.
 * Variabel wajib sebelum include: $type ('in'|'out').
 * Memakai transaction commit/rollback dan UPDATE bersyarat agar stok tidak pernah negatif.
 */
if (!isset($type) || !in_array($type, ['in', 'out'], true)) { http_response_code(404); exit('Not found'); }
require __DIR__ . '/../../config/init.php';
require_login();
$pdo = db();
$products = $pdo->query('SELECT id, sku, name, stock FROM products ORDER BY name')->fetchAll();

$errors = [];
$form = ['product_id' => (int) ($_GET['product_id'] ?? 0), 'quantity' => '', 'note' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form['product_id'] = (int) ($_POST['product_id'] ?? 0);
    $form['quantity']   = trim($_POST['quantity'] ?? '');
    $form['note']       = trim($_POST['note'] ?? '');

    $errors = collect_errors([
        $form['product_id'] > 0 ? null : 'Produk wajib dipilih.',
        v_int_min('Quantity', $form['quantity'], 1),
        v_maxlen('Catatan', $form['note'], 255),
    ]);
    if (!$errors) {
        $qty = (int) $form['quantity'];
        try {
            $pdo->beginTransaction();
            $chk = $pdo->prepare('SELECT name, stock FROM products WHERE id = ?');
            $chk->execute([$form['product_id']]);
            $prod = $chk->fetch();
            if (!$prod) { throw new RuntimeException('Produk tidak ditemukan.'); }

            if ($type === 'out') {
                // Barang keluar tidak boleh melebihi stok; stok tidak boleh negatif.
                $upd = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
                $upd->execute([$qty, $form['product_id'], $qty]);
                if ($upd->rowCount() !== 1) {
                    throw new RuntimeException("Stok tidak mencukupi. Stok {$prod['name']} saat ini {$prod['stock']}, diminta $qty.");
                }
            } else {
                $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$qty, $form['product_id']]);
            }
            $pdo->prepare('INSERT INTO stock_transactions (product_id, user_id, type, quantity, note) VALUES (?,?,?,?,?)')
                ->execute([$form['product_id'], current_user()['id'], $type, $qty, $form['note'] ?: null]);
            $pdo->commit();
            flash('Transaksi barang ' . ($type === 'in' ? 'masuk' : 'keluar') . ' berhasil dicatat.');
            redirect('stock/history.php');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = $ex->getMessage();
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = 'Transaksi gagal dan dibatalkan (rollback).';
        }
    }
}
$label = $type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
$title = $label;
require __DIR__ . '/../../views/header.php';
?>
<h1><?= e($label) ?></h1>
<p class="sub"><?= $type === 'in' ? 'Catat penerimaan barang; stok bertambah.' : 'Catat pengeluaran barang; stok berkurang dan tidak boleh melebihi stok tersedia.' ?></p>
<div class="card">
<?php if ($errors): ?><div class="errors"><strong>Transaksi tidak dapat diproses:</strong><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="form">
  <?= csrf_field() ?>
  <label>Produk</label>
  <select name="product_id"><option value="">-- pilih produk --</option>
    <?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $form['product_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['sku'] . ' - ' . $p['name'] . ' (stok: ' . $p['stock'] . ')') ?></option><?php endforeach; ?>
  </select>
  <label>Quantity</label><input name="quantity" value="<?= e($form['quantity']) ?>">
  <label>Catatan</label><input name="note" value="<?= e($form['note']) ?>" placeholder="Opsional">
  <p style="margin-top:18px"><button class="btn <?= $type === 'in' ? 'green' : 'danger' ?>">Simpan <?= e($label) ?></button> <a class="btn gray" href="history.php">Batal</a></p>
</form>
</div>
<?php require __DIR__ . '/../../views/footer.php';
