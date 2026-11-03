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
