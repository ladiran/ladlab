-- Phase 9i: lecturers. A registered member can be made a lecturer (users.role = 'lecturer'); courses belong to a lecturer.

ALTER TABLE courses ADD COLUMN lecturer_id INT UNSIGNED NULL, ADD KEY idx_lecturer (lecturer_id), ADD CONSTRAINT fk_course_lecturer FOREIGN KEY (lecturer_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE users ADD COLUMN lecturer_since DATETIME NULL, ADD COLUMN lecturer_bio VARCHAR(600) NOT NULL DEFAULT '';
