-- NexShelfy synchronized cart + Cash on Delivery migration
-- Safe for the current project. The PHP application also self-creates these
-- columns/tables if this migration is not imported manually.

CREATE TABLE IF NOT EXISTS user_cart_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_product(user_id,product_id),
  KEY idx_cart_user(user_id),
  CONSTRAINT fk_cart_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order delivery/COD columns are added automatically by ensure_commerce_schema()
-- in api/bootstrap.php. This avoids duplicate-column errors on shared hosting.
