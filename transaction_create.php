<?php
require __DIR__ . '/config.php';
require_login();
$error = '';
$profileStmt = $pdo->prepare("SELECT payment_status,account_number FROM users WHERE id=?");
$profileStmt->execute([current_user()['id']]);
$paymentProfile = $profileStmt->fetch();
$paymentStatus = $paymentProfile['payment_status'] ?? '';
$accountNumber = $paymentProfile['account_number'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $supplier = trim((string)($_POST['supplier'] ?? ''));
    $receiptNo = trim((string)($_POST['receipt_number'] ?? ''));
    $purchaseDate = (string)($_POST['purchase_date'] ?? '');
    $paymentStatus = trim((string)($_POST['payment_status'] ?? ''));
    $accountNumber = trim((string)($_POST['account_number'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $itemNames = $_POST['item_name'] ?? [];
    $units = $_POST['unit'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['unit_price'] ?? [];
    $file = $_FILES['receipt'] ?? null;
    if ($supplier === '' || $receiptNo === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)
        || preg_match('/^.{1,120}$/us', $paymentStatus) !== 1 || preg_match('/^.{0,50}$/us', $accountNumber) !== 1
        || !is_array($itemNames) || count($itemNames) < 1 || !is_array($units) || !is_array($quantities) || !is_array($prices)
        || count($itemNames) !== count($units) || count($itemNames) !== count($quantities) || count($itemNames) !== count($prices)) {
        $error = 'Lengkapi pemasok, nomor nota, tanggal, status pembayaran, dan minimal satu barang. Status pembayaran maksimal 120 karakter dan nomor rekening maksimal 50 karakter.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_RECEIPT_BYTES) {
        $error = 'Foto nota wajib diunggah dan maksimal 5 MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
        if (!isset($allowed[$mime])) {
            $error = 'Format nota harus JPG, PNG, WEBP, atau PDF.';
        } else {
            $items = []; $total = 0.0;
            foreach ($itemNames as $i => $rawName) {
                $itemName = trim((string)$rawName);
                $unit = trim((string)($units[$i] ?? ''));
                $qty = filter_var($quantities[$i] ?? null, FILTER_VALIDATE_FLOAT);
                $price = filter_var($prices[$i] ?? null, FILTER_VALIDATE_FLOAT);
                if ($itemName === '' || preg_match('/^.{1,160}$/us', $itemName) !== 1 || $unit === '' || preg_match('/^.{1,40}$/us', $unit) !== 1 || $qty === false || $qty <= 0 || $price === false || $price < 0) {
                    $error = 'Nama barang, satuan, jumlah, atau harga tidak valid.';
                    break;
                }
                $subtotal = round($qty * $price, 2);
                $total += $subtotal;
                $items[] = ['name'=>$itemName,'unit'=>$unit,'qty'=>$qty,'price'=>$price,'subtotal'=>$subtotal];
            }
            if ($error === '') {
                $stored = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
                $txNumber = 'TRX-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
                try {
                    if (!is_dir(RECEIPT_DIR) && !mkdir(RECEIPT_DIR, 0750, true)) throw new RuntimeException('Folder nota tidak tersedia.');
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO transactions (transaction_number,user_id,supplier,receipt_number,purchase_date,payment_status,account_number,notes,status,total_amount) VALUES (?,?,?,?,?,?,?,?,'pending',?)");
                    $stmt->execute([$txNumber,current_user()['id'],$supplier,$receiptNo,$purchaseDate,$paymentStatus,$accountNumber !== '' ? $accountNumber : null,$notes,$total]);
                    $txId = (int)$pdo->lastInsertId();
                    $itemStmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id,item_name,unit_snapshot,quantity,unit_price,subtotal) VALUES (?,?,?,?,?,?)");
                    foreach ($items as $it) $itemStmt->execute([$txId,$it['name'],$it['unit'],$it['qty'],$it['price'],$it['subtotal']]);
                    if (!move_uploaded_file($file['tmp_name'], RECEIPT_DIR . DIRECTORY_SEPARATOR . $stored)) throw new RuntimeException('Gagal menyimpan file nota.');
                    $stmt = $pdo->prepare("INSERT INTO receipts (transaction_id,stored_filename,original_filename,mime_type,uploaded_by) VALUES (?,?,?,?,?)");
                    $stmt->execute([$txId,$stored,basename((string)$file['name']),$mime,current_user()['id']]);
                    audit($pdo,'submit_transaction',$txId,"Transaksi {$txNumber}; total {$total}; nomor nota {$receiptNo}");
                    $pdo->commit();
                    flash('Transaksi berhasil diajukan untuk verifikasi. Nomor: ' . $txNumber,'success');
                    redirect('/transactions.php');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    if (isset($stored) && is_file(RECEIPT_DIR . DIRECTORY_SEPARATOR . $stored)) @unlink(RECEIPT_DIR . DIRECTORY_SEPARATOR . $stored);
                    $error = 'Transaksi gagal disimpan. Periksa izin folder storage/receipts dan konfigurasi database.';
                }
            }
        }
    }
}
layout_start('Tambah Pembelian');
?>
<div class="page-head"><div><p class="eyebrow">TRANSAKSI</p><h1>Tambah pembelian</h1><p class="muted">Foto nota, nomor nota, dan rincian barang wajib diisi.</p></div></div>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="panel">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-grid">
<label>Nama toko / pemasok<input name="supplier" required maxlength="160"></label>
<label>Nomor nota<input name="receipt_number" required maxlength="100"></label>
<label>Tanggal pembelian<input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" required></label>
<label>Status pembayaran<input name="payment_status" required maxlength="120" value="<?= e($paymentStatus) ?>" placeholder="Isi status pembayaran"></label>
<label>No. rekening<input name="account_number" maxlength="50" value="<?= e($accountNumber) ?>"></label>
<label>Foto nota (JPG, PNG, WEBP atau PDF; maks. 5 MB)<input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,.pdf" required></label>
</div>
<h2>Rincian barang</h2>
<div id="items">
<div class="item-row">
<label>Nama barang<input name="item_name[]" required maxlength="160"></label>
<label>Satuan<input name="unit[]" required maxlength="40" value="unit"></label>
<label>Jumlah<input type="number" name="quantity[]" min="0.001" step="0.001" value="1" required></label>
<label>Harga satuan (Rp)<input type="number" name="unit_price[]" min="0" step="0.01" required></label>
</div>
</div>
<button class="button secondary" type="button" id="addItem">+ Tambah barang lain</button>
<label>Catatan (opsional)<textarea name="notes" rows="3" maxlength="2000"></textarea></label>
<div class="actions"><button class="button" type="submit">Ajukan untuk verifikasi</button><a class="button secondary" href="<?= BASE_URL ?>/transactions.php">Batal</a></div>
</form>
<script>
const items = document.getElementById('items');
document.getElementById('addItem').addEventListener('click', () => {
 const first = items.querySelector('.item-row');
 const row = first.cloneNode(true);
 row.querySelectorAll('input').forEach(input => { input.value = input.name === 'quantity[]' ? '1' : input.name === 'unit[]' ? 'unit' : ''; });
 items.appendChild(row);
});
</script>
<?php layout_end(); ?>
