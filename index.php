<?php
require __DIR__ . '/config.php';
if (!current_user()) redirect('/login.php');
require_login();

$user = current_user();
if ($user['role'] === 'admin') {
    $total = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE status='approved'")->fetchColumn();
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
    $count = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
} else {
    $s = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM transactions WHERE user_id=? AND status='approved'");
    $s->execute([$user['id']]); $total = (float)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=? AND status IN ('pending','revision')");
    $s->execute([$user['id']]); $pending = (int)$s->fetchColumn();
    $s = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id=?");
    $s->execute([$user['id']]); $count = (int)$s->fetchColumn();
} 
layout_start('Dashboard');
show_flash();
?>
<section class="hero">
  <div><p class="eyebrow">DASHBOARD</p><h1>Halo, <?= e($user['name']) ?>.</h1>
  <p>Catat pembelian, lampirkan nota, dan pantau proses verifikasi.</p></div>
  <a class="button" href="<?= BASE_URL ?>/transaction_create.php">+ Tambah pembelian</a>
</section>
<div class="stats">
  <article class="stat"><span><?= $user['role']==='admin'?'Total transaksi disetujui':'Pengeluaran disetujui' ?></span><strong><?= money($total) ?></strong></article>
  <article class="stat"><span><?= $user['role']==='admin'?'Menunggu verifikasi':'Menunggu / revisi' ?></span><strong><?= $pending ?></strong></article>
  <article class="stat"><span><?= $user['role']==='admin'?'Total transaksi':'Total transaksi saya' ?></span><strong><?= $count ?></strong></article>
</div>
<section class="panel">
  <div class="panel-head"><h2>Transaksi terbaru</h2><a href="<?= BASE_URL ?>/transactions.php">Lihat semua</a></div>
  <?php
  if ($user['role'] === 'admin') {
      $stmt = $pdo->query("SELECT t.*, u.name AS staff_name FROM transactions t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 8");
  } else {
      $stmt = $pdo->prepare("SELECT t.*, u.name AS staff_name FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.user_id=? ORDER BY t.created_at DESC LIMIT 8");
      $stmt->execute([$user['id']]);
  }
  $rows = $stmt->fetchAll();
  ?>
  <?php if (!$rows): ?><p class="empty">Belum ada transaksi. Mulai dengan menambahkan pembelian pertama.</p>
  <?php else: ?>
  <div class="table-wrap"><table><thead><tr><th>No. Transaksi</th><th>Tanggal</th><?php if($user['role']==='admin'): ?><th>Staf</th><?php endif; ?><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach($rows as $r): ?><tr><td><?= e($r['transaction_number']) ?></td><td><?= e($r['purchase_date']) ?></td><?php if($user['role']==='admin'): ?><td><?= e($r['staff_name']) ?></td><?php endif; ?><td><?= money($r['total_amount']) ?></td><td><span class="badge <?= e($r['status']) ?>"><?= e(strtoupper($r['status'])) ?></span></td><td><a href="<?= BASE_URL ?>/transaction_view.php?id=<?= (int)$r['id'] ?>">Detail</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php layout_end(); ?>
