-- ============================================================
-- Adds an optional placement/rank field (e.g. "1st", "2nd Runner-up") to
-- individually-issued certificates.
-- (run once against a database created before this migration)
-- ============================================================

ALTER TABLE certificates ADD COLUMN placement VARCHAR(50) DEFAULT NULL AFTER remarks;
