-- Migration 004 — privacy policy / terms-of-use consent tracking
-- Run once against the live database:
--   psql -U <user> -d <dbname> -f migrations/004_consent.sql
--
-- Adds the columns backend/consent.php and pages/consent.php use to gate
-- access behind explicit acceptance of the current privacy policy version
-- (see CONSENT_VERSION in backend/consent.php, kept in sync with the "Stand /
-- Last updated" date in pages/privacy.php).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS consent_accepted_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS consent_version     VARCHAR(20);
