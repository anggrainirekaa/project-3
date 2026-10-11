<?php
require __DIR__ . '/../../config/init.php';
require_admin();   // Staff tidak boleh mengakses administrasi user.
$pdo = db();
$errors = [];
$f = ['name' => '', 'username' => '', 'role' => 'staff'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) current_user()['id']) {
            flash('Anda tidak dapat menghapus akun yang sedang dipakai.', 'error');
        } else {
            try { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); flash('User dihapus.'); }
            catch (PDOException $ex) { flash('User tidak dapat dihapus karena memiliki histori transaksi.', 'error'); }
        }
        redirect('users/index.php');
    }
    $f['name'] = trim($_POST['name'] ?? ''); $f['username'] = trim($_POST['username'] ?? '');
    $f['role'] = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';
    $pw = $_POST['password'] ?? '';
    $errors = collect_errors([
        v_required('Nama', $f['name']), v_required('Username', $f['username']), v_maxlen('Username', $f['username'], 50),
        mb_strlen($pw) < 6 ? 'Password minimal 6 karakter.' : null,
    ]);
    if (!$errors) {
        $d = $pdo->prepare('SELECT id FROM users WHERE username = ?'); $d->execute([$f['username']]);
        if ($d->fetch()) { $errors[] = 'Username sudah dipakai.'; }
    }
    if (!$errors) {
        $pdo->prepare('INSERT INTO users (name, username, password_hash, role) VALUES (?,?,?,?)')
            ->execute([$f['name'], $f['username'], password_hash($pw, PASSWORD_DEFAULT), $f['role']]);
        flash('User berhasil ditambahkan.');
        redirect('users/index.php');
    }
}
$rows = $pdo->query('SELECT id, name, username, role, created_at FROM users ORDER BY id')->fetchAll();
$title = 'Administrasi User';
require __DIR__ . '/../../views/header.php';
?>
<h1>Administrasi User</h1>
<p class="sub">Hanya admin yang dapat mengelola user. Password disimpan dengan hashing (password_hash).</p>
<div class="grid g2">
  <div class="card">
    <h2>Tambah User</h2>
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="form"><?= csrf_field() ?>
      <label>Nama</label><input name="name" value="<?= e($f['name']) ?>">
      <label>Username</label><input name="username" value="<?= e($f['username']) ?>">
      <label>Password</label><input type="password" name="password">
      <label>Role</label><select name="role"><option value="staff" <?= $f['role']==='staff'?'selected':'' ?>>staff</option><option value="admin" <?= $f['role']==='admin'?'selected':'' ?>>admin</option></select>
      <p><button class="btn">Tambah</button></p>
    </form>
  </div>
  <div>
    <table>
      <tr><th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><?= e($r['username']) ?></td><td><span class="badge <?= e($r['role']) ?>"><?= e($r['role']) ?></span></td>
        <td><form method="post" style="display:inline" onsubmit="return confirm('Hapus user ini?')"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small danger">Hapus</button></form></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../../views/footer.php';
