<?php
require __DIR__ . '/../../config/init.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('products/index.php'); }
csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
$pdo = db();

// Business rule: produk yang memiliki histori tidak boleh dihapus sembarangan.
$h = $pdo->prepare('SELECT COUNT(*) FROM stock_transactions WHERE product_id = ?');
$h->execute([$id]);
if ((int) $h->fetchColumn() > 0) {
    flash('Produk tidak dapat dihapus karena sudah memiliki histori transaksi stok.', 'error');
    redirect('products/index.php');
}
$pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
flash('Produk berhasil dihapus.');
redirect('products/index.php');
