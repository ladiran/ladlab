-- Phase 9e: author byline on blog posts, and articles written by registered users (approved by the administrator before they appear).

ALTER TABLE posts ADD COLUMN author_id INT UNSIGNED NULL, ADD COLUMN author_name VARCHAR(150) NOT NULL DEFAULT '', ADD COLUMN author_email VARCHAR(190) NOT NULL DEFAULT '', ADD COLUMN is_user_article TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN review_note VARCHAR(500) NOT NULL DEFAULT '', ADD COLUMN consent_at DATETIME NULL, ADD COLUMN submitted_at DATETIME NULL, ADD COLUMN reviewed_at DATETIME NULL, ADD KEY idx_author (author_id, status);

ALTER TABLE posts ADD CONSTRAINT fk_post_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL;
