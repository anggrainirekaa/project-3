<?php
/**
 * Seed data contoh. Jalankan: php database/seed.php
 * - MySQL: import schema.sql dulu, lalu jalankan skrip ini.
 * - Demo SQLite: DB_DRIVER=sqlite php database/seed.php (membuat tabel otomatis).
 */
define('BASE_URL', '');
require __DIR__ . '/../config/database.php';
$pdo = db();

if ((getenv('DB_DRIVER') ?: 'mysql') === 'sqlite') {
    $pdo->exec(file_get_contents(__DIR__ . '/schema_sqlite.sql'));
} else {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['stock_transactions','product_suppliers','products','suppliers','categories','users'] as $t) { $pdo->exec("DELETE FROM $t"); }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

$pdo->beginTransaction();
$ins = $pdo->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)');
$ins->execute(['Administrator', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
$ins->execute(['Staff Gudang', 'staff', password_hash('staff123', PASSWORD_DEFAULT), 'staff']);

foreach (['Elektronik','Alat Tulis','Perabot','Makanan & Minuman','Kebersihan'] as $c) {
    $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$c]);
}
$sup = $pdo->prepare('INSERT INTO suppliers (name, phone, email, address) VALUES (?,?,?,?)');
$sup->execute(['PT Sumber Makmur', '0812-1111-2222', 'sales@sumbermakmur.co.id', 'Lhokseumawe']);
$sup->execute(['CV Aceh Jaya Abadi', '0813-3333-4444', 'info@acehjaya.co.id', 'Banda Aceh']);
$sup->execute(['UD Barokah Tani', '0852-5555-6666', 'barokah@tani.id', 'Kutacane']);
$sup->execute(['PT Nusantara Digital', '0821-7777-8888', 'cs@nusadigital.id', 'Medan']);

// sku, nama, kategori(id), harga, stok awal(dibuat lewat transaksi masuk), min_stock, suppliers
$products = [
 ['ELK-001','Mouse Wireless Logitech',1,185000,40,10,[1,4]],
 ['ELK-002','Keyboard Mechanical',1,450000,15,5,[1,4]],
 ['ELK-003','Flashdisk 64GB',1,95000,60,15,[4]],
 ['ELK-004','Monitor LED 24 inci',1,1850000,6,5,[4]],
 ['ATK-001','Pulpen Standard (box)',2,32000,120,30,[2]],
 ['ATK-002','Buku Tulis A5',2,8500,200,50,[2]],
 ['ATK-003','Kertas HVS A4 (rim)',2,58000,45,20,[2,1]],
 ['ATK-004','Spidol Whiteboard',2,12000,8,15,[2]],
 ['PRB-001','Kursi Kantor Ergonomis',3,875000,12,4,[1]],
 ['PRB-002','Meja Lipat Serbaguna',3,320000,3,5,[1]],
 ['MKN-001','Kopi Gayo 250g',4,65000,70,20,[3]],
 ['MKN-002','Teh Celup (box)',4,18000,90,25,[3]],
 ['MKN-003','Air Mineral 600ml (dus)',4,42000,55,20,[3]],
 ['KBR-001','Cairan Pembersih Lantai 1L',5,22000,35,10,[2,3]],
 ['KBR-002','Tisu Wajah (pak)',5,9500,150,40,[2]],
];
$p = $pdo->prepare('INSERT INTO products (sku, name, category_id, price, stock, min_stock) VALUES (?,?,?,?,0,?)');
$ps = $pdo->prepare('INSERT INTO product_suppliers (product_id, supplier_id) VALUES (?,?)');
$tx = $pdo->prepare('INSERT INTO stock_transactions (product_id, user_id, type, quantity, note) VALUES (?,?,?,?,?)');
$up = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
foreach ($products as $row) {
    [$sku,$name,$cat,$price,$stock,$min,$sups] = $row;
    $p->execute([$sku,$name,$cat,$price,$min]);
    $pid = (int) $pdo->lastInsertId();
    foreach ($sups as $s) { $ps->execute([$pid,$s]); }
    $tx->execute([$pid, 1, 'in', $stock, 'Stok awal']);
    $up->execute([$stock, $pid]);
}
// beberapa transaksi keluar
$out = [[1,12,'Penjualan toko'],[3,20,'Order kantor'],[5,40,'Distribusi'],[8,3,'Pemakaian kelas'],[11,15,'Penjualan toko'],[2,4,'Penjualan online']];
$chk = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
foreach ($out as [$pid,$q,$note]) { $tx->execute([$pid,2,'out',$q,$note]); $chk->execute([$q,$pid]); }
$pdo->commit();
echo "Seed selesai.\nadmin / admin123\nstaff / staff123\n";
