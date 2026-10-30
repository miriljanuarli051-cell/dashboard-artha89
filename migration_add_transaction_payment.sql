USE monitoring_pembelian;

ALTER TABLE transactions
  ADD COLUMN payment_status VARCHAR(120) NOT NULL AFTER purchase_date,
  ADD COLUMN account_number VARCHAR(50) NULL AFTER payment_status;
