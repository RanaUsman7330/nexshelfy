ALTER TABLE products
  ADD COLUMN IF NOT EXISTS zip_path VARCHAR(500) NULL AFTER file_path,
  ADD COLUMN IF NOT EXISTS resource_link VARCHAR(500) NULL AFTER zip_path;

ALTER TABLE content_resources
  ADD COLUMN IF NOT EXISTS resource_link VARCHAR(500) NULL AFTER file_name,
  ADD COLUMN IF NOT EXISTS resource_type VARCHAR(80) NULL AFTER resource_link;

ALTER TABLE blog_posts
  ADD COLUMN IF NOT EXISTS cover_image VARCHAR(500) NULL AFTER cover_color;

CREATE TABLE IF NOT EXISTS download_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(120) NULL,
  item_type VARCHAR(30) NOT NULL,
  item_key VARCHAR(180) NOT NULL,
  item_title VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_email_created(email,created_at),
  KEY idx_item(item_type,item_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creator_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  age VARCHAR(20) NULL,
  gender VARCHAR(40) NULL,
  qualification VARCHAR(190) NULL,
  reason TEXT NULL,
  contribution TEXT NULL,
  status ENUM('new','reviewed','approved','rejected') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_creator_status(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
