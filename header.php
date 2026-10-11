<?php
$u = current_user();
$title = $title ?? 'Inventory';
$flash = flash();
$here = $_SERVER['SCRIPT_NAME'] ?? '';
function nav_active(string $needle): string { global $here; return strpos($here, $needle) !== false ? ' class="active"' : ''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - Inventory Management System</title>
<link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<?php if ($u): ?>
<header class="topbar">
  <div class="brand">&#128230; Inventory IMS</div>
  <nav>
    <a<?= nav_active('dashboard') ?> href="<?= e(url('dashboard.php')) ?>">Dashboard</a>
    <a<?= nav_active('/products/') ?> href="<?= e(url('products/index.php')) ?>">Produk</a>
    <a<?= nav_active('/categories/') ?> href="<?= e(url('categories/index.php')) ?>">Kategori</a>
    <a<?= nav_active('/suppliers/') ?> href="<?= e(url('suppliers/index.php')) ?>">Supplier</a>
    <a<?= nav_active('/stock/') ?> href="<?= e(url('stock/history.php')) ?>">Stok</a>
    <?php if (is_admin()): ?><a<?= nav_active('/users/') ?> href="<?= e(url('users/index.php')) ?>">User</a><?php endif; ?>
  </nav>
  <div class="userbox">
    <span><?= e($u['name']) ?> <em class="badge <?= e($u['role']) ?>"><?= e($u['role']) ?></em></span>
    <a class="btn small ghost" href="<?= e(url('logout.php')) ?>">Logout</a>
  </div>
</header>
<?php endif; ?>
<main class="container">
<?php if ($flash): ?>
  <div class="alert <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
