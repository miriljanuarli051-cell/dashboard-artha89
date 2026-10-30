<?php
require __DIR__ . '/config.php';
require_role('admin');
$rows = $pdo->query("SELECT a.*,u.name AS actor,t.transaction_number FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id LEFT JOIN transactions t ON t.id=a.transaction_id ORDER BY a.created_at DESC LIMIT 300")->fetchAll();
layout_start('Audit Log');
?>
<div class="page-head"><div><p class="eyebrow">ADMIN</p><h1>Audit Log</h1><p class="muted">300 aktivitas terbaru.</p></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Transaksi</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= e($r['created_at']) ?></td><td><?= e($r['actor'] ?? 'Akun dihapus') ?></td><td><?= e($r['action']) ?></td><td><?= e($r['transaction_number'] ?? '-') ?></td><td><?= e($r['details'] ?? '') ?></td><td><?= e($r['ip_address'] ?? '') ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6">Belum ada aktivitas.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php layout_end(); ?>
