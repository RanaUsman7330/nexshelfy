SET NAMES utf8mb4;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS is_free TINYINT(1) NOT NULL DEFAULT 1 AFTER price,
  ADD COLUMN IF NOT EXISTS download_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER file_path;
UPDATE products SET price=0,is_free=1,currency='AED';

CREATE TABLE IF NOT EXISTS content_resources (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(255) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 description TEXT NULL,
 file_path VARCHAR(500) NULL,
 file_name VARCHAR(255) NULL,
 status ENUM('active','draft') NOT NULL DEFAULT 'active',
 download_count INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 author_id BIGINT UNSIGNED NULL,
 title VARCHAR(255) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 excerpt TEXT NULL,
 content LONGTEXT NOT NULL,
 category VARCHAR(120) NULL,
 cover_color VARCHAR(30) NOT NULL DEFAULT '#6366F1',
 status ENUM('published','draft') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 KEY idx_blog_status_date(status,published_at),
 CONSTRAINT fk_blog_author FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_resources (
 blog_post_id BIGINT UNSIGNED NOT NULL,
 resource_id BIGINT UNSIGNED NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 PRIMARY KEY(blog_post_id,resource_id),
 CONSTRAINT fk_bpr_post FOREIGN KEY(blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
 CONSTRAINT fk_bpr_resource FOREIGN KEY(resource_id) REFERENCES content_resources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_likes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 visitor_key VARCHAR(80) NULL,
 content_type ENUM('product','blog') NOT NULL,
 content_key VARCHAR(180) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_user_like(user_id,content_type,content_key),
 UNIQUE KEY uniq_visitor_like(visitor_key,content_type,content_key),
 KEY idx_content_likes(content_type,content_key),
 CONSTRAINT fk_like_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_comments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 blog_post_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NULL,
 comment TEXT NOT NULL,
 status ENUM('pending','approved','spam') NOT NULL DEFAULT 'approved',
 created_at DATETIME NOT NULL,
 KEY idx_comments_post(blog_post_id,status,created_at),
 CONSTRAINT fk_comment_post FOREIGN KEY(blog_post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
 CONSTRAINT fk_comment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(80) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_user_category(user_id,name),
 CONSTRAINT fk_saved_category_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_category_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id BIGINT UNSIGNED NOT NULL,
 item_type ENUM('product','blog') NOT NULL,
 item_key VARCHAR(180) NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY uniq_category_item(category_id,item_type,item_key),
 CONSTRAINT fk_saved_item_category FOREIGN KEY(category_id) REFERENCES saved_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO content_resources(title,slug,description,file_path,file_name,status,created_at,updated_at) VALUES
('Creator Communication Templates','creator-communication-templates','Ready-to-use email, client and collaboration templates.',NULL,'creator-communication-templates.txt','active',NOW(),NOW()),
('Digital System Starter E-book','digital-system-starter-ebook','A short practical guide to building your first digital system.',NULL,'digital-system-starter-ebook.txt','active',NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),status='active',updated_at=NOW();

INSERT INTO blog_posts(author_id,title,slug,excerpt,content,category,cover_color,status,published_at,created_at,updated_at)
SELECT NULL,'Build a digital system that actually lasts','build-a-digital-system','A practical framework for creating a simple, maintainable digital operating system.',
'<p>Good systems reduce decisions, make work visible and help useful habits survive busy weeks.</p><h2>Start with one outcome</h2><p>Choose the result the system must produce. Then keep only the pages, templates and routines that support it.</p><h2>Design for weekly use</h2><p>A small system used every week is more valuable than a complex system used once.</p>',
'Productivity','#6366F1','published',NOW(),NOW(),NOW()
WHERE NOT EXISTS(SELECT 1 FROM blog_posts WHERE slug='build-a-digital-system');

INSERT INTO blog_posts(author_id,title,slug,excerpt,content,category,cover_color,status,published_at,created_at,updated_at)
SELECT NULL,'The creator operating system','creator-operating-system','A lightweight operating system for ideas, publishing and products.',
'<p>A creator operating system connects ideas, content, audience feedback and products in one clear workflow.</p><h2>Capture, shape, publish</h2><p>Use one inbox, one editorial view and one review ritual.</p>',
'Creators','#06B6D4','published',NOW(),NOW(),NOW()
WHERE NOT EXISTS(SELECT 1 FROM blog_posts WHERE slug='creator-operating-system');

INSERT IGNORE INTO blog_post_resources(blog_post_id,resource_id,sort_order)
SELECT b.id,r.id,1 FROM blog_posts b JOIN content_resources r ON r.slug='digital-system-starter-ebook' WHERE b.slug='build-a-digital-system';
INSERT IGNORE INTO blog_post_resources(blog_post_id,resource_id,sort_order)
SELECT b.id,r.id,1 FROM blog_posts b JOIN content_resources r ON r.slug='creator-communication-templates' WHERE b.slug='creator-operating-system';
