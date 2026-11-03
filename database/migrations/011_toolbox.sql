-- Phase 7: Free tools for students and researchers (a curated directory of links you manage in Admin).

CREATE TABLE IF NOT EXISTS toolbox_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  url VARCHAR(600) NOT NULL,
  kind VARCHAR(12) NOT NULL DEFAULT 'tutorial',
  subject VARCHAR(60) NOT NULL DEFAULT 'General',
  audience VARCHAR(12) NOT NULL DEFAULT 'all',
  provider VARCHAR(120) NOT NULL DEFAULT '',
  description VARCHAR(500) NOT NULL DEFAULT '',
  license VARCHAR(30) NOT NULL DEFAULT 'free',
  notes MEDIUMTEXT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  link_status VARCHAR(8) NOT NULL DEFAULT 'unknown',
  link_checked_at DATETIME NULL,
  clicks INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_list (is_visible, kind, subject)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
