USE monitoring_pembelian;

ALTER TABLE users
  MODIFY COLUMN payment_status VARCHAR(120) NULL;

ALTER TABLE transactions
  MODIFY COLUMN payment_status VARCHAR(120) NOT NULL;

UPDATE users
SET payment_status = CASE payment_status
  WHEN 'unpaid' THEN NULL
  WHEN 'paid' THEN 'Sudah dibayar'
  ELSE payment_status
END;

UPDATE transactions
SET payment_status = CASE payment_status
  WHEN 'unpaid' THEN ''
  WHEN 'paid' THEN 'Sudah dibayar'
  ELSE payment_status
END;
