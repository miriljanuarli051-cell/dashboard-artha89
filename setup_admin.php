<?php
require __DIR__ . '/config.php';
$count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$message = '';
if ($count > 0) {
    exit('Admin sudah dibuat. Demi keamanan, hapus file setup_admin.php dari folder aplikasi.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || !preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $username) || strlen($password) < 12) {
        $message = 'Isi nama, username 3–60 karakter (huruf/angka/._-), dan password minimal 12 karakter.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, 'admin')");
        try {
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            exit('Admin berhasil dibuat. Hapus file setup_admin.php sekarang, lalu login di login.php.');
        } catch (PDOException $e) {
            $message = 'Gagal membuat admin. Pastikan username belum digunakan.';
        }
    }
}
layout_start('Setup Admin');
?>
<div class="auth-card">
<h1>Buat admin pertama</h1>
<p class="muted">Halaman setup sementara. Setelah berhasil, hapus file setup_admin.php.</p>
<?php if($message): ?><div class="alert error"><?= e($message) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Nama admin<input name="name" required maxlength="120"></label>
<label>Username<input name="username" required minlength="3" maxlength="60"></label>
<label>Password (minimal 12 karakter)<input type="password" name="password" required minlength="12"></label>
<button class="button full" type="submit">Buat admin</button>
</form></div>
<?php layout_end(); ?>
