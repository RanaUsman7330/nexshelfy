-- NexShelfy serious safe migration 2026-07-08
-- Safe for old data: no DROP/TRUNCATE/DELETE. It only creates missing tables/columns and marks products free.

CREATE TABLE IF NOT EXISTS download_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(120) NULL,
  item_type VARCHAR(40) NOT NULL,
  item_key VARCHAR(180) NULL,
  item_title VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_email(email),
  KEY idx_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  subscribed_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_resources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  description TEXT NULL,
  file_path VARCHAR(500) NULL,
  file_name VARCHAR(255) NULL,
  resource_link VARCHAR(500) NULL,
  resource_type VARCHAR(80) NULL,
  status ENUM('active','draft') NOT NULL DEFAULT 'active',
  download_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creator_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  age VARCHAR(40) NULL,
  gender VARCHAR(80) NULL,
  qualification VARCHAR(255) NULL,
  reason TEXT NULL,
  contribution TEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_created(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookmarks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  article_slug VARCHAR(180) NOT NULL,
  article_title VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_article(user_id,article_slug),
  KEY idx_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_user_product(user_id,product_id),
  KEY idx_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  metadata LONGTEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_admin_created (admin_id, created_at),
  KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run these ALTER statements one by one only if phpMyAdmin reports the column is missing.
-- ALTER TABLE products ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 1 AFTER price;
-- ALTER TABLE products ADD COLUMN download_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER file_path;
-- ALTER TABLE products ADD COLUMN zip_path VARCHAR(500) NULL AFTER file_path;
-- ALTER TABLE products ADD COLUMN resource_link VARCHAR(500) NULL AFTER zip_path;
-- ALTER TABLE blog_posts ADD COLUMN cover_image VARCHAR(500) NULL AFTER cover_color;

UPDATE products SET price=0;
-- The PHP self-healing bootstrap will set is_free=1 after adding the column if it is missing.
