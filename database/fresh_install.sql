-- ClearSky Academy: complete fresh-install database (Phases 1-8 with sample content).
-- For a NEW, EMPTY database only. Using phpMyAdmin: create a database (collation utf8mb4_unicode_ci), select it, Import this file,
-- copy config/.env.example to config/.env and edit the DB_ lines, then create your administrator:
--     php tools/create_admin.php --email=you@example.com --password="a long passphrase" --name="Your Name"
-- (The web installer does all of this for you; use this file only if you prefer importing by hand.)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===== Phase 1 tables =====
-- ClearSky Academy: Phase 1 schema (utf8mb4, InnoDB). Safe to run on an empty database.

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'admin',
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  identifier VARCHAR(190) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ip (ip, attempted_at),
  KEY idx_ident (identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(40) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bucket (bucket, ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(80) PRIMARY KEY,
  svalue MEDIUMTEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL UNIQUE,
  original_name VARCHAR(200) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size INT UNSIGNED NOT NULL DEFAULT 0,
  kind VARCHAR(10) NOT NULL DEFAULT 'image',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cv_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL,
  original_name VARCHAR(200) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS publications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(500) NOT NULL,
  authors VARCHAR(1000) NOT NULL DEFAULT '',
  venue VARCHAR(300) NOT NULL DEFAULT '',
  year SMALLINT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'journal',
  doi VARCHAR(200) NOT NULL DEFAULT '',
  url VARCHAR(500) NOT NULL DEFAULT '',
  abstract TEXT NULL,
  pdf_file VARCHAR(255) NOT NULL DEFAULT '',
  tags VARCHAR(300) NOT NULL DEFAULT '',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_year (year),
  KEY idx_vis (is_visible, is_sample)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS awards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  org VARCHAR(300) NOT NULL DEFAULT '',
  year SMALLINT NULL,
  description TEXT NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS talks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  event VARCHAR(300) NOT NULL DEFAULT '',
  location VARCHAR(200) NOT NULL DEFAULT '',
  talk_date DATE NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'talk',
  url VARCHAR(500) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  summary TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'ongoing',
  url VARCHAR(500) NOT NULL DEFAULT '',
  image VARCHAR(255) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testimonials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  role VARCHAR(200) NOT NULL DEFAULT '',
  quote TEXT NOT NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timeline (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year VARCHAR(40) NOT NULL,
  title VARCHAR(300) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(250) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  excerpt VARCHAR(400) NOT NULL DEFAULT '',
  body MEDIUMTEXT NOT NULL,
  cover_image VARCHAR(255) NOT NULL DEFAULT '',
  tags VARCHAR(300) NOT NULL DEFAULT '',
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pub (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  body MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  type VARCHAR(20) NOT NULL DEFAULT 'general',
  subject VARCHAR(200) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  ip VARCHAR(45) NOT NULL DEFAULT '',
  status VARCHAR(12) NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL DEFAULT '',
  token CHAR(40) NOT NULL,
  confirmed TINYINT(1) NOT NULL DEFAULT 0,
  source VARCHAR(40) NOT NULL DEFAULT 'website',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME NULL,
  unsubscribed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Phase 2: news =====
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

-- ===== Phase 3: accounts and shop =====
-- Phase 3: customer accounts + T-shirt shop.

ALTER TABLE users
  ADD COLUMN phone VARCHAR(40) NOT NULL DEFAULT '' AFTER email,
  ADD COLUMN address_line VARCHAR(200) NOT NULL DEFAULT '' AFTER phone,
  ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER address_line,
  ADD COLUMN state VARCHAR(60) NOT NULL DEFAULT '' AFTER city;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_token (token_hash),
  KEY idx_user (user_id, created_at),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'T-shirts',
  tagline VARCHAR(200) NOT NULL DEFAULT '',
  description MEDIUMTEXT NULL,
  details MEDIUMTEXT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  compare_price INT UNSIGNED NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  colours TEXT NULL,
  sizes VARCHAR(200) NOT NULL DEFAULT 'S,M,L,XL,XXL',
  design_text VARCHAR(80) NOT NULL DEFAULT '',
  design_style VARCHAR(12) NOT NULL DEFAULT 'text',
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_list (status, is_sample, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(200) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_product (product_id, sort_order),
  CONSTRAINT fk_img_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_variants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  colour VARCHAR(40) NOT NULL,
  colour_hex CHAR(7) NOT NULL DEFAULT '#cccccc',
  size VARCHAR(10) NOT NULL,
  sku VARCHAR(80) NOT NULL,
  bin_location VARCHAR(40) NOT NULL DEFAULT '',
  stock INT NOT NULL DEFAULT 0,
  reorder_level INT NOT NULL DEFAULT 3,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_variant (product_id, colour, size),
  UNIQUE KEY uq_sku (sku),
  CONSTRAINT fk_var_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  type VARCHAR(8) NOT NULL DEFAULT 'percent',
  value INT UNSIGNED NOT NULL DEFAULT 0,
  min_order INT UNSIGNED NOT NULL DEFAULT 0,
  max_uses INT UNSIGNED NOT NULL DEFAULT 0,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  expires_on DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_rates (
  state VARCHAR(40) NOT NULL PRIMARY KEY,
  fee INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(24) NULL UNIQUE,
  access_token CHAR(32) NOT NULL,
  user_id INT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  delivery_method VARCHAR(8) NOT NULL DEFAULT 'ship',
  address_line VARCHAR(200) NOT NULL DEFAULT '',
  city VARCHAR(100) NOT NULL DEFAULT '',
  state VARCHAR(60) NOT NULL DEFAULT '',
  country VARCHAR(40) NOT NULL DEFAULT 'Nigeria',
  subtotal INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  shipping_fee INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  coupon_code VARCHAR(40) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'awaiting_payment',
  payment_method VARCHAR(20) NOT NULL DEFAULT 'bank_transfer',
  receipt_path VARCHAR(255) NOT NULL DEFAULT '',
  receipt_at DATETIME NULL,
  paid_at DATETIME NULL,
  packed_at DATETIME NULL,
  shipped_at DATETIME NULL,
  delivered_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  expires_at DATETIME NULL,
  courier VARCHAR(60) NOT NULL DEFAULT '',
  tracking_no VARCHAR(80) NOT NULL DEFAULT '',
  customer_note VARCHAR(500) NOT NULL DEFAULT '',
  admin_note VARCHAR(500) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status (status, created_at),
  KEY idx_user (user_id),
  KEY idx_email (email),
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  variant_id INT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  size VARCHAR(10) NOT NULL DEFAULT '',
  colour VARCHAR(40) NOT NULL DEFAULT '',
  sku VARCHAR(80) NOT NULL DEFAULT '',
  bin_location VARCHAR(40) NOT NULL DEFAULT '',
  unit_price INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  line_total INT UNSIGNED NOT NULL,
  KEY idx_order (order_id),
  CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
  CONSTRAINT fk_item_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  event VARCHAR(30) NOT NULL,
  detail VARCHAR(400) NOT NULL DEFAULT '',
  actor VARCHAR(10) NOT NULL DEFAULT 'system',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_order (order_id, created_at),
  CONSTRAINT fk_event_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  variant_id INT UNSIGNED NULL,
  label VARCHAR(160) NOT NULL DEFAULT '',
  change_qty INT NOT NULL,
  stock_after INT NOT NULL,
  reason VARCHAR(100) NOT NULL DEFAULT '',
  order_id INT UNSIGNED NULL,
  actor VARCHAR(10) NOT NULL DEFAULT 'system',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_variant (variant_id, created_at),
  KEY idx_created (created_at),
  CONSTRAINT fk_stock_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Phase 4: courses, lessons, quizzes, enrolments, certificates =====
-- Phase 4: University Physics courses (lessons, quizzes, enrolments, progress, certificates).
-- Lesson content, quizzes, questions, enrolments, progress and scores all live in MySQL.

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(150) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  subtitle VARCHAR(250) NOT NULL DEFAULT '',
  subject VARCHAR(40) NOT NULL DEFAULT 'Physics',
  level VARCHAR(40) NOT NULL DEFAULT 'University Year 1',
  description MEDIUMTEXT NULL,
  outcomes MEDIUMTEXT NULL,
  requirements MEDIUMTEXT NULL,
  cover_image VARCHAR(255) NOT NULL DEFAULT '',
  price INT UNSIGNED NOT NULL DEFAULT 0,
  compare_price INT UNSIGNED NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  pass_mark TINYINT UNSIGNED NOT NULL DEFAULT 60,
  certificate_enabled TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_list (status, is_sample, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_modules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  summary VARCHAR(400) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_course (course_id, sort_order),
  CONSTRAINT fk_mod_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  module_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  objectives TEXT NULL,
  content MEDIUMTEXT NULL,
  video_type VARCHAR(8) NOT NULL DEFAULT 'none',
  video_ref VARCHAR(255) NOT NULL DEFAULT '',
  video_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_free_preview TINYINT(1) NOT NULL DEFAULT 0,
  status VARCHAR(10) NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_course (course_id, sort_order),
  KEY idx_module (module_id, sort_order),
  CONSTRAINT fk_lesson_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_lesson_module FOREIGN KEY (module_id) REFERENCES course_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  path VARCHAR(255) NOT NULL,
  size INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_file_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quizzes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  module_id INT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  kind VARCHAR(8) NOT NULL DEFAULT 'quiz',
  instructions TEXT NULL,
  pass_mark TINYINT UNSIGNED NOT NULL DEFAULT 60,
  time_limit_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  questions_to_draw SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  shuffle TINYINT(1) NOT NULL DEFAULT 1,
  status VARCHAR(10) NOT NULL DEFAULT 'published',
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_course (course_id),
  CONSTRAINT fk_quiz_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_quiz_module FOREIGN KEY (module_id) REFERENCES course_modules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT UNSIGNED NOT NULL,
  type VARCHAR(10) NOT NULL DEFAULT 'mcq',
  prompt TEXT NOT NULL,
  explanation TEXT NULL,
  points TINYINT UNSIGNED NOT NULL DEFAULT 1,
  num_answer DOUBLE NULL,
  num_tolerance DOUBLE NOT NULL DEFAULT 1,
  num_unit VARCHAR(30) NOT NULL DEFAULT '',
  accepted TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_quiz (quiz_id, sort_order),
  CONSTRAINT fk_q_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  label VARCHAR(500) NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_q (question_id, sort_order),
  CONSTRAINT fk_opt_q FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrolments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enrol_no VARCHAR(24) NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'awaiting_payment',
  price INT UNSIGNED NOT NULL DEFAULT 0,
  discount INT UNSIGNED NOT NULL DEFAULT 0,
  total INT UNSIGNED NOT NULL DEFAULT 0,
  coupon_code VARCHAR(40) NOT NULL DEFAULT '',
  receipt_path VARCHAR(255) NOT NULL DEFAULT '',
  receipt_at DATETIME NULL,
  paid_at DATETIME NULL,
  source VARCHAR(10) NOT NULL DEFAULT 'purchase',
  completed_at DATETIME NULL,
  last_lesson_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_course (user_id, course_id),
  KEY idx_status (status, created_at),
  CONSTRAINT fk_enrol_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_enrol_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_progress (
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, lesson_id),
  CONSTRAINT fk_prog_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_prog_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  quiz_id INT UNSIGNED NOT NULL,
  question_ids TEXT NOT NULL,
  answers MEDIUMTEXT NULL,
  results MEDIUMTEXT NULL,
  score TINYINT UNSIGNED NULL,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deadline_at DATETIME NULL,
  submitted_at DATETIME NULL,
  KEY idx_user_quiz (user_id, quiz_id),
  CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_att_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(24) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  course_id INT UNSIGNED NULL,
  student_name VARCHAR(150) NOT NULL,
  course_title VARCHAR(200) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT fk_cert_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Phase 5: shop categories, alerts, course community, self-promotion =====
-- Phase 5: shop categories and product types, back-in-stock alerts, course announcements / questions / reviews,
-- and the self-promotion content (services, speaking topics, press, events, link page, free resources).

CREATE TABLE IF NOT EXISTS shop_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(90) NOT NULL UNIQUE,
  description TEXT NULL,
  image VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE products ADD COLUMN category_id INT UNSIGNED NULL AFTER category, ADD COLUMN product_type VARCHAR(10) NOT NULL DEFAULT 'apparel' AFTER category_id, ADD KEY idx_category (category_id);

ALTER TABLE products ADD CONSTRAINT fk_prod_category FOREIGN KEY (category_id) REFERENCES shop_categories (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS stock_alerts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notified_at DATETIME NULL,
  UNIQUE KEY uq_alert (product_id, email),
  CONSTRAINT fk_alert_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  emailed INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_course (course_id, created_at),
  CONSTRAINT fk_ann_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lesson_questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'open',
  answered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_lesson (lesson_id, status),
  KEY idx_status (status, created_at),
  CONSTRAINT fk_q_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE,
  CONSTRAINT fk_q_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_q_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (course_id, user_id),
  KEY idx_course_status (course_id, status),
  CONSTRAINT fk_rev_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '',
  summary VARCHAR(400) NOT NULL DEFAULT '',
  details TEXT NULL,
  price_note VARCHAR(120) NOT NULL DEFAULT '',
  enquiry_type VARCHAR(20) NOT NULL DEFAULT 'general',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS speaking_topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  summary TEXT NULL,
  audience VARCHAR(200) NOT NULL DEFAULT '',
  duration VARCHAR(60) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS press_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  outlet VARCHAR(200) NOT NULL,
  title VARCHAR(300) NOT NULL,
  url VARCHAR(500) NOT NULL DEFAULT '',
  pub_date DATE NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'article',
  quote TEXT NULL,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(250) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  time_text VARCHAR(80) NOT NULL DEFAULT '',
  location VARCHAR(200) NOT NULL DEFAULT '',
  summary TEXT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'talk',
  url VARCHAR(500) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS link_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(150) NOT NULL,
  url VARCHAR(500) NOT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '',
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  topic VARCHAR(80) NOT NULL DEFAULT '',
  pdf_file VARCHAR(255) NOT NULL DEFAULT '',
  url VARCHAR(500) NOT NULL DEFAULT '',
  downloads INT NOT NULL DEFAULT 0,
  is_visible TINYINT(1) NOT NULL DEFAULT 1,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== Phase 6: two-step verification and sign-in history =====
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

-- ===== Phase 7: free tools directory =====
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

-- ===== Phase 8: licences for free resources =====
-- Phase 8: licence, author and rights confirmation for Free Resources.

ALTER TABLE resources ADD COLUMN license VARCHAR(20) NOT NULL DEFAULT 'own', ADD COLUMN author VARCHAR(200) NOT NULL DEFAULT '', ADD COLUMN source_url VARCHAR(500) NOT NULL DEFAULT '', ADD COLUMN rights_confirmed TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(120) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Starter data: sample content, approved news sources, sample T-shirts, delivery fees, the sample University Physics course, legal templates =====
INSERT INTO `awards` (`id`, `title`, `org`, `year`, `description`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample award: Early Career Researcher Award','Professional body',2023,'',1,1,10);
INSERT INTO `awards` (`id`, `title`, `org`, `year`, `description`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Sample award: Best Paper Prize','Conference or society',2021,'',1,1,20);
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('002_news.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('003_news_seed.php','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('004_accounts_shop.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('005_shop_seed.php','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('006_courses.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('007_courses_seed.php','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('008_phase5.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('009_phase5_seed.php','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('010_security.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('011_toolbox.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('012_toolbox_seed.php','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('013_licences.sql','2026-10-10 07:49:15');
INSERT INTO `migrations` (`name`, `applied_at`) VALUES ('014_copyright_page.php','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (1,'sponsored','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (2,'advertorial','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (3,'paid post','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (4,'promoted content','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (5,'miracle cure','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (6,'you won\'t believe','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (7,'shocking','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (8,'doctors hate','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (9,'conspiracy','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (10,'flat earth','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (11,'chemtrail','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (12,'horoscope','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (13,'astrology','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (14,'psychic','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (15,'weight loss','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (16,'get rich','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (17,'click here','2026-10-10 07:49:15');
INSERT INTO `news_blocked_terms` (`id`, `term`, `created_at`) VALUES (18,'giveaway','2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (1,'NASA','https://www.nasa.gov','https://www.nasa.gov/feed/','nasa.gov',1,'primary','world','space',1,1,0,0,1,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (2,'NASA Earth Observatory','https://earthobservatory.nasa.gov','https://earthobservatory.nasa.gov/feeds/earth-observatory.rss','nasa.gov',1,'primary','world','climate',1,0,0,0,1,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (3,'ESA: Space Science','https://www.esa.int','https://www.esa.int/rssfeed/Our_Activities/Space_Science','esa.int',1,'primary','world','space',1,1,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (4,'ESA: Observing the Earth','https://www.esa.int','https://www.esa.int/rssfeed/Our_Activities/Observing_the_Earth','esa.int',1,'primary','world','climate',1,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (5,'NOAA','https://www.noaa.gov','https://www.noaa.gov/rss.xml','noaa.gov',1,'primary','world','climate',1,1,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (6,'USGS: significant earthquakes','https://earthquake.usgs.gov','https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/significant_week.atom','usgs.gov',1,'primary','world','earth',1,1,1,0,0,'active',NULL,'',0,NULL,NULL,'','','','Every item in this feed is a significant earthquake, so each one counts as breaking.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (7,'NobelPrize.org','https://www.nobelprize.org','https://www.nobelprize.org/feed/','nobelprize.org',1,'primary','world','nobel',1,1,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (8,'Nature (research papers)','https://www.nature.com','https://www.nature.com/nature.rss','nature.com',1,'primary','world','other',1,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','Primary research from the journal; never marked breaking by itself.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (9,'WMO (World Meteorological Organization)','https://wmo.int','https://wmo.int/news/rss','wmo.int',1,'primary','world','climate',1,1,0,0,0,'paused',NULL,'Paused at install: The feed address was not found when this site was set up. Find the current RSS link on wmo.int, paste it here and press Resume.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (10,'NiMet (Nigerian Meteorological Agency)','https://nimet.gov.ng','https://nimet.gov.ng/rss','nimet.gov.ng',1,'primary','nigeria','climate',1,1,0,0,0,'paused',NULL,'Paused at install: NiMet did not offer a reachable feed when this site was set up. Check nimet.gov.ng for an RSS link, or leave paused.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (11,'Science (AAAS) News','https://www.science.org','https://www.science.org/rss/news_current.xml','science.org',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (12,'New Scientist','https://www.newscientist.com','https://www.newscientist.com/feed/home/','newscientist.com',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (13,'Science News','https://www.sciencenews.org','https://www.sciencenews.org/feed','sciencenews.org',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (14,'Scientific American','https://www.scientificamerican.com','https://www.scientificamerican.com/platform/syndication/rss/','scientificamerican.com',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (15,'BBC Science & Environment','https://www.bbc.com/news/science_and_environment','https://feeds.bbci.co.uk/news/science_and_environment/rss.xml','bbc.co.uk,bbc.com',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (16,'The Guardian: Science','https://www.theguardian.com/science','https://www.theguardian.com/science/rss','theguardian.com',2,'news','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (17,'Mongabay','https://news.mongabay.com','https://news.mongabay.com/feed/','mongabay.com',2,'news','world','climate',0,0,0,1,0,'active',NULL,'',0,NULL,NULL,'','','','Environment news; only science and climate stories are kept.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (18,'Premium Times (Nigeria)','https://www.premiumtimesng.com','https://www.premiumtimesng.com/feed','premiumtimesng.com',2,'news','nigeria','other',0,0,0,1,0,'active',NULL,'',0,NULL,NULL,'','','','General news feed: only stories that pass the science-topic check are kept.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (19,'Punch (Nigeria)','https://punchng.com','https://punchng.com/feed/','punchng.com',2,'news','nigeria','other',0,0,0,1,0,'active',NULL,'',0,NULL,NULL,'','','','General news feed: only stories that pass the science-topic check are kept.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (20,'TheCable (Nigeria)','https://www.thecable.ng','https://www.thecable.ng/feed','thecable.ng',2,'news','nigeria','other',0,0,0,1,0,'paused',NULL,'Paused at install: The site blocks automated readers (bot protection), so the feed could not be fetched. Try again later, or ask the publisher for feed access.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (21,'The Guardian Nigeria','https://guardian.ng','https://guardian.ng/feed/','guardian.ng',2,'news','nigeria','other',0,0,0,1,0,'paused',NULL,'Paused at install: The site blocks automated readers, so the feed could not be fetched.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (22,'Reuters: Science','https://www.reuters.com/science/','https://www.reuters.com/arc/outboundfeeds/v3/all/category/science/?outputType=xml','reuters.com',2,'news','world','other',0,0,0,0,0,'paused',NULL,'Paused at install: Reuters no longer publishes a free public RSS feed. Leave paused unless you have a licensed feed address.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (23,'SciDev.Net','https://www.scidev.net','https://www.scidev.net/global/feed/','scidev.net',2,'news','africa','other',0,0,0,0,0,'paused',NULL,'Paused at install: The address returned a web page instead of a feed. Look for the current RSS link on scidev.net.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (24,'Nigerian Academy of Science','https://www.nas.edu.ng','https://www.nas.edu.ng/feed/','nas.edu.ng',2,'news','nigeria','other',0,0,0,0,0,'paused',NULL,'Paused at install: The site could not be reached when this was set up. Check the address and press Resume.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (25,'Phys.org','https://phys.org','https://phys.org/rss-feed/','phys.org',3,'press_release','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','Mostly university press releases.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (26,'ScienceDaily','https://www.sciencedaily.com','https://www.sciencedaily.com/rss/top/science.xml','sciencedaily.com',3,'press_release','world','other',0,0,0,0,0,'active',NULL,'',0,NULL,NULL,'','','','Mostly university press releases.',1,'2026-10-10 07:49:15');
INSERT INTO `news_sources` (`id`, `name`, `site_url`, `feed_url`, `domains`, `tier`, `kind`, `region`, `default_topic`, `is_official`, `can_break`, `all_breaking`, `topic_filter`, `show_images`, `status`, `probation_until`, `paused_reason`, `consecutive_failures`, `last_fetch_at`, `last_success_at`, `last_error`, `etag`, `last_modified`, `notes`, `is_seed`, `created_at`) VALUES (27,'EurekAlert!','https://www.eurekalert.org','https://www.eurekalert.org/rss.xml','eurekalert.org',3,'press_release','world','other',0,0,0,0,0,'paused',NULL,'Paused at install: The feed address returned “not found”. Find the current RSS link on eurekalert.org and press Resume.',0,NULL,NULL,'','','','',1,'2026-10-10 07:49:15');
INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `updated_at`) VALUES (1,'privacy','Privacy Policy','This is a starting template. Please review it and adapt it to how you actually use data.\n\n## What we collect\n\nWhen you use the contact form we collect your name, email address, optional phone number and your message. When you subscribe to the newsletter we collect your email address (and name if you give it).\n\n## Why we collect it\n\nWe use contact details only to reply to you, and newsletter details only to send the newsletter you asked for. We do not sell your data.\n\n## Your rights\n\nUnder the Nigeria Data Protection Act you may ask to see, correct or delete your data. Email us and we will respond. You can unsubscribe from the newsletter at any time using the link in every email.\n\n## Cookies\n\nThis site uses only essential cookies (to keep forms safe and to keep the administrator logged in). It does not use advertising or tracking cookies.','2026-10-10 07:49:14');
INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `updated_at`) VALUES (2,'terms','Terms of Use','This is a starting template. Please review it and adapt it.\n\n## Use of this website\n\nThe content on this site is provided for general information and education. We try to keep it accurate, but we make no guarantee that it is complete or error-free.\n\n## Intellectual property\n\nUnless stated otherwise, the text, images and course material on this site belong to the site owner. You may share links, and quote short extracts with credit, but may not copy or resell content without permission.\n\n## External links\n\nWe link to other websites for your convenience and are not responsible for their content.\n\n## Changes\n\nWe may update these terms from time to time.','2026-10-10 07:49:14');
INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `updated_at`) VALUES (3,'sales-terms','Terms of Sale','This is a starting template. Please review it and adapt it to how you actually sell.\n\n## Orders and payment\n\nPrices are in Nigerian Naira. After you place an order you pay by bank transfer to the account shown, using your order number as the reference. Your order is held for a limited time (shown at checkout) while we wait for payment. Items are reserved for you during this time and released if payment is not received. Your order is confirmed only after we have received your payment.\n\n## Delivery\n\nWe dispatch confirmed orders within the time shown on the website and send you a tracking number where the courier provides one. Delivery times depend on the courier and your location. You can choose to collect your order if pickup is offered.\n\n## Returns and exchanges\n\nIf your item arrives damaged or faulty, contact us within 7 days of delivery with your order number and a photo and we will replace it or refund you. Please check the size guide before ordering; unworn items in original condition can be exchanged for another size within 7 days, subject to stock, with the delivery cost paid by the customer.\n\n## Your data\n\nWe use your details only to process and deliver your order and to contact you about it. See the privacy policy.','2026-10-10 07:49:15');
INSERT INTO `pages` (`id`, `slug`, `title`, `body`, `updated_at`) VALUES (4,'copyright','Copyright and takedown policy','We respect the work of authors, publishers, photographers, teachers and software developers, and we expect everyone who uses this site to do the same.\n\n## What we publish\n\n* **Our own material**: notes, lessons, articles and downloads written by us, which we have the right to share.\n* **Openly licensed or public-domain material**, shown with its licence (for example Creative Commons) and its author. Where a licence requires credit, we give it.\n* **Links to other websites** (the Free Tools page). We link only to the owner\'s own page or to legitimate sources: open-access, openly licensed or freely given resources. We do not host those files and do not control those sites.\n\nWe do **not** knowingly publish or link to pirated books, cracked software or other unauthorised copies. Please do not send us any.\n\n## If you believe something here infringes your copyright\n\nTell us at **{{contact_email}}** (or use the \"Report a problem\" link beside any free resource). Please include:\n\n1. **What the work is** (title, author, and where it is lawfully available, if you can say).\n2. **Where it appears on our site**: the web address of the page, resource or link.\n3. **Who you are**: your name, your role (owner or authorised agent) and how to reach you.\n4. **A statement** that you believe in good faith that the use is not authorised by the copyright owner, its agent or the law.\n5. **A statement** that the information you give is accurate and that you are the owner or authorised to act for the owner.\n\n## What we do next\n\n* We acknowledge your message, normally within **2 working days**.\n* If the claim looks valid we **remove or disable access promptly**, and we tell the person who added the item.\n* If the person who added it believes it was removed by mistake, they may send us a short explanation and the proof of their right to share it. We will review it fairly and may restore the item, or ask you to confirm your claim.\n* We may **end the access of anyone who repeatedly shares material they have no right to share.**\n* We keep a record of every notice and what we did about it.\n\n## If you want to share material with us\n\nOnly offer material that is **yours**, **openly licensed**, **in the public domain**, or that you have **written permission** to share. Every free resource on this site shows its licence and author so that readers know what they may do with it. Suggestions for new links are checked before they are listed.\n\n## Using what you find here\n\nFree to read does not always mean free to copy. Check the licence shown on each resource and follow it.\n\n*This policy is provided in good faith to explain how we work. It is not legal advice.*','2026-10-10 07:49:15');
INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `body`, `cover_image`, `tags`, `status`, `published_at`, `is_visible`, `is_sample`, `created_at`, `updated_at`) VALUES (1,'Sample post: Welcome to ClearSky Academy','welcome-to-clearsky-academy','This is a sample article. Replace it with your first real post from the Admin area.','This is a **sample post** so you can see how articles look.\n\n## What you can do\n\n- Write in plain text with simple formatting\n- Add images, links and headings\n- Schedule posts for later\n\nReplace this post from **Admin → Blog posts**.','','welcome, physics','published','2026-10-01 09:00:00',1,1,'2026-10-10 07:49:14','2026-10-10 07:49:14');
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (1,1,'Navy','#0b1f3a','S','TEE-E-NAVY-S','A1',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (2,1,'Navy','#0b1f3a','M','TEE-E-NAVY-M','A1',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (3,1,'Navy','#0b1f3a','L','TEE-E-NAVY-L','A1',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (4,1,'Navy','#0b1f3a','XL','TEE-E-NAVY-XL','A1',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (5,1,'Navy','#0b1f3a','XXL','TEE-E-NAVY-XXL','A1',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (6,1,'White','#ffffff','S','TEE-E-WHITE-S','A1',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (7,1,'White','#ffffff','M','TEE-E-WHITE-M','A1',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (8,1,'White','#ffffff','L','TEE-E-WHITE-L','A1',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (9,1,'White','#ffffff','XL','TEE-E-WHITE-XL','A1',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (10,1,'White','#ffffff','XXL','TEE-E-WHITE-XXL','A1',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (11,2,'Navy','#0b1f3a','S','TEE-ATMOSPHERE-NAVY-S','A2',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (12,2,'Navy','#0b1f3a','M','TEE-ATMOSPHERE-NAVY-M','A2',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (13,2,'Navy','#0b1f3a','L','TEE-ATMOSPHERE-NAVY-L','A2',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (14,2,'Navy','#0b1f3a','XL','TEE-ATMOSPHERE-NAVY-XL','A2',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (15,2,'Navy','#0b1f3a','XXL','TEE-ATMOSPHERE-NAVY-XXL','A2',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (16,2,'White','#ffffff','S','TEE-ATMOSPHERE-WHITE-S','A2',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (17,2,'White','#ffffff','M','TEE-ATMOSPHERE-WHITE-M','A2',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (18,2,'White','#ffffff','L','TEE-ATMOSPHERE-WHITE-L','A2',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (19,2,'White','#ffffff','XL','TEE-ATMOSPHERE-WHITE-XL','A2',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (20,2,'White','#ffffff','XXL','TEE-ATMOSPHERE-WHITE-XXL','A2',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (21,3,'Navy','#0b1f3a','S','TEE-ISOBAR-NAVY-S','A3',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (22,3,'Navy','#0b1f3a','M','TEE-ISOBAR-NAVY-M','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (23,3,'Navy','#0b1f3a','L','TEE-ISOBAR-NAVY-L','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (24,3,'Navy','#0b1f3a','XL','TEE-ISOBAR-NAVY-XL','A3',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (25,3,'Navy','#0b1f3a','XXL','TEE-ISOBAR-NAVY-XXL','A3',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (26,3,'White','#ffffff','S','TEE-ISOBAR-WHITE-S','A3',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (27,3,'White','#ffffff','M','TEE-ISOBAR-WHITE-M','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (28,3,'White','#ffffff','L','TEE-ISOBAR-WHITE-L','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (29,3,'White','#ffffff','XL','TEE-ISOBAR-WHITE-XL','A3',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (30,3,'White','#ffffff','XXL','TEE-ISOBAR-WHITE-XXL','A3',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (31,3,'Sky','#7ec0ff','S','TEE-ISOBAR-SKY-S','A3',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (32,3,'Sky','#7ec0ff','M','TEE-ISOBAR-SKY-M','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (33,3,'Sky','#7ec0ff','L','TEE-ISOBAR-SKY-L','A3',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (34,3,'Sky','#7ec0ff','XL','TEE-ISOBAR-SKY-XL','A3',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (35,3,'Sky','#7ec0ff','XXL','TEE-ISOBAR-SKY-XXL','A3',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (36,4,'Sand','#d9b88f','S','TEE-HARMATTAN-SAND-S','A4',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (37,4,'Sand','#d9b88f','M','TEE-HARMATTAN-SAND-M','A4',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (38,4,'Sand','#d9b88f','L','TEE-HARMATTAN-SAND-L','A4',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (39,4,'Sand','#d9b88f','XL','TEE-HARMATTAN-SAND-XL','A4',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (40,4,'Sand','#d9b88f','XXL','TEE-HARMATTAN-SAND-XXL','A4',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (41,4,'Navy','#0b1f3a','S','TEE-HARMATTAN-NAVY-S','A4',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (42,4,'Navy','#0b1f3a','M','TEE-HARMATTAN-NAVY-M','A4',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (43,4,'Navy','#0b1f3a','L','TEE-HARMATTAN-NAVY-L','A4',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (44,4,'Navy','#0b1f3a','XL','TEE-HARMATTAN-NAVY-XL','A4',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (45,4,'Navy','#0b1f3a','XXL','TEE-HARMATTAN-NAVY-XXL','A4',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (46,5,'Navy','#0b1f3a','S','TEE-VECTOR-NAVY-S','A5',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (47,5,'Navy','#0b1f3a','M','TEE-VECTOR-NAVY-M','A5',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (48,5,'Navy','#0b1f3a','L','TEE-VECTOR-NAVY-L','A5',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (49,5,'Navy','#0b1f3a','XL','TEE-VECTOR-NAVY-XL','A5',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (50,5,'Navy','#0b1f3a','XXL','TEE-VECTOR-NAVY-XXL','A5',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (51,5,'White','#ffffff','S','TEE-VECTOR-WHITE-S','A5',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (52,5,'White','#ffffff','M','TEE-VECTOR-WHITE-M','A5',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (53,5,'White','#ffffff','L','TEE-VECTOR-WHITE-L','A5',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (54,5,'White','#ffffff','XL','TEE-VECTOR-WHITE-XL','A5',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (55,5,'White','#ffffff','XXL','TEE-VECTOR-WHITE-XXL','A5',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (56,6,'Navy','#0b1f3a','S','TEE-ENTROPY-NAVY-S','A6',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (57,6,'Navy','#0b1f3a','M','TEE-ENTROPY-NAVY-M','A6',2,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (58,6,'Navy','#0b1f3a','L','TEE-ENTROPY-NAVY-L','A6',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (59,6,'Navy','#0b1f3a','XL','TEE-ENTROPY-NAVY-XL','A6',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (60,6,'Navy','#0b1f3a','XXL','TEE-ENTROPY-NAVY-XXL','A6',4,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (61,6,'White','#ffffff','S','TEE-ENTROPY-WHITE-S','A6',6,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (62,6,'White','#ffffff','M','TEE-ENTROPY-WHITE-M','A6',2,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (63,6,'White','#ffffff','L','TEE-ENTROPY-WHITE-L','A6',10,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (64,6,'White','#ffffff','XL','TEE-ENTROPY-WHITE-XL','A6',8,3,1);
INSERT INTO `product_variants` (`id`, `product_id`, `colour`, `colour_hex`, `size`, `sku`, `bin_location`, `stock`, `reorder_level`, `is_active`) VALUES (65,6,'White','#ffffff','XXL','TEE-ENTROPY-WHITE-XXL','A6',4,3,1);
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (1,'e-mc2-tee','E = mc² Tee','T-shirts',1,'apparel','The most famous equation in physics, on a tee.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nThe most famous equation in physics, on a tee.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',6500,NULL,'active',1,10,'Navy|#0b1f3a\nWhite|#ffffff','S,M,L,XL,XXL','E = mc²','text',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (2,'atmosphere-layers-tee','Atmosphere Layers Tee','T-shirts',1,'apparel','Troposphere to exosphere, layer by layer.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nTroposphere to exosphere, layer by layer.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',6500,NULL,'active',1,20,'Navy|#0b1f3a\nWhite|#ffffff','S,M,L,XL,XXL','LAYERS','stripes',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (3,'isobar-tee','Isobar Tee','T-shirts',1,'apparel','The pressure lines of a weather map.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nThe pressure lines of a weather map.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',6500,NULL,'active',1,30,'Navy|#0b1f3a\nWhite|#ffffff\nSky|#7ec0ff','S,M,L,XL,XXL','ISOBAR','curves',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (4,'harmattan-season-tee','Harmattan Season Tee','T-shirts',1,'apparel','For the dusty, golden dry season.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nFor the dusty, golden dry season.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',6500,NULL,'active',0,40,'Sand|#d9b88f\nNavy|#0b1f3a','S,M,L,XL,XXL','HARMATTAN','text',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (5,'vector-a-cross-b-tee','Vector A × B Tee','T-shirts',1,'apparel','Cross products and good taste.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nCross products and good taste.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',6500,NULL,'active',0,50,'Navy|#0b1f3a\nWhite|#ffffff','S,M,L,XL,XXL','A × B','text',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `products` (`id`, `slug`, `name`, `category`, `category_id`, `product_type`, `tagline`, `description`, `details`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `colours`, `sizes`, `design_text`, `design_style`, `is_sample`, `created_at`, `updated_at`) VALUES (6,'entropy-happens-tee','Entropy Happens Tee','T-shirts',1,'apparel','Everything falls apart eventually. Look good meanwhile.','**Sample product.** Replace this description, the price, photos and stock in Admin → Shop → Products.\n\nEverything falls apart eventually. Look good meanwhile.','**Placeholder text: replace with your real fabric details.**\n\n- 100% combed cotton, 180 gsm\n- Screen-printed design that will not crack or fade\n- Unisex fit; see the size guide\n- Machine wash cold, inside out. Do not tumble dry.',7000,NULL,'active',0,60,'Navy|#0b1f3a\nWhite|#ffffff','S,M,L,XL,XXL','ENTROPY HAPPENS','text',1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `projects` (`id`, `title`, `summary`, `status`, `url`, `image`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample research area: West African monsoon dynamics','A short description of the question you study, the data and methods you use, and why it matters for people in the region.','ongoing','','',1,1,10);
INSERT INTO `projects` (`id`, `title`, `summary`, `status`, `url`, `image`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Sample research area: Aerosols, clouds and climate','Replace this with your own project description. Add links, images and collaborators from the Admin area.','ongoing','','',1,1,20);
INSERT INTO `projects` (`id`, `title`, `summary`, `status`, `url`, `image`, `is_visible`, `is_sample`, `sort_order`) VALUES (3,'Sample research area: Weather and climate education','Developing clear teaching resources for university physics and atmospheric science students.','ongoing','','',1,1,30);
INSERT INTO `publications` (`id`, `title`, `authors`, `venue`, `year`, `type`, `doi`, `url`, `abstract`, `pdf_file`, `tags`, `is_featured`, `is_visible`, `is_sample`, `sort_order`, `created_at`) VALUES (1,'Sample publication: variability of West African monsoon rainfall','A. Author, B. Author','Journal name',2026,'journal','','',NULL,'','monsoon, rainfall',1,1,1,0,'2026-10-10 07:49:14');
INSERT INTO `publications` (`id`, `title`, `authors`, `venue`, `year`, `type`, `doi`, `url`, `abstract`, `pdf_file`, `tags`, `is_featured`, `is_visible`, `is_sample`, `sort_order`, `created_at`) VALUES (2,'Sample publication: aerosol-cloud interactions over the Gulf of Guinea','A. Author, C. Author','Journal name',2025,'journal','','',NULL,'','aerosols, clouds',1,1,1,0,'2026-10-10 07:49:14');
INSERT INTO `publications` (`id`, `title`, `authors`, `venue`, `year`, `type`, `doi`, `url`, `abstract`, `pdf_file`, `tags`, `is_featured`, `is_visible`, `is_sample`, `sort_order`, `created_at`) VALUES (3,'Sample conference paper: boundary-layer processes in the Sahel','A. Author','Conference name',2024,'conference','','',NULL,'','boundary layer',1,1,1,0,'2026-10-10 07:49:14');
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Abia',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Adamawa',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Akwa Ibom',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Anambra',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Bauchi',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Bayelsa',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Benue',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Borno',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Cross River',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Delta',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Ebonyi',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Edo',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Ekiti',3000);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Enugu',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('FCT (Abuja)',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Gombe',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Imo',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Jigawa',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Kaduna',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Kano',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Katsina',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Kebbi',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Kogi',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Kwara',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Lagos',2500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Nasarawa',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Niger',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Ogun',3000);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Ondo',3000);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Osun',3000);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Oyo',3000);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Plateau',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Rivers',3500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Sokoto',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Taraba',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Yobe',4500);
INSERT INTO `shipping_rates` (`state`, `fee`) VALUES ('Zamfara',4500);
INSERT INTO `talks` (`id`, `title`, `event`, `location`, `talk_date`, `kind`, `url`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample talk: Why the sky behaves the way it does','University lecture series','Nigeria','2026-03-12','talk','',1,1,10);
INSERT INTO `talks` (`id`, `title`, `event`, `location`, `talk_date`, `kind`, `url`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Sample interview: Understanding seasonal forecasts','Radio or TV programme','Lagos','2025-11-02','interview','',1,1,20);
INSERT INTO `testimonials` (`id`, `name`, `role`, `quote`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample student','300L Physics student','Sample testimonial: replace this with a real comment from a student or colleague (with their permission).',1,1,10);
INSERT INTO `timeline` (`id`, `year`, `title`, `detail`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'20XX','Sample: BSc Physics','University name',1,1,10);
INSERT INTO `timeline` (`id`, `year`, `title`, `detail`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'20XX','Sample: MSc Atmospheric Science','University name',1,1,20);
INSERT INTO `timeline` (`id`, `year`, `title`, `detail`, `is_visible`, `is_sample`, `sort_order`) VALUES (3,'20XX','Sample: PhD','University name',1,1,30);
INSERT INTO `timeline` (`id`, `year`, `title`, `detail`, `is_visible`, `is_sample`, `sort_order`) VALUES (4,'20XX–now','Sample: Lecturer / Researcher','Institution name',1,1,40);
INSERT INTO `courses` (`id`, `slug`, `title`, `subtitle`, `subject`, `level`, `description`, `outcomes`, `requirements`, `cover_image`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `pass_mark`, `certificate_enabled`, `is_sample`, `created_at`, `updated_at`) VALUES (1,'university-physics-i-mechanics','University Physics I: Mechanics','From vectors to oscillations: first-year mechanics taught clearly.','Physics','University Year 1','**Sample course description.** Replace this with your own. This course covers the full first-year mechanics syllabus: units and vectors, kinematics, Newton\'s laws, energy, momentum, rotation, gravitation and oscillations. Every lesson has notes with worked equations, and every module ends with a quiz.\n\nA free preview lesson in each module lets you try the course before you enrol.','- Resolve vectors and use dot and cross products\n- Solve kinematics and projectile problems\n- Apply Newton\'s laws, including friction and inclines\n- Use work, energy and power\n- Analyse collisions with momentum conservation\n- Describe rotation, gravitation and simple harmonic motion','- A–level / WAEC Physics and Mathematics\n- Basic calculus (derivatives and integrals) is helpful','',15000,NULL,'published',1,10,60,1,1,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `courses` (`id`, `slug`, `title`, `subtitle`, `subject`, `level`, `description`, `outcomes`, `requirements`, `cover_image`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `pass_mark`, `certificate_enabled`, `is_sample`, `created_at`, `updated_at`) VALUES (2,'electricity-and-magnetism','Electricity and Magnetism','Coulomb to Maxwell: electric fields, circuits and magnetism.','Physics','University Year 1-2','Coulomb to Maxwell: electric fields, circuits and magnetism.',NULL,NULL,'',0,NULL,'soon',0,20,60,1,0,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `courses` (`id`, `slug`, `title`, `subtitle`, `subject`, `level`, `description`, `outcomes`, `requirements`, `cover_image`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `pass_mark`, `certificate_enabled`, `is_sample`, `created_at`, `updated_at`) VALUES (3,'thermodynamics','Thermodynamics','Heat, temperature, entropy and the laws of thermodynamics.','Physics','University Year 1-2','Heat, temperature, entropy and the laws of thermodynamics.',NULL,NULL,'',0,NULL,'soon',0,30,60,1,0,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `courses` (`id`, `slug`, `title`, `subtitle`, `subject`, `level`, `description`, `outcomes`, `requirements`, `cover_image`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `pass_mark`, `certificate_enabled`, `is_sample`, `created_at`, `updated_at`) VALUES (4,'waves-and-optics','Waves and Optics','Sound, light, interference and diffraction.','Physics','University Year 1-2','Sound, light, interference and diffraction.',NULL,NULL,'',0,NULL,'soon',0,40,60,1,0,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `courses` (`id`, `slug`, `title`, `subtitle`, `subject`, `level`, `description`, `outcomes`, `requirements`, `cover_image`, `price`, `compare_price`, `status`, `is_featured`, `sort_order`, `pass_mark`, `certificate_enabled`, `is_sample`, `created_at`, `updated_at`) VALUES (5,'modern-physics','Modern Physics','Relativity, quantum mechanics and atomic structure.','Physics','University Year 1-2','Relativity, quantum mechanics and atomic structure.',NULL,NULL,'',0,NULL,'soon',0,50,60,1,0,'2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (1,1,'Units, Measurement and Vectors','The language of physics: units, uncertainty and vectors.',10);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (2,1,'Motion in One and Two Dimensions','Describing motion with kinematics.',20);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (3,1,'Newton\'s Laws of Motion','Force and motion.',30);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (4,1,'Work and Energy','Energy methods for solving problems.',40);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (5,1,'Momentum and Collisions','Impulse, conservation of momentum and collisions.',50);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (6,1,'Rotation of Rigid Bodies','Rotational kinematics and dynamics.',60);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (7,1,'Gravitation','Newton\'s law of gravitation and orbits.',70);
INSERT INTO `course_modules` (`id`, `course_id`, `title`, `summary`, `sort_order`) VALUES (8,1,'Oscillations','Simple harmonic motion and beyond.',80);
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (1,1,1,'Physical quantities and SI units',10,'Name the SI base units; use dimensional analysis to check an equation.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nEvery physical quantity has a **magnitude** and a **unit**. The SI system is built on seven base units; in mechanics we mostly use the metre (m), kilogram (kg) and second (s).\n\nOther units are *derived*. For example the newton is $1\\,\\mathrm{N} = 1\\,\\mathrm{kg\\,m/s^2}$.\n\n## Dimensional analysis\n\nBoth sides of a correct equation must have the same dimensions. Writing $[L]$, $[M]$, $[T]$ for length, mass and time:\n\n$$[F] = [M][L][T]^{-2}$$\n\n**Check:** is $x = v t + \\tfrac12 a t^2$ dimensionally consistent? $[v t] = [L][T]^{-1}[T] = [L]$ and $[a t^2] = [L][T]^{-2}[T]^2 = [L]$. Yes.','none','',9,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (2,1,1,'Significant figures and uncertainty',20,'Report a result with the correct number of significant figures and an uncertainty.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nA measurement is never exact. We write $x = 12.3 \\pm 0.2\\,\\mathrm{cm}$.\n\n- Multiplying or dividing: keep as many significant figures as the *least* precise factor.\n- Adding or subtracting: keep as many decimal places as the *least* precise term.\n\nThe **percentage uncertainty** is $\\dfrac{\\delta x}{x}\\times 100\\%$. When quantities are multiplied, their percentage uncertainties add (for a rough estimate).','none','',8,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (3,1,1,'Vectors: components and magnitude',30,'Resolve a vector into components and find its magnitude and direction.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nA **vector** has magnitude and direction. In two dimensions we write\n\n$$\\vec{A} = A_x\\,\\hat{x} + A_y\\,\\hat{y}$$\n\nwith components $A_x = A\\cos\\theta$ and $A_y = A\\sin\\theta$. The magnitude and direction are\n\n$$A = \\sqrt{A_x^2 + A_y^2}, \\qquad \\theta = \\tan^{-1}\\!\\left(\\frac{A_y}{A_x}\\right)$$\n\n**Example.** $\\vec{A} = 3\\hat{x} + 4\\hat{y}$ has magnitude $\\sqrt{3^2+4^2} = 5$.','none','',12,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (4,1,1,'Scalar and vector products',40,'Compute dot and cross products and interpret them physically.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nThe **dot (scalar) product** gives a number:\n\n$$\\vec{A}\\cdot\\vec{B} = AB\\cos\\theta = A_xB_x + A_yB_y + A_zB_z$$\n\nWork is a dot product: $W = \\vec{F}\\cdot\\vec{d}$. Perpendicular vectors have a zero dot product.\n\nThe **cross (vector) product** gives a vector of magnitude $|\\vec{A}\\times\\vec{B}| = AB\\sin\\theta$, directed by the right-hand rule. Torque is a cross product: $\\vec{\\tau} = \\vec{r}\\times\\vec{F}$.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (5,1,2,'Position, velocity and acceleration',10,'Define and relate displacement, velocity and acceleration using derivatives.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nVelocity and acceleration are the rates of change of position and velocity:\n\n$$v = \\frac{dx}{dt}, \\qquad a = \\frac{dv}{dt} = \\frac{d^2x}{dt^2}$$\n\nGoing the other way we integrate: $\\Delta x = \\int_{t_1}^{t_2} v\\,dt$. The area under a velocity–time graph is the displacement.','none','',10,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (6,1,2,'Motion with constant acceleration',20,'Apply the four constant-acceleration equations.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nFor constant $a$:\n\n$$v = v_0 + a t$$\n$$x = x_0 + v_0 t + \\tfrac12 a t^2$$\n$$v^2 = v_0^2 + 2a\\,(x - x_0)$$\n\n**Example.** A car starts from rest and accelerates at $2\\,\\mathrm{m/s^2}$ for $5\\,\\mathrm{s}$. Then $x = \\tfrac12 (2)(5)^2 = 25\\,\\mathrm{m}$ and $v = 10\\,\\mathrm{m/s}$.','none','',14,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (7,1,2,'Projectile motion',30,'Analyse horizontal and vertical motion independently.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nIgnoring air resistance, a projectile has **no horizontal acceleration** and a vertical acceleration of $-g$.\n\nWith launch speed $v_0$ at angle $\\theta$:\n\n$$x = (v_0\\cos\\theta)\\,t, \\qquad y = (v_0\\sin\\theta)\\,t - \\tfrac12 g t^2$$\n\nThe range on level ground is\n\n$$R = \\frac{v_0^2 \\sin 2\\theta}{g}$$\n\nwhich is largest at $\\theta = 45^\\circ$.','none','',15,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (8,1,2,'Relative and circular motion',40,'Use relative velocity and the centripetal acceleration.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n**Relative velocity:** $\\vec{v}_{A/C} = \\vec{v}_{A/B} + \\vec{v}_{B/C}$.\n\nAn object moving in a circle of radius $r$ at constant speed $v$ accelerates towards the centre:\n\n$$a_c = \\frac{v^2}{r} = \\omega^2 r, \\qquad T = \\frac{2\\pi r}{v}$$','none','',10,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (9,1,3,'Newton\'s first and second laws',10,'State the laws and apply $\\vec F_{net} = m\\vec a$.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n**First law:** a body keeps its velocity unless a net external force acts on it.\n\n**Second law:**\n\n$$\\sum \\vec{F} = m\\vec{a}$$\n\n**Example.** A $5\\,\\mathrm{kg}$ block pushed by a net force of $20\\,\\mathrm{N}$ accelerates at $a = F/m = 4\\,\\mathrm{m/s^2}$.','none','',12,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (10,1,3,'Free-body diagrams and the third law',20,'Draw free-body diagrams and identify action–reaction pairs.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nDraw the object alone, then draw **every force acting on it**: weight $m\\vec g$, normal force $\\vec N$, tension $\\vec T$, friction $\\vec f$.\n\n**Third law:** if A pushes on B with force $\\vec F$, then B pushes on A with $-\\vec F$. The two forces act on *different* objects, so they never cancel in a free-body diagram.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (11,1,3,'Friction and drag',30,'Use the friction model and interpret terminal velocity.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nStatic friction satisfies $f_s \\le \\mu_s N$; kinetic friction is $f_k = \\mu_k N$.\n\n**Example.** A crate with $N = 50\\,\\mathrm{N}$ and $\\mu_k = 0.3$ experiences $f_k = 15\\,\\mathrm{N}$.\n\nAt high speed, drag grows with speed until it balances weight: the object reaches **terminal velocity**.','none','',10,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (12,1,3,'Applications: inclines and pulleys',40,'Solve inclined-plane and Atwood-machine problems.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nOn a frictionless incline of angle $\\theta$ the acceleration down the slope is\n\n$$a = g\\sin\\theta$$\n\nFor an Atwood machine with masses $m_1 > m_2$:\n\n$$a = \\frac{m_1 - m_2}{m_1 + m_2}\\,g, \\qquad T = \\frac{2 m_1 m_2}{m_1 + m_2}\\,g$$','none','',16,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (13,1,4,'Work done by a force',10,'Calculate the work done by constant and variable forces.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nFor a constant force, $W = \\vec{F}\\cdot\\vec{d} = Fd\\cos\\theta$. For a variable force along a path,\n\n$$W = \\int_{\\vec r_1}^{\\vec r_2} \\vec{F}\\cdot d\\vec{r}$$\n\nThe work done by a spring that is stretched from $0$ to $x$ is $W = \\tfrac12 k x^2$.','none','',12,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (14,1,4,'Kinetic energy and the work–energy theorem',20,'Use $W_{net} = \\Delta K$.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$K = \\tfrac12 m v^2, \\qquad W_{net} = \\Delta K = K_f - K_i$$\n\n**Example.** A $2\\,\\mathrm{kg}$ ball moving at $3\\,\\mathrm{m/s}$ has $K = \\tfrac12(2)(3)^2 = 9\\,\\mathrm{J}$.','none','',10,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (15,1,4,'Potential energy',30,'Define gravitational and elastic potential energy.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nA conservative force has a potential energy $U$ with\n\n$$\\Delta U = -\\int \\vec{F}\\cdot d\\vec{r}, \\qquad F_x = -\\frac{dU}{dx}$$\n\nNear the Earth\'s surface $U = mgh$; for a spring $U = \\tfrac12 k x^2$.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (16,1,4,'Conservation of energy and power',40,'Apply $E = K + U$ conservation and compute power.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nIf only conservative forces do work, the **mechanical energy** is constant:\n\n$$K_i + U_i = K_f + U_f$$\n\n**Power** is the rate of doing work:\n\n$$P = \\frac{dW}{dt} = \\vec{F}\\cdot\\vec{v}$$\n\nThe SI unit is the watt, $1\\,\\mathrm{W} = 1\\,\\mathrm{J/s}$.','none','',13,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (17,1,5,'Momentum and impulse',10,'Relate impulse to the change in momentum.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$\\vec{p} = m\\vec{v}, \\qquad \\vec{F}_{net} = \\frac{d\\vec{p}}{dt}$$\n\n**Impulse:** $\\vec{J} = \\int \\vec{F}\\,dt = \\Delta\\vec{p}$. A long contact time lowers the average force for the same impulse: this is why airbags work.','none','',9,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (18,1,5,'Conservation of momentum',20,'Use conservation of momentum in isolated systems.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nIf the net external force on a system is zero, its total momentum is constant:\n\n$$\\sum \\vec{p}_{initial} = \\sum \\vec{p}_{final}$$','none','',10,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (19,1,5,'Elastic and inelastic collisions',30,'Distinguish collision types and solve one-dimensional collisions.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nIn every isolated collision momentum is conserved. In an **elastic** collision kinetic energy is conserved too. For a 1-D elastic collision:\n\n$$v_{1f} = \\frac{m_1 - m_2}{m_1 + m_2}\\,v_{1i}, \\qquad v_{2f} = \\frac{2m_1}{m_1 + m_2}\\,v_{1i}\\quad (m_2\\ \\text{initially at rest})$$\n\nIn a **perfectly inelastic** collision the objects stick together: $v_f = \\dfrac{m_1 v_1 + m_2 v_2}{m_1 + m_2}$.','none','',14,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (20,1,5,'Centre of mass',40,'Locate the centre of mass of a system.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$\\vec{R}_{cm} = \\frac{\\sum m_i \\vec{r}_i}{\\sum m_i}$$\n\nThe centre of mass of a system moves as if all the mass were concentrated there and all external forces acted on it: $M\\vec{a}_{cm} = \\sum \\vec{F}_{ext}$.','none','',9,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (21,1,6,'Angular kinematics',10,'Relate angular and linear quantities.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nThe rotational equations mirror the linear ones:\n\n$$\\omega = \\omega_0 + \\alpha t, \\qquad \\theta = \\theta_0 + \\omega_0 t + \\tfrac12\\alpha t^2$$\n\nand for a point at radius $r$: $v = \\omega r$, $a_t = \\alpha r$.','none','',9,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (22,1,6,'Torque and moment of inertia',20,'Compute torque and moment of inertia.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$\\tau = rF\\sin\\theta, \\qquad I = \\sum m_i r_i^2$$\n\nFor a solid disc about its axis $I = \\tfrac12 M R^2$; for a thin hoop $I = MR^2$.','none','',12,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (23,1,6,'Rotational dynamics',30,'Use $\\tau_{net} = I\\alpha$.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$\\sum \\tau = I\\alpha, \\qquad K_{rot} = \\tfrac12 I\\omega^2$$\n\nThe **parallel-axis theorem**: $I = I_{cm} + M d^2$.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (24,1,6,'Angular momentum and rolling',40,'Apply conservation of angular momentum; analyse rolling.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$\\vec{L} = \\vec{r}\\times\\vec{p} = I\\vec{\\omega}, \\qquad \\vec{\\tau}_{net} = \\frac{d\\vec{L}}{dt}$$\n\nRolling without slipping: $v_{cm} = \\omega R$ and the total kinetic energy is $\\tfrac12 M v_{cm}^2 + \\tfrac12 I_{cm}\\omega^2$.','none','',13,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (25,1,7,'Newton\'s law of gravitation',10,'State and use the inverse-square law.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$F = G\\frac{m_1 m_2}{r^2}$$\n\nwith $G = 6.674\\times 10^{-11}\\,\\mathrm{N\\,m^2/kg^2}$. At the Earth\'s surface $g = GM/R^2 \\approx 9.8\\,\\mathrm{m/s^2}$.','none','',10,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (26,1,7,'Gravitational potential energy and escape speed',20,'Compute $U(r)$ and the escape speed.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\n$$U(r) = -\\frac{G M m}{r}, \\qquad v_{esc} = \\sqrt{\\frac{2GM}{R}}$$\n\nFor the Earth, $v_{esc} \\approx 11.2\\,\\mathrm{km/s}$.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (27,1,7,'Orbits and Kepler\'s laws',30,'Use Kepler\'s third law for circular and elliptical orbits.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nFor a circular orbit, setting gravity equal to the centripetal force gives\n\n$$v = \\sqrt{\\frac{GM}{r}}, \\qquad T^2 = \\frac{4\\pi^2}{GM}\\,r^3$$\n\n**Kepler\'s laws:** (1) orbits are ellipses with the Sun at a focus; (2) equal areas are swept in equal times; (3) $T^2\\propto a^3$.','none','',14,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (28,1,8,'Simple harmonic motion',10,'Write and interpret $x(t) = A\\cos(\\omega t + \\phi)$.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nA restoring force $F = -kx$ produces simple harmonic motion:\n\n$$x(t) = A\\cos(\\omega t + \\phi), \\qquad \\omega = \\sqrt{\\frac{k}{m}}, \\qquad T = \\frac{2\\pi}{\\omega}$$\n\n**Example.** $k = 100\\,\\mathrm{N/m}$ and $m = 1\\,\\mathrm{kg}$ give $\\omega = 10\\,\\mathrm{rad/s}$ and $T \\approx 0.63\\,\\mathrm{s}$.','none','',13,1,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (29,1,8,'Energy in SHM and the pendulum',20,'Use energy conservation in SHM; find the pendulum period.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nThe total energy is constant: $E = \\tfrac12 k A^2 = \\tfrac12 m v^2 + \\tfrac12 k x^2$.\n\nFor a simple pendulum with small angles,\n\n$$T = 2\\pi\\sqrt{\\frac{L}{g}}$$','none','',12,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `lessons` (`id`, `course_id`, `module_id`, `title`, `sort_order`, `objectives`, `content`, `video_type`, `video_ref`, `video_minutes`, `is_free_preview`, `status`, `created_at`, `updated_at`) VALUES (30,1,8,'Damped and driven oscillations',30,'Describe damping and resonance qualitatively.','*Sample lesson text. Replace it with your own lecture notes in Admin → Courses.*\n\nWith a damping force $-b v$, the amplitude decays like $e^{-bt/2m}$. A periodic driving force produces **resonance** when its frequency is close to the natural frequency $\\omega_0 = \\sqrt{k/m}$.','none','',11,0,'published','2026-10-10 07:49:15','2026-10-10 07:49:15');
INSERT INTO `quizzes` (`id`, `course_id`, `module_id`, `title`, `kind`, `instructions`, `pass_mark`, `time_limit_minutes`, `max_attempts`, `questions_to_draw`, `shuffle`, `status`, `sort_order`) VALUES (1,1,1,'Quiz: Units and vectors','quiz','Check your understanding. You can retake this quiz as often as you like.',60,0,0,0,1,'published',15);
INSERT INTO `quizzes` (`id`, `course_id`, `module_id`, `title`, `kind`, `instructions`, `pass_mark`, `time_limit_minutes`, `max_attempts`, `questions_to_draw`, `shuffle`, `status`, `sort_order`) VALUES (2,1,2,'Quiz: Kinematics','quiz','Check your understanding. You can retake this quiz as often as you like.',60,0,0,0,1,'published',25);
INSERT INTO `quizzes` (`id`, `course_id`, `module_id`, `title`, `kind`, `instructions`, `pass_mark`, `time_limit_minutes`, `max_attempts`, `questions_to_draw`, `shuffle`, `status`, `sort_order`) VALUES (3,1,3,'Quiz: Newton\'s laws','quiz','Check your understanding. You can retake this quiz as often as you like.',60,0,0,0,1,'published',35);
INSERT INTO `quizzes` (`id`, `course_id`, `module_id`, `title`, `kind`, `instructions`, `pass_mark`, `time_limit_minutes`, `max_attempts`, `questions_to_draw`, `shuffle`, `status`, `sort_order`) VALUES (4,1,NULL,'Final exam: University Physics I','final','Take your time. Calculators are allowed. You need the pass mark to earn your certificate.',60,30,3,5,1,'published',900);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (1,1,'mcq','Which is the SI unit of force?','The newton: $1\\,\\mathrm{N} = 1\\,\\mathrm{kg\\,m/s^2}$.',1,NULL,1,'',NULL,10);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (2,1,'truefalse','The dot product of two perpendicular vectors is zero.','$\\vec A\\cdot\\vec B = AB\\cos 90^\\circ = 0$.',1,NULL,1,'',NULL,20);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (3,1,'numeric','What is the magnitude of the vector $\\vec A = 3\\hat{x} + 4\\hat{y}$?','$\\sqrt{3^2+4^2}=5$.',1,5,0.5,'',NULL,30);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (4,1,'numeric','Find $\\vec A\\cdot\\vec B$ for $\\vec A = (2, 3, 1)$ and $\\vec B = (1, -1, 4)$.','$2(1)+3(-1)+1(4)=3$.',2,3,0,'',NULL,40);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (5,1,'short','What rule gives the direction of a cross product? (two or three words)','The right-hand rule.',1,NULL,1,'','right-hand rule\nright hand rule\nthe right-hand rule\nthe right hand rule',50);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (6,1,'multi','Select ALL the vector quantities.','Displacement, velocity and force have direction; speed does not.',2,NULL,1,'',NULL,60);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (7,2,'numeric','A car starts from rest and accelerates at $2\\,\\mathrm{m/s^2}$ for $5\\,\\mathrm{s}$. How far does it travel? (metres)','$x = \\tfrac12 a t^2 = 25\\,\\mathrm{m}$.',1,25,1,'m',NULL,10);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (8,2,'numeric','A ball is launched at $20\\,\\mathrm{m/s}$ at $30^\\circ$ above the horizontal on level ground ($g = 9.8\\,\\mathrm{m/s^2}$). Find its range in metres.','$R = v_0^2\\sin 2\\theta / g = 400(0.866)/9.8 \\approx 35.3\\,\\mathrm{m}$.',2,35.35,1,'m',NULL,20);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (9,2,'mcq','Ignoring air resistance, what is the horizontal acceleration of a projectile?','No horizontal force acts, so $a_x = 0$.',1,NULL,1,'',NULL,30);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (10,3,'numeric','A net force of $20\\,\\mathrm{N}$ acts on a $5\\,\\mathrm{kg}$ block. What is its acceleration? (m/s²)','$a = F/m = 4$.',1,4,1,'m/s^2',NULL,10);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (11,3,'numeric','A crate has normal force $50\\,\\mathrm{N}$ and $\\mu_k = 0.3$. What is the kinetic friction force in newtons?','$f_k = \\mu_k N = 15\\,\\mathrm{N}$.',1,15,1,'N',NULL,20);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (12,3,'truefalse','Action and reaction forces from Newton\'s third law act on the same object.','They act on *different* objects, so they do not cancel.',1,NULL,1,'',NULL,30);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (13,4,'numeric','A $2\\,\\mathrm{kg}$ ball moves at $3\\,\\mathrm{m/s}$. What is its kinetic energy in joules?','$K = \\tfrac12 m v^2 = 9\\,\\mathrm{J}$.',2,9,1,'J',NULL,10);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (14,4,'numeric','A mass of $1\\,\\mathrm{kg}$ on a spring with $k = 100\\,\\mathrm{N/m}$ oscillates. What is its period in seconds?','$T = 2\\pi\\sqrt{m/k} = 0.628\\,\\mathrm{s}$.',2,0.6283,1,'s',NULL,20);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (15,4,'numeric','A $0.5\\,\\mathrm{kg}$ ball moving at $4\\,\\mathrm{m/s}$ is stopped in $0.1\\,\\mathrm{s}$. What is the magnitude of the average force in newtons?','$F = \\Delta p/\\Delta t = 2/0.1 = 20\\,\\mathrm{N}$.',2,20,1,'N',NULL,30);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (16,4,'mcq','In a perfectly inelastic collision, which quantity is NOT conserved?','Momentum is conserved in every isolated collision; kinetic energy is lost.',1,NULL,1,'',NULL,40);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (17,4,'mcq','The escape speed from a planet of mass $M$ and radius $R$ is','$v_{esc}=\\sqrt{2GM/R}$.',1,NULL,1,'',NULL,50);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (18,4,'short','What is the name of the point where all of a system\'s mass can be treated as concentrated for translational motion?','The centre of mass.',1,NULL,1,'','centre of mass\ncenter of mass\ncentre of gravity\ncenter of gravity',60);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (19,4,'truefalse','A simple pendulum\'s period (small angles) depends on the mass of the bob.','$T = 2\\pi\\sqrt{L/g}$ does not contain $m$.',1,NULL,1,'',NULL,70);
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `prompt`, `explanation`, `points`, `num_answer`, `num_tolerance`, `num_unit`, `accepted`, `sort_order`) VALUES (20,4,'numeric','A disc of mass $2\\,\\mathrm{kg}$ and radius $0.5\\,\\mathrm{m}$ spins about its axis. Find its moment of inertia in $\\mathrm{kg\\,m^2}$.','$I = \\tfrac12 M R^2 = 0.25$.',2,0.25,1,'kg m^2',NULL,80);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (1,1,'newton (N)',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (2,1,'joule (J)',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (3,1,'watt (W)',0,30);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (4,1,'pascal (Pa)',0,40);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (5,2,'True',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (6,2,'False',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (7,6,'displacement',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (8,6,'speed',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (9,6,'velocity',1,30);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (10,6,'force',1,40);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (11,6,'mass',0,50);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (12,9,'zero',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (13,9,'$g$',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (14,9,'$-g$',0,30);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (15,9,'$v_0\\cos\\theta$',0,40);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (16,12,'True',0,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (17,12,'False',1,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (18,16,'kinetic energy',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (19,16,'momentum',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (20,16,'total energy',0,30);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (21,17,'$\\sqrt{2GM/R}$',1,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (22,17,'$\\sqrt{GM/R}$',0,20);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (23,17,'$2GM/R$',0,30);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (24,17,'$GM/R^2$',0,40);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (25,19,'True',0,10);
INSERT INTO `question_options` (`id`, `question_id`, `label`, `is_correct`, `sort_order`) VALUES (26,19,'False',1,20);
INSERT INTO `shop_categories` (`id`, `name`, `slug`, `description`, `image`, `sort_order`, `is_visible`, `created_at`) VALUES (1,'T-shirts','t-shirts','Original physics and atmosphere designs. Wear the science.','',10,1,'2026-10-10 07:49:15');
INSERT INTO `services` (`id`, `title`, `icon`, `summary`, `details`, `price_note`, `enquiry_type`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Consulting','📊','Weather, climate and atmospheric data analysis for organisations.','*Sample text: replace it in Admin → Services.*\n\nWhat I can help with:\n\n- Interpreting weather and climate data for planning and risk\n- Reviewing and designing observation or monitoring programmes\n- Technical reports and independent expert opinion','Quoted per project','consulting',1,1,10);
INSERT INTO `services` (`id`, `title`, `icon`, `summary`, `details`, `price_note`, `enquiry_type`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Speaking and keynotes','🎤','Talks on climate, weather and physics for conferences, schools and media.','*Sample text: replace it in Admin → Services.*\n\nTalks can be 20 minutes to a full day, in person or online. See the Speaking page for topics and the media kit.','Fee on request','speaking',1,1,20);
INSERT INTO `services` (`id`, `title`, `icon`, `summary`, `details`, `price_note`, `enquiry_type`, `is_visible`, `is_sample`, `sort_order`) VALUES (3,'Training and workshops','🧑‍🏫','Hands-on workshops for teachers, students and professionals.','*Sample text: replace it in Admin → Services.*\n\nExamples: teaching physics with everyday examples, introduction to weather data, research methods for graduate students.','From a half-day session','training',1,1,30);
INSERT INTO `services` (`id`, `title`, `icon`, `summary`, `details`, `price_note`, `enquiry_type`, `is_visible`, `is_sample`, `sort_order`) VALUES (4,'Supervision and mentoring','🎓','Mentoring for postgraduate students and early-career researchers.','*Sample text: replace it in Admin → Services.*\n\nProject and thesis supervision, proposal and manuscript feedback, career guidance in the atmospheric sciences.','By arrangement','supervision',1,1,40);
INSERT INTO `speaking_topics` (`id`, `title`, `summary`, `audience`, `duration`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Why West Africa\'s rains are changing','Sample topic: how the West African monsoon works and what a warming climate means for farmers and cities.','General audience, policy makers','30–45 min',1,1,10);
INSERT INTO `speaking_topics` (`id`, `title`, `summary`, `audience`, `duration`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Physics you can feel: the science of everyday weather','Sample topic: a lively talk that explains weather with simple demonstrations.','Secondary schools, undergraduates','45 min',1,1,20);
INSERT INTO `speaking_topics` (`id`, `title`, `summary`, `audience`, `duration`, `is_visible`, `is_sample`, `sort_order`) VALUES (3,'From data to decisions: using climate information','Sample topic: turning forecasts and observations into practical planning.','Organisations, NGOs, government','60 min',1,1,30);
INSERT INTO `press_items` (`id`, `outlet`, `title`, `url`, `pub_date`, `kind`, `quote`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample Newspaper','Sample: interview on the coming rainy season','','2026-08-11','interview','Sample quote: replace with a line from the real article.',1,1,10);
INSERT INTO `press_items` (`id`, `outlet`, `title`, `url`, `pub_date`, `kind`, `quote`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Sample Radio','Sample: explaining the harmattan on morning radio','','2026-03-24','radio','',1,1,20);
INSERT INTO `events` (`id`, `title`, `start_date`, `end_date`, `time_text`, `location`, `summary`, `kind`, `url`, `is_visible`, `is_sample`, `sort_order`) VALUES (1,'Sample: public lecture on climate and weather','2026-11-24',NULL,'','Lagos, Nigeria (sample)','Sample event. Replace it in Admin → Events.','talk','',1,1,10);
INSERT INTO `events` (`id`, `title`, `start_date`, `end_date`, `time_text`, `location`, `summary`, `kind`, `url`, `is_visible`, `is_sample`, `sort_order`) VALUES (2,'Sample: workshop for physics teachers','2027-01-08',NULL,'','Online (sample)','Sample event.','workshop','',1,1,20);
INSERT INTO `events` (`id`, `title`, `start_date`, `end_date`, `time_text`, `location`, `summary`, `kind`, `url`, `is_visible`, `is_sample`, `sort_order`) VALUES (3,'Sample: past conference keynote','2026-06-12',NULL,'','Abuja, Nigeria (sample)','Sample past event.','keynote','',1,1,30);
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (1,'MIT OpenCourseWare on YouTube','https://www.youtube.com/@mitocw','video','Physics','all','MIT OpenCourseWare','Complete university lecture series from MIT, including physics, mathematics and earth sciences.','free',NULL,1,1,10,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (2,'Khan Academy on YouTube','https://www.youtube.com/@khanacademy','video','Mathematics','students','Khan Academy','Clear short lessons from arithmetic to calculus, plus physics and chemistry basics.','free',NULL,1,1,20,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (3,'3Blue1Brown on YouTube','https://www.youtube.com/@3blue1brown','video','Mathematics','all','3Blue1Brown','Beautiful visual explanations of calculus, linear algebra, differential equations and more.','free',NULL,1,1,30,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (4,'Professor Leonard on YouTube','https://www.youtube.com/@ProfessorLeonard','video','Mathematics','students','Professor Leonard','Full-length university lectures in calculus, statistics and differential equations.','free',NULL,0,1,40,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (5,'The Organic Chemistry Tutor on YouTube','https://www.youtube.com/@TheOrganicChemistryTutor','video','Physics','students','The Organic Chemistry Tutor','Thousands of worked problems in physics, maths and chemistry, step by step.','free',NULL,0,1,50,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (6,'Veritasium on YouTube','https://www.youtube.com/@veritasium','video','Physics','all','Veritasium','Science and physics stories and experiments explained for a wide audience.','free',NULL,0,1,60,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (7,'The Feynman Lectures on Physics (online)','https://www.feynmanlectures.caltech.edu/','course','Physics','students','Caltech','The classic physics lectures, free to read online in your browser.','read',NULL,0,1,70,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (8,'OpenStax: free textbooks','https://openstax.org/subjects','course','Physics','students','OpenStax (Rice University)','Peer-reviewed, openly licensed textbooks including University Physics, Calculus and Chemistry.','open',NULL,1,1,80,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (9,'LibreTexts','https://libretexts.org/','course','General','all','LibreTexts','A huge library of free open textbooks in physics, mathematics, geosciences and more.','open',NULL,0,1,90,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (10,'MIT OpenCourseWare','https://ocw.mit.edu/','course','Physics','all','MIT','Free course materials from MIT: lecture notes, problem sets, exams and videos.','free',NULL,1,1,100,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (11,'NPTEL','https://nptel.ac.in/','course','General','all','NPTEL (India)','Free video courses from the Indian Institutes of Technology in physics, mathematics and engineering.','free',NULL,0,1,110,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (12,'Paul\'s Online Math Notes','https://tutorial.math.lamar.edu/','course','Mathematics','students','Lamar University','Clear notes with worked examples for calculus, differential equations and linear algebra.','free',NULL,0,1,120,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (13,'Open Textbook Library','https://open.umn.edu/opentextbooks','course','General','all','University of Minnesota','Browse hundreds of free, openly licensed textbooks that you can read online or download.','open',NULL,0,1,130,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (14,'Directory of Open Access Books','https://www.doabooks.org/','course','General','researchers','DOAB','Search peer-reviewed academic books that publishers have made free to read.','open',NULL,0,1,140,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (15,'Project Gutenberg','https://www.gutenberg.org/','course','General','all','Project Gutenberg','Over 70,000 books whose copyright has expired, free to read and download.','read',NULL,0,1,150,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (16,'COMET MetEd: meteorology training','https://www.meted.ucar.edu/','course','Meteorology & climate','all','UCAR COMET Program','Free professional-level lessons in weather, climate, forecasting and numerical models.','account',NULL,1,1,160,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (17,'NWS JetStream online weather school','https://www.weather.gov/jetstream/','course','Meteorology & climate','students','US National Weather Service','A friendly introduction to the atmosphere, weather, storms and forecasting.','free',NULL,0,1,170,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (18,'Climate.gov','https://www.climate.gov/','course','Meteorology & climate','all','NOAA','Explainers, maps and teaching resources on climate science.','free',NULL,0,1,180,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (19,'NASA Climate','https://climate.nasa.gov/','course','Meteorology & climate','all','NASA','Evidence, data visualisations and learning resources about Earth\'s changing climate.','free',NULL,0,1,190,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (20,'PhET Interactive Simulations','https://phet.colorado.edu/','online','Teaching','teachers','University of Colorado Boulder','Free interactive physics, maths and chemistry simulations for classroom and self-study.','free',NULL,1,1,200,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (21,'Desmos Graphing Calculator','https://www.desmos.com/calculator','online','Mathematics','all','Desmos','A fast, free online graphing calculator.','free',NULL,0,1,210,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (22,'GeoGebra','https://www.geogebra.org/','online','Mathematics','all','GeoGebra','Free tools for graphing, geometry, algebra, calculus and 3D.','free',NULL,0,1,220,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (23,'Wolfram MathWorld','https://mathworld.wolfram.com/','online','Mathematics','all','Wolfram Research','An encyclopedia of mathematics: definitions, formulas and diagrams.','free',NULL,0,1,230,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (24,'Overleaf (online LaTeX editor)','https://www.overleaf.com/','online','Research skills','researchers','Overleaf','Write papers and theses in LaTeX in your browser, with templates for many journals.','plan',NULL,0,1,240,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (25,'WRF: Weather Research and Forecasting model','https://www.mmm.ucar.edu/models/wrf','software','Meteorology & climate','researchers','NCAR / UCAR','The widely used open-source regional weather and climate model. Source code, documentation and user support.','open','*Starter notes: always follow the official guide for your version.*\n\n**Before you start**\n\n- WRF runs on Linux. On Windows use **WSL 2** (Windows Subsystem for Linux) with Ubuntu.\n- You need a Fortran and C compiler (`gfortran`, `gcc`), `make`, `perl`, `tcsh`, and the **netCDF** libraries (C and Fortran, built with the same compilers). For parallel runs also install MPI (for example OpenMPI).\n\n**Main steps**\n\n1. Download **WRF** and **WPS** (the pre-processing system) from the official GitHub pages.\n2. Set the `NETCDF` environment variable, run `./configure` in the WRF folder (a common choice is the gfortran *dmpar* option), then `./compile em_real`.\n3. In the WPS folder run `./configure` and `./compile`. Download the static geographical data and your forcing data (for example ERA5 or GFS).\n4. Run `geogrid.exe`, `ungrib.exe`, `metgrid.exe`, then in the WRF run folder `real.exe` and `wrf.exe`.\n\nThe **online tutorial** linked on this page walks through every step with sample data.',1,1,250,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (26,'WRF source code (GitHub)','https://github.com/wrf-model/WRF','software','Meteorology & climate','researchers','WRF model developers','Official WRF repository and releases.','open',NULL,0,1,260,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (27,'WRF Online Tutorial','https://www2.mmm.ucar.edu/wrf/OnLineTutorial/index.php','tutorial','Meteorology & climate','researchers','NCAR / UCAR','Step-by-step official tutorial: compiling WRF and WPS, preparing data and running your first case.','free',NULL,1,1,270,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (28,'WRF user support and documentation','https://www2.mmm.ucar.edu/wrf/users/','tutorial','Meteorology & climate','researchers','NCAR / UCAR','User guides, download pages, FAQs and forum for WRF and WPS.','free',NULL,0,1,280,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (29,'wrf-python','https://wrf-python.readthedocs.io/','software','Meteorology & climate','researchers','NCAR','Python package for reading, diagnosing and plotting WRF output.','open',NULL,0,1,290,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (30,'MPAS (Model for Prediction Across Scales)','https://mpas-dev.github.io/','software','Meteorology & climate','researchers','NCAR / LANL','Open-source global and regional atmosphere and ocean models.','open',NULL,0,1,300,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (31,'Community Earth System Model (CESM)','https://www.cesm.ucar.edu/','software','Meteorology & climate','researchers','NCAR','A fully coupled, community climate model with public releases and tutorials.','open',NULL,0,1,310,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (32,'Quantum ESPRESSO','https://www.quantum-espresso.org/','software','Physics','researchers','Quantum ESPRESSO Foundation','Open-source suite for electronic-structure calculations and materials modelling.','open','*Starter notes: always follow the official guide for your version.*\n\n**Getting it**\n\n- On Ubuntu or WSL: `sudo apt install quantum-espresso` (an easy start), or download the source from the official site and compile it.\n- You need **pseudopotential** files for every element in your calculation. The official site links to libraries of them.\n\n**A first calculation**\n\n1. Write an input file for `pw.x` (structure, k-points, cut-offs, pseudopotentials).\n2. Run: `mpirun -np 4 pw.x -in si.scf.in > si.scf.out`\n3. Read the total energy and convergence in the output file. Then follow the tutorials for band structures, relaxations and phonons.',1,1,320,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (33,'Quantum ESPRESSO: learn and tutorials','https://www.quantum-espresso.org/learn/','tutorial','Physics','researchers','Quantum ESPRESSO Foundation','Official learning page: tutorials, user guide and material from schools and workshops.','free',NULL,0,1,330,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (34,'LAMMPS molecular dynamics','https://www.lammps.org/','software','Physics','researchers','Sandia National Laboratories','Open-source classical molecular-dynamics simulator, with manual and examples.','open',NULL,0,1,340,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (35,'OpenFOAM','https://www.openfoam.com/','software','Physics','researchers','OpenFOAM','Open-source computational fluid dynamics toolbox.','open',NULL,0,1,350,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (36,'Python','https://www.python.org/downloads/','software','Computing & programming','all','Python Software Foundation','The programming language most used in science and data analysis.','open',NULL,0,1,360,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (37,'Miniforge (conda for scientific Python)','https://github.com/conda-forge/miniforge','software','Computing & programming','researchers','conda-forge','A minimal, free installer for managing scientific Python environments.','open','*Starter notes: always follow the official guide for your version.*\n\nA good, free setup for meteorology and physics work:\n\n1. Install **Miniforge** (conda) from the link on this page.\n2. Create an environment: `conda create -n sky python=3.12 numpy scipy matplotlib xarray netcdf4 metpy cartopy jupyterlab`\n3. Activate it with `conda activate sky` and start notebooks with `jupyter lab`.',0,1,370,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (38,'Jupyter','https://jupyter.org/','software','Computing & programming','all','Project Jupyter','Notebooks that combine code, results and notes: ideal for teaching and research.','open',NULL,0,1,380,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (39,'NumPy','https://numpy.org/','software','Computing & programming','all','NumPy','Fast array computing for Python.','open',NULL,0,1,390,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (40,'SciPy','https://scipy.org/','software','Computing & programming','all','SciPy','Scientific computing library: integration, optimisation, statistics and more.','open',NULL,0,1,400,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (41,'Matplotlib','https://matplotlib.org/','software','Computing & programming','all','Matplotlib','Publication-quality plots in Python.','open',NULL,0,1,410,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (42,'xarray','https://xarray.dev/','software','Meteorology & climate','researchers','xarray','Work with labelled multi-dimensional data such as netCDF climate files.','open',NULL,0,1,420,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (43,'MetPy','https://unidata.github.io/MetPy/','software','Meteorology & climate','researchers','Unidata','Python toolkit for meteorological calculations, skew-T diagrams and plotting.','open',NULL,0,1,430,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (44,'Cartopy','https://scitools.org.uk/cartopy/','software','Meteorology & climate','researchers','SciTools','Draw maps and project geospatial data in Python.','open',NULL,0,1,440,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (45,'GNU Octave','https://octave.org/','software','Computing & programming','all','GNU Octave','A free alternative to MATLAB for numerical computing.','open',NULL,0,1,450,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (46,'R','https://www.r-project.org/','software','Computing & programming','researchers','The R Foundation','Free language and environment for statistics and graphics.','open',NULL,0,1,460,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (47,'Julia','https://julialang.org/','software','Computing & programming','researchers','JuliaLang','A fast, free language for scientific computing.','open',NULL,0,1,470,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (48,'SageMath','https://www.sagemath.org/','software','Mathematics','all','SageMath','Free open-source mathematics system: algebra, calculus, number theory and more.','open',NULL,0,1,480,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (49,'Maxima','https://maxima.sourceforge.io/','software','Mathematics','all','Maxima','Free computer algebra system for symbolic mathematics.','open',NULL,0,1,490,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (50,'Panoply netCDF viewer','https://www.giss.nasa.gov/tools/panoply/','software','Meteorology & climate','all','NASA GISS','Quickly plot and explore netCDF, HDF and GRIB data without programming.','free',NULL,0,1,500,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (51,'CDO: Climate Data Operators','https://code.mpimet.mpg.de/projects/cdo','software','Meteorology & climate','researchers','Max Planck Institute for Meteorology','Command-line tools for processing climate and forecast data.','open',NULL,0,1,510,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (52,'ParaView','https://www.paraview.org/','software','Computing & programming','researchers','Kitware','Open-source visualisation of large scientific datasets.','open',NULL,0,1,520,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (53,'TeX Live (LaTeX for your computer)','https://www.tug.org/texlive/','software','Research skills','researchers','TeX Users Group','A complete free LaTeX distribution for Windows, macOS and Linux.','open',NULL,0,1,530,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (54,'Zotero','https://www.zotero.org/','software','Research skills','researchers','Zotero','Free reference manager: collect, cite and share your sources.','open',NULL,0,1,540,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (55,'LibreOffice','https://www.libreoffice.org/','software','Research skills','all','The Document Foundation','A free office suite for documents, spreadsheets and presentations.','open',NULL,0,1,550,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (56,'Git','https://git-scm.com/','software','Computing & programming','researchers','Git','Version control for your code and data analysis.','open',NULL,0,1,560,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (57,'Visual Studio Code','https://code.visualstudio.com/','software','Computing & programming','all','Microsoft','A free code editor that works well for Python, Fortran and LaTeX.','free',NULL,0,1,570,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (58,'Install Linux on Windows with WSL','https://learn.microsoft.com/windows/wsl/install','tutorial','Computing & programming','researchers','Microsoft Learn','How to run Ubuntu inside Windows: the easiest way to use WRF, Quantum ESPRESSO and other Linux-only models.','free',NULL,1,1,580,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (59,'Copernicus Climate Data Store (ERA5)','https://cds.climate.copernicus.eu/','data','Meteorology & climate','researchers','ECMWF / Copernicus','Free reanalysis and climate datasets, including ERA5, to drive models like WRF.','account',NULL,1,1,590,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (60,'NASA Earthdata','https://www.earthdata.nasa.gov/','data','Meteorology & climate','researchers','NASA','Satellite and Earth-science data from NASA missions.','account',NULL,0,1,600,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (61,'NOAA National Centers for Environmental Information','https://www.ncei.noaa.gov/','data','Meteorology & climate','researchers','NOAA','Weather, climate and ocean archives.','free',NULL,0,1,610,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (62,'NASA POWER','https://power.larc.nasa.gov/','data','Meteorology & climate','all','NASA','Solar and meteorological data for any location, useful for agriculture and energy studies.','free',NULL,0,1,620,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (63,'CHIRPS rainfall data','https://www.chc.ucsb.edu/data/chirps','data','Meteorology & climate','researchers','Climate Hazards Center, UC Santa Barbara','Long rainfall records for Africa and the tropics, widely used in drought and monsoon research.','free',NULL,0,1,630,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (64,'KNMI Climate Explorer','https://climexp.knmi.nl/','data','Meteorology & climate','researchers','KNMI','Analyse climate data online without installing anything.','free',NULL,0,1,640,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (65,'Open-Meteo','https://open-meteo.com/','data','Meteorology & climate','all','Open-Meteo','Free weather and climate API for non-commercial use.','free',NULL,0,1,650,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (66,'ESGF: CMIP climate model data','https://esgf.llnl.gov/','data','Meteorology & climate','researchers','Earth System Grid Federation','Access to CMIP climate model output.','account',NULL,0,1,660,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (67,'arXiv','https://arxiv.org/','papers','Physics','researchers','Cornell University','Free preprints in physics, mathematics, computer science and more.','free',NULL,1,1,670,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (68,'Semantic Scholar','https://www.semanticscholar.org/','papers','Research skills','researchers','Allen Institute for AI','Free AI-assisted search engine for scientific papers.','free',NULL,0,1,680,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (69,'DOAJ: Directory of Open Access Journals','https://doaj.org/','papers','Research skills','researchers','DOAJ','Find quality journals and articles that are free to read.','free',NULL,0,1,690,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (70,'Unpaywall','https://unpaywall.org/','papers','Research skills','researchers','OurResearch','Finds the legal free version of a paper when one exists.','free',NULL,0,1,700,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (71,'CORE','https://core.ac.uk/','papers','Research skills','researchers','The Open University','The world\'s largest collection of open-access research papers.','free',NULL,0,1,710,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (72,'Zenodo','https://zenodo.org/','papers','Research skills','researchers','CERN','Share and find research papers, data and software, each with a citable DOI.','free',NULL,0,1,720,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (73,'African Journals Online (AJOL)','https://www.ajol.info/','papers','Research skills','researchers','AJOL','Journals published in Africa, many free to read.','free',NULL,0,1,730,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (74,'Research4Life','https://www.research4life.org/','papers','Research skills','researchers','Research4Life','Free or low-cost access to journals and books for institutions in eligible countries (ask your library).','eligible',NULL,0,1,740,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `toolbox_links` (`id`, `title`, `url`, `kind`, `subject`, `audience`, `provider`, `description`, `license`, `notes`, `is_featured`, `is_visible`, `sort_order`, `link_status`, `link_checked_at`, `clicks`, `created_at`) VALUES (75,'ORCID','https://orcid.org/','papers','Research skills','researchers','ORCID','A free persistent identifier that connects you to your publications.','free',NULL,0,1,750,'unknown',NULL,0,'2026-10-10 07:49:15');
INSERT INTO `settings` (`skey`, `svalue`, `updated_at`) VALUES ('enable_learn','1','2026-10-10 07:49:15');
INSERT INTO `settings` (`skey`, `svalue`, `updated_at`) VALUES ('enable_news','1','2026-10-10 07:49:15');
INSERT INTO `settings` (`skey`, `svalue`, `updated_at`) VALUES ('enable_shop','1','2026-10-10 07:49:15');

SET FOREIGN_KEY_CHECKS = 1;
