-- Migration 002 — add hero_image_url to projects
-- Run once against the live database:
--   psql -U <user> -d <dbname> -f migrations/002_hero_image_url.sql
--
-- Bug fix: pages/projects.php and backend/upload_hero_image.php both
-- read/write projects.hero_image_url, but no migration ever created this
-- column. The resulting SQL error (PDO ERRMODE_EXCEPTION + display_errors
-- off) made the /projects page appear to fail to load entirely.

ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS hero_image_url TEXT;
