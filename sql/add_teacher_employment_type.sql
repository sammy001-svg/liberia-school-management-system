-- ============================================================
-- MIGRATION: Add missing columns to teachers table
-- Fixes: Unknown column 't.employment_type' in SELECT
-- Run this once on the live database.
-- ============================================================

-- Add national_id if missing (some live DBs don't have it)
ALTER TABLE teachers
    ADD COLUMN IF NOT EXISTS national_id VARCHAR(50) DEFAULT NULL;

-- Add employment_type (appended to end, no AFTER dependency)
ALTER TABLE teachers
    ADD COLUMN IF NOT EXISTS employment_type
        ENUM('full_time','part_time','contract') DEFAULT 'full_time';
