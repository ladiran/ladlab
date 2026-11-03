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
