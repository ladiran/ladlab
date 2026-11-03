-- Phase 9g: check-up questions after each lesson (a question bank per lesson), module summary scores and a complete-module PDF.

ALTER TABLE quizzes ADD COLUMN lesson_id INT UNSIGNED NULL, ADD KEY idx_lesson (lesson_id), ADD CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE;

ALTER TABLE course_modules ADD COLUMN pdf_path VARCHAR(255) NOT NULL DEFAULT '', ADD COLUMN pdf_title VARCHAR(190) NOT NULL DEFAULT '', ADD COLUMN pdf_size INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS lesson_checks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  question_ids TEXT NOT NULL,
  answers MEDIUMTEXT NULL,
  results MEDIUMTEXT NULL,
  earned DECIMAL(6,2) NULL,
  max_points SMALLINT UNSIGNED NULL,
  answered_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_lesson (user_id, lesson_id),
  CONSTRAINT fk_check_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_check_lesson FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
