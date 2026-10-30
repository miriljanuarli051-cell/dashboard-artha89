<?php
require __DIR__ . '/config.php';
require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT r.*,t.user_id FROM receipts r JOIN transactions t ON t.id=r.transaction_id WHERE r.id=?");
$stmt->execute([$id ?: 0]); $r = $stmt->fetch();
if (!$r || (current_user()['role'] !== 'admin' && (int)$r['user_id'] !== (int)current_user()['id'])) {
    http_response_code(404); exit('File nota tidak ditemukan.');
}
$path = RECEIPT_DIR . DIRECTORY_SEPARATOR . basename($r['stored_filename']);
if (!is_file($path)) { http_response_code(404); exit('File nota tidak tersedia.'); }
$allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
if (!in_array($r['mime_type'], $allowed, true)) { http_response_code(403); exit('Jenis file tidak diizinkan.'); }
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $r['mime_type']);
header('Content-Disposition: attachment; filename="nota-' . (int)$r['id'] . '.' . pathinfo($r['stored_filename'], PATHINFO_EXTENSION) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
