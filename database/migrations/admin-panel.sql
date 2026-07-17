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
