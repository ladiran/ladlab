-- Phase 9h: a chat room for every course, and the option to lock a course for a period of time.

ALTER TABLE courses ADD COLUMN lock_from DATETIME NULL, ADD COLUMN lock_until DATETIME NULL, ADD COLUMN chat_enabled TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE enrolments ADD COLUMN chat_muted TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS course_chat (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_course (course_id, id),
  CONSTRAINT fk_chat_course FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
