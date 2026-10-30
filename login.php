<?php
require __DIR__ . '/config.php';
if (current_user()) redirect('/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT id, name, username, password_hash, role, is_active FROM users WHERE username=? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>(int)$user['id'], 'name'=>$user['name'], 'username'=>$user['username'], 'role'=>$user['role']];
        audit($pdo, 'login', null, 'Login berhasil');
        redirect('/index.php');
    }
    $error = 'Username atau password salah, atau akun dinonaktifkan.';
}
layout_start('Login');
?>
<div class="auth-card">
  <p class="eyebrow">AKSES INTERNAL</p><h1>Masuk ke sistem</h1>
  <p class="muted">Gunakan akun yang dibuat oleh admin kantor.</p>
  <?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>Username<input name="username" required autocomplete="username" maxlength="60"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="button full" type="submit">Login</button>
  </form>
</div>
<?php layout_end(); ?>
