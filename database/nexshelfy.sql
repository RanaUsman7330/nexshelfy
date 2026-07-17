SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
 status ENUM('active','blocked') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 slug VARCHAR(160) NOT NULL UNIQUE,
 name VARCHAR(255) NOT NULL,
 description TEXT NULL,
 price DECIMAL(10,2) NOT NULL DEFAULT 0,
 currency CHAR(3) NOT NULL DEFAULT 'AED',
 file_path VARCHAR(500) NULL,
 status ENUM('active','draft') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products(slug,name,description,price,currency,status,created_at,updated_at) VALUES
('notion-business-os','Notion Business OS','A complete workspace for running a modern digital business.',149.00,'AED','active',NOW(),NOW()),
('saas-launch-kit','SaaS Launch Kit','Launch planning, positioning and execution templates.',189.00,'AED','active',NOW(),NOW()),
('portfolio-system','Portfolio System','A structured system for building a high-converting portfolio.',99.00,'AED','active',NOW(),NOW()),
('content-calendar','Content Calendar','Plan and publish content with a reusable editorial workflow.',79.00,'AED','active',NOW(),NOW()),
('freelance-finance-kit','Freelance Finance Kit','Track income, expenses, invoices and financial goals.',89.00,'AED','active',NOW(),NOW()),
('personal-knowledge-base','Personal Knowledge Base','Organize notes, learning and ideas in one connected system.',69.00,'AED','active',NOW(),NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),price=VALUES(price),currency=VALUES(currency),status=VALUES(status),updated_at=NOW();

CREATE TABLE IF NOT EXISTS wishlists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_wishlist(user_id,product_id),
 CONSTRAINT fk_wishlist_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_wishlist_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookmarks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 article_slug VARCHAR(180) NOT NULL,
 article_title VARCHAR(255) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_bookmark(user_id,article_slug),
 CONSTRAINT fk_bookmark_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL UNIQUE,
 status ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
 subscribed_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL,
 subject VARCHAR(160) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
 ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 order_number VARCHAR(40) NOT NULL UNIQUE,
 status ENUM('pending','processing','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
 payment_status ENUM('unpaid','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
 currency CHAR(3) NOT NULL DEFAULT 'AED',
 subtotal DECIMAL(10,2) NOT NULL,
 total DECIMAL(10,2) NOT NULL,
 customer_name VARCHAR(100) NOT NULL,
 customer_email VARCHAR(190) NOT NULL,
 payment_reference VARCHAR(255) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT fk_order_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 product_name VARCHAR(255) NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 quantity INT UNSIGNED NOT NULL DEFAULT 1,
 line_total DECIMAL(10,2) NOT NULL,
 created_at DATETIME NOT NULL,
 CONSTRAINT fk_item_order FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_item_product FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 order_item_id BIGINT UNSIGNED NOT NULL,
 download_count INT UNSIGNED NOT NULL DEFAULT 0,
 last_downloaded_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_download(user_id,order_item_id),
 CONSTRAINT fk_download_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_download_item FOREIGN KEY(order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id BIGINT UNSIGNED NULL,
 action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(80) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 metadata LONGTEXT NULL,
 ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL,
 KEY idx_admin_created(admin_id,created_at),
 CONSTRAINT fk_admin_log_user FOREIGN KEY(admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 setting_key VARCHAR(120) NOT NULL UNIQUE,
 setting_value TEXT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES
('site_name','NexShelfy',NOW()),
('support_email','enquiry@mrusman.com',NOW()),
('currency','AED',NOW()),
('maintenance_mode','0',NOW())
ON DUPLICATE KEY UPDATE setting_value=setting_value;
SET NAMES utf8mb4;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(500) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS bio VARCHAR(500) NULL AFTER avatar_url,
  ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER status;

CREATE TABLE IF NOT EXISTS user_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 message TEXT NOT NULL,
 type ENUM('info','success','warning','order','download') NOT NULL DEFAULT 'info',
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 action_url VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 KEY idx_user_read_created(user_id,is_read,created_at),
 CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(100) NOT NULL,
 description VARCHAR(500) NULL,
 ip_address VARCHAR(45) NULL,
 created_at DATETIME NOT NULL,
 KEY idx_user_activity(user_id,created_at),
 CONSTRAINT fk_user_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
