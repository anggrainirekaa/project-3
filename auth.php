<?php
/** Autentikasi, otorisasi, flash message, dan CSRF. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login.php');
    }
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Staff tidak boleh mengakses administrasi user. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        $title = 'Akses Ditolak';
        require __DIR__ . '/../views/header.php';
        echo '<div class="card error-page"><h2>403 - Akses Ditolak</h2>'
           . '<p>Halaman ini hanya dapat diakses oleh <strong>admin</strong>. Role Anda: <strong>'
           . e(current_user()['role']) . '</strong>.</p>'
           . '<a class="btn" href="' . e(url('dashboard.php')) . '">Kembali ke Dashboard</a></div>';
        require __DIR__ . '/../views/footer.php';
        exit;
    }
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, username, password_hash, role FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        unset($u['password_hash']);
        $_SESSION['user'] = $u;
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('Token CSRF tidak valid. Muat ulang halaman dan coba lagi.');
    }
}
