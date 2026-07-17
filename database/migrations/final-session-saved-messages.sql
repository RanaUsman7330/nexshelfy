SET NAMES utf8mb4;
ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS user_id BIGINT UNSIGNED NULL AFTER id;
CREATE INDEX IF NOT EXISTS idx_contact_user ON contact_messages(user_id);
CREATE TABLE IF NOT EXISTS user_cart_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uniq_user_product(user_id,product_id),
 KEY idx_cart_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
