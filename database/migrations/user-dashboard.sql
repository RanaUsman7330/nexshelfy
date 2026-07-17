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
