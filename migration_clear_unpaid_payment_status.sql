USE monitoring_pembelian;

UPDATE users
SET payment_status = NULL
WHERE LOWER(TRIM(payment_status)) IN ('unpaid', 'belum dibayar');

UPDATE transactions
SET payment_status = ''
WHERE LOWER(TRIM(payment_status)) IN ('unpaid', 'belum dibayar');
