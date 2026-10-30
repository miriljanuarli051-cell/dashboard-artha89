<?php
require __DIR__ . '/config.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Metode tidak diizinkan.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$decision = (string)($_POST['decision'] ?? '');
$note = trim((string)($_POST['review_note'] ?? ''));
if (!$id || !in_array($decision, ['approved','revision','rejected'], true)) { flash('Data verifikasi tidak valid.', 'error'); redirect('/transactions.php'); }
$stmt = $pdo->prepare("SELECT status,transaction_number FROM transactions WHERE id=?");
$stmt->execute([$id]); $tx = $stmt->fetch();
if (!$tx || $tx['status'] !== 'pending') { flash('Transaksi tidak ditemukan atau bukan lagi menunggu verifikasi.', 'error'); redirect('/transactions.php'); }
if ($decision !== 'approved' && $note === '') { flash('Isi catatan untuk revisi atau penolakan.', 'error'); redirect('/transaction_view.php?id=' . $id); }
$stmt = $pdo->prepare("UPDATE transactions SET status=?,review_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='pending'");
$stmt->execute([$decision,$note,current_user()['id'],$id]);
audit($pdo,'review_transaction',$id,"Status: {$decision}; catatan: {$note}");
flash('Keputusan verifikasi tersimpan.', 'success');
redirect('/transaction_view.php?id=' . $id);
