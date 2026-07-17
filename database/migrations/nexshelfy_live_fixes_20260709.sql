-- NexShelfy live fixes migration - 2026-07-09
-- Safe to run once after uploading the changed files. The PHP bootstrap also self-heals these columns/tables.

SET @db_name := DATABASE();

-- content_resources must support both normal file path and ZIP/bundle path.
SET @has_content_zip := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='content_resources' AND COLUMN_NAME='zip_path'
);
SET @sql_content_zip := IF(@has_content_zip = 0,
  'ALTER TABLE content_resources ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path',
  'SELECT "content_resources.zip_path already exists" AS info'
);
PREPARE stmt FROM @sql_content_zip; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Keep all current products free until paid products are intentionally enabled later.
SET @has_products := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='products'
);
SET @sql_free_products := IF(@has_products > 0,
  'UPDATE products SET price=0.00, is_free=1 WHERE status IN ("active","draft")',
  'SELECT "products table not found" AS info'
);
PREPARE stmt FROM @sql_free_products; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure download email capture table exists for free resources/products.
CREATE TABLE IF NOT EXISTS download_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(120) NULL,
  item_type VARCHAR(40) NOT NULL,
  item_key VARCHAR(190) NOT NULL,
  item_title VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_download_leads_email (email),
  KEY idx_download_leads_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure newsletter table exists and can store subscriber confirmation status.
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  subscribed_at DATETIME NOT NULL,
  KEY idx_newsletter_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure creator application requests are stored in admin panel.
CREATE TABLE IF NOT EXISTS creator_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  age VARCHAR(20) NULL,
  gender VARCHAR(40) NULL,
  qualification VARCHAR(190) NULL,
  reason TEXT NULL,
  contribution TEXT NULL,
  portfolio_url VARCHAR(500) NULL,
  status ENUM('new','reviewed','approved','rejected') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_creator_apps_email (email),
  KEY idx_creator_apps_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
