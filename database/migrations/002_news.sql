-- Phase 2: science news engine.

CREATE TABLE IF NOT EXISTS news_sources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  site_url VARCHAR(300) NOT NULL DEFAULT '',
  feed_url VARCHAR(600) NOT NULL,
  domains VARCHAR(300) NOT NULL DEFAULT '',
  tier TINYINT NOT NULL DEFAULT 2,
  kind VARCHAR(14) NOT NULL DEFAULT 'news',
  region VARCHAR(10) NOT NULL DEFAULT 'world',
  default_topic VARCHAR(12) NOT NULL DEFAULT 'other',
  is_official TINYINT(1) NOT NULL DEFAULT 0,
  can_break TINYINT(1) NOT NULL DEFAULT 0,
  all_breaking TINYINT(1) NOT NULL DEFAULT 0,
  topic_filter TINYINT(1) NOT NULL DEFAULT 0,
  show_images TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(12) NOT NULL DEFAULT 'active',
  probation_until DATETIME NULL,
  paused_reason VARCHAR(200) NOT NULL DEFAULT '',
  consecutive_failures INT NOT NULL DEFAULT 0,
  last_fetch_at DATETIME NULL,
  last_success_at DATETIME NULL,
  last_error VARCHAR(300) NOT NULL DEFAULT '',
  etag VARCHAR(255) NOT NULL DEFAULT '',
  last_modified VARCHAR(100) NOT NULL DEFAULT '',
  notes VARCHAR(300) NOT NULL DEFAULT '',
  is_seed TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id INT UNSIGNED NOT NULL,
  cluster_id INT UNSIGNED NULL,
  url_hash CHAR(40) NOT NULL,
  title_hash CHAR(40) NOT NULL,
  title VARCHAR(500) NOT NULL,
  summary VARCHAR(700) NOT NULL DEFAULT '',
  url VARCHAR(800) NOT NULL,
  image_url VARCHAR(800) NOT NULL DEFAULT '',
  published_at DATETIME NOT NULL,
  fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  region VARCHAR(10) NOT NULL DEFAULT 'world',
  topic VARCHAR(12) NOT NULL DEFAULT 'other',
  status VARCHAR(10) NOT NULL DEFAULT 'published',
  hidden_reason VARCHAR(200) NOT NULL DEFAULT '',
  hidden_by VARCHAR(8) NOT NULL DEFAULT '',
  is_primary TINYINT(1) NOT NULL DEFAULT 1,
  is_breaking TINYINT(1) NOT NULL DEFAULT 0,
  breaking_at DATETIME NULL,
  was_updated TINYINT(1) NOT NULL DEFAULT 0,
  last_checked_at DATETIME NULL,
  UNIQUE KEY uq_url (url_hash),
  KEY idx_list (status, is_primary, published_at),
  KEY idx_cluster (cluster_id),
  KEY idx_title (title_hash),
  KEY idx_source (source_id, published_at),
  KEY idx_breaking (is_breaking, breaking_at),
  CONSTRAINT fk_news_source FOREIGN KEY (source_id) REFERENCES news_sources (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_blocked_terms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  term VARCHAR(120) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NULL,
  source_id INT UNSIGNED NULL,
  event VARCHAR(24) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  actor VARCHAR(8) NOT NULL DEFAULT 'auto',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  KEY idx_event (event, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  message VARCHAR(600) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  status VARCHAR(10) NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at),
  CONSTRAINT fk_report_item FOREIGN KEY (item_id) REFERENCES news_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nobel_cache (
  year SMALLINT NOT NULL PRIMARY KEY,
  payload MEDIUMTEXT NOT NULL,
  fetched_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
