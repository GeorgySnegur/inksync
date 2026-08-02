-- Migration 003 — login brute-force lockout tracking
-- Run once against the live database:
--   psql -U <user> -d <dbname> -f migrations/003_login_attempts.sql
--
-- Adds a table used by backend/check_login.php to lock an account out for
-- 15 minutes after 5 consecutive failed login attempts.

CREATE TABLE IF NOT EXISTS login_attempts (
    username     VARCHAR(50) PRIMARY KEY,
    failed_count INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP
);
