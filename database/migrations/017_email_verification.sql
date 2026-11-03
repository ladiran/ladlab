-- Phase 9f: email verification at sign-up (a 6-digit code sent to the address).

ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS email_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  salt CHAR(16) NOT NULL,
  code_hash CHAR(64) NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id, created_at),
  CONSTRAINT fk_ecode_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE users SET email_verified_at = NOW() WHERE role = 'admin';

INSERT INTO settings (skey, svalue) VALUES ('email_verify_since', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) ON DUPLICATE KEY UPDATE svalue = svalue;
