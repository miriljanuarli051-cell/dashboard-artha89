USE monitoring_pembelian;

ALTER TABLE users
  ADD COLUMN item_name VARCHAR(160) NULL AFTER name,
  ADD COLUMN payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' AFTER item_name,
  ADD COLUMN account_number VARCHAR(50) NULL AFTER payment_status;

ALTER TABLE transaction_items
  DROP FOREIGN KEY fk_item_product,
  DROP COLUMN product_id,
  CHANGE COLUMN product_name_snapshot item_name VARCHAR(160) NOT NULL,
  DROP COLUMN reference_price,
  DROP COLUMN reference_subtotal;

ALTER TABLE transactions
  DROP COLUMN reference_total;

DROP TABLE price_references;
DROP TABLE products;
DROP TABLE categories;
