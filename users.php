<?php
require __DIR__ . '/config.php';
require_role('admin');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || !preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $username) || strlen($password) < 12) {
        $error = 'Periksa nama, username, dan password.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, 'staff')");
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            audit($pdo, 'create_staff', null, 'Membuat akun staf: ' . $username);
            flash('Akun staf berhasil dibuat.', 'success');
            redirect('/users.php');
        } catch (PDOException $e) { $error = 'Username sudah digunakan atau akun gagal dibuat.'; }
    }
}
$users = $pdo->query("SELECT id,name,username,role,is_active,created_at FROM users ORDER BY created_at DESC")->fetchAll();
layout_start('Pengguna');
show_flash();
?>
<div class="page-head"><div><p class="eyebrow">ADMIN</p><h1>Pengguna</h1><p class="muted">Buat akun unik untuk setiap staf.</p></div></div>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<div class="split">
<section class="panel"><h2>Tambah akun staf</h2><form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label>Nama lengkap<input name="name" required maxlength="120"></label>
<label>Username<input name="username" required minlength="3" maxlength="60"></label>
<label>Password sementara (min. 12 karakter)<input type="password" name="password" required minlength="12"></label>
<button class="button" type="submit">Buat akun staf</button></form></section>
<section class="panel"><h2>Daftar akun</h2><div class="table-wrap"><table><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th></tr></thead><tbody>
<?php foreach($users as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['role']) ?></td><td><?= (int)$u['is_active']?'Aktif':'Nonaktif' ?></td></tr><?php endforeach; ?>
</tbody></table></div></section></div>
<?php layout_end(); ?>
