-- Migration 001 — Phase 6 schema changes
-- Run once against the live database:
--   psql -U <user> -d <dbname> -f migrations/001_phase6.sql

-- 1. Add notes column to storyboard_panels
--    IF NOT EXISTS guard makes it safe to run twice
ALTER TABLE storyboard_panels
    ADD COLUMN IF NOT EXISTS notes TEXT;

-- 2. Allow different users to reuse the same project name.
--    Previously: UNIQUE(name) across the whole table.
--    Now:        UNIQUE(user_id, name) so each user has their own namespace.
ALTER TABLE projects
    DROP CONSTRAINT IF EXISTS projects_name_key;

ALTER TABLE projects
    ADD CONSTRAINT projects_user_name_unique UNIQUE (user_id, name);
