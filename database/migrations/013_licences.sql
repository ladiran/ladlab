-- Phase 8: licence, author and rights confirmation for Free Resources.

ALTER TABLE resources ADD COLUMN license VARCHAR(20) NOT NULL DEFAULT 'own', ADD COLUMN author VARCHAR(200) NOT NULL DEFAULT '', ADD COLUMN source_url VARCHAR(500) NOT NULL DEFAULT '', ADD COLUMN rights_confirmed TINYINT(1) NOT NULL DEFAULT 1;
