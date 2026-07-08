-- ============================================================
-- Adds comprehensive admission-detail fields to an existing students table
-- (run once against a database created before this migration)
-- ============================================================

ALTER TABLE students
    ADD COLUMN blood_group VARCHAR(5) DEFAULT NULL AFTER status,
    ADD COLUMN previous_school VARCHAR(200) DEFAULT NULL AFTER blood_group,
    ADD COLUMN guardian_name VARCHAR(150) DEFAULT NULL AFTER previous_school,
    ADD COLUMN guardian_phone VARCHAR(30) DEFAULT NULL AFTER guardian_name,
    ADD COLUMN guardian_relationship VARCHAR(50) DEFAULT NULL AFTER guardian_phone,
    ADD COLUMN emergency_contact_phone VARCHAR(30) DEFAULT NULL AFTER guardian_relationship;
