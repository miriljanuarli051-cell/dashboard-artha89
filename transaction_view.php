<?php
require __DIR__ . '/config.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('ID transaksi tidak valid.'); }
$stmt = $pdo->prepare("SELECT t.*,u.name AS staff_name FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.id=?");
$stmt->execute([$id]); $tx = $stmt->fetch();
if (!$tx || (current_user()['role'] !== 'admin' && (int)$tx['user_id'] !== (int)current_user()['id'])) { http_response_code(404); exit('Transaksi tidak ditemukan.'); }
$stmt = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id=? ORDER BY id");
$stmt->execute([$id]); $items = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM receipts WHERE transaction_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$id]); $receipt = $stmt->fetch();
layout_start('Detail Transaksi');
show_flash();
?>
<div class="page-head"><div><p class="eyebrow">DETAIL TRANSAKSI</p><h1><?= e($tx['transaction_number']) ?></h1><p class="muted">Dibuat oleh <?= e($tx['staff_name']) ?> · <?= e($tx['purchase_date']) ?></p></div><span class="badge <?= e($tx['status']) ?>"><?= e(strtoupper($tx['status'])) ?></span></div>
<div class="split">
<section class="panel"><h2>Informasi pembelian</h2><dl class="details">
<dt>Pemasok</dt><dd><?= e($tx['supplier']) ?></dd><dt>Nomor nota</dt><dd><?= e($tx['receipt_number']) ?></dd><dt>Status pembayaran</dt><dd><?= e($tx['payment_status'] ?? '-') ?></dd><dt>No. rekening</dt><dd><?= e($tx['account_number'] ?? '-') ?></dd><dt>Total pembelian</dt><dd><strong><?= money($tx['total_amount']) ?></strong></dd><dt>Catatan staf</dt><dd><?= nl2br(e($tx['notes'] ?? '-')) ?></dd><dt>Catatan admin</dt><dd><?= nl2br(e($tx['review_note'] ?? '-')) ?></dd>
</dl></section>
<section class="panel"><h2>Bukti nota</h2>
<?php if($receipt): ?><p>File: <?= e($receipt['original_filename']) ?></p><p><a class="button secondary" href="<?= BASE_URL ?>/receipt.php?id=<?= (int)$receipt['id'] ?>">Lihat / unduh nota</a></p><?php else: ?><p class="empty">Belum ada nota.</p><?php endif; ?>
</section></div>
<section class="panel"><h2>Rincian barang</h2><div class="table-wrap"><table><thead><tr><th>Barang</th><th>Jumlah</th><th>Harga satuan</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach($items as $it): ?><tr><td><?= e($it['item_name']) ?></td><td><?= e((string)$it['quantity']) ?> <?= e($it['unit_snapshot']) ?></td><td><?= money($it['unit_price']) ?></td><td><?= money($it['subtotal']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php if(current_user()['role']==='admin' && $tx['status']==='pending'): ?>
<section class="panel"><h2>Verifikasi admin</h2><form method="post" action="<?= BASE_URL ?>/review.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="transaction_id" value="<?= (int)$tx['id'] ?>">
<label>Catatan pemeriksaan<textarea name="review_note" rows="3" maxlength="2000" placeholder="Alasan persetujuan, revisi, atau penolakan"></textarea></label>
<div class="actions"><button class="button" name="decision" value="approved">Setujui</button><button class="button secondary" name="decision" value="revision">Minta revisi</button><button class="button danger" name="decision" value="rejected">Tolak</button></div>
</form></section>
<?php endif; ?>
<p><a href="<?= BASE_URL ?>/transactions.php">← Kembali ke transaksi</a></p>
<?php layout_end(); ?>
