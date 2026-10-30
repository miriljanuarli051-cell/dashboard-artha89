<?php
require __DIR__ . '/config.php';
require_login();
$user = current_user();
if ($user['role'] === 'admin') {
    $stmt = $pdo->query("SELECT t.*,u.name AS staff_name FROM transactions t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT t.*,u.name AS staff_name FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.user_id=? ORDER BY t.created_at DESC");
    $stmt->execute([$user['id']]);
}
$rows = $stmt->fetchAll();
layout_start('Transaksi');
show_flash();
?>
<div class="page-head"><div><p class="eyebrow">PEMBELIAN</p><h1><?= $user['role']==='admin'?'Semua transaksi':'Transaksi saya' ?></h1><p class="muted">Buka detail untuk memeriksa bukti dan status.</p></div><a class="button" href="<?= BASE_URL ?>/transaction_create.php">+ Tambah pembelian</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>No. transaksi</th><th>Tanggal</th><th>Staf</th><th>Pemasok</th><th>No. nota</th><th>Total</th><th>Status pembayaran</th><th>Status verifikasi</th><th></th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['transaction_number']) ?></td><td><?= e($r['purchase_date']) ?></td><td><?= e($r['staff_name']) ?></td><td><?= e($r['supplier']) ?></td><td><?= e($r['receipt_number']) ?></td><td><?= money($r['total_amount']) ?></td><td><?= e($r['payment_status']) ?></td><td><span class="badge <?= e($r['status']) ?>"><?= e(strtoupper($r['status'])) ?></span></td><td><a href="<?= BASE_URL ?>/transaction_view.php?id=<?= (int)$r['id'] ?>">Detail</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="9" class="empty">Belum ada transaksi.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php layout_end(); ?>
