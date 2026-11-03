-- Phase 6: two-step verification (TOTP), recovery codes, sign-in history and "sign out everywhere".

ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL, ADD COLUMN totp_enabled_at DATETIME NULL, ADD COLUMN totp_last_step BIGINT NULL, ADD COLUMN auth_epoch INT NOT NULL DEFAULT 1, ADD COLUMN pw_changed_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS recovery_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  used_at DATETIME NULL,
  KEY idx_user (user_id),
  CONSTRAINT fk_rc_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  device CHAR(16) NOT NULL DEFAULT '',
  success TINYINT(1) NOT NULL DEFAULT 1,
  method VARCHAR(12) NOT NULL DEFAULT 'password',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id, created_at),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
