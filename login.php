<?php
require __DIR__ . '/../config/init.php';
if (current_user()) { redirect('dashboard.php'); }

$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors = collect_errors([
        v_required('Username', $username),
        v_required('Password', $password),
    ]);
    if (!$errors && !attempt_login($username, $password)) {
        $errors[] = 'Username atau password salah.';
    }
    if (!$errors) { redirect('dashboard.php'); }
}
$title = 'Login';
require __DIR__ . '/../views/header.php';
?>
<div class="login-wrap">
  <div class="card">
    <h1>&#128230; Inventory IMS</h1>
    <p class="sub">Silakan login untuk melanjutkan.</p>
    <?php if ($errors): ?><div class="errors"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="form" autocomplete="off">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= e($username) ?>">
      <label for="password">Password</label>
      <input id="password" type="password" name="password">
      <p><button class="btn" style="width:100%">Login</button></p>
    </form>
    <p class="hint">Akun demo: <strong>admin</strong> / admin123 &nbsp;|&nbsp; <strong>staff</strong> / staff123</p>
  </div>
</div>
<?php require __DIR__ . '/../views/footer.php';
