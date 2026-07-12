-- ============================================================
-- Removes university-only schema now that the app is high-school only
-- (UniversityController -> AcademicsController, Lecturer role retired,
-- university.* permissions renamed to academics.*).
-- (run once against a database created before this migration)
-- ============================================================

-- Drop FKs that point at columns/tables we're about to remove.
-- Looked up dynamically since these were unnamed in schema.sql and MySQL
-- auto-generates the constraint name (e.g. courses_ibfk_2).

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'program_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE courses DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'program_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE students DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'students' AND COLUMN_NAME = 'department_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE students DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Dead tables (never populated by the app; enrollments references courses/
-- students/classes so it must go before programs).
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS programs;

-- Dead university-only columns.
ALTER TABLE courses DROP COLUMN program_id, DROP COLUMN semester_no;
ALTER TABLE students DROP COLUMN department_id, DROP COLUMN program_id, DROP COLUMN current_semester, DROP COLUMN cgpa;
ALTER TABLE fee_structures DROP COLUMN program_id;
ALTER TABLE tenants DROP COLUMN institution_type, DROP COLUMN current_semester;

-- Rename the surviving university.* permissions (Departments/Subjects
-- screens are high-school features now, just under the old permission key).
UPDATE permissions SET name = 'academics.manage', module = 'academics' WHERE name = 'university.manage';
UPDATE permissions SET name = 'academics.view',   module = 'academics' WHERE name = 'university.view';

-- Retire the unused Lecturer role (0 users hold it).
DELETE FROM role_permissions WHERE role_id IN (SELECT id FROM roles WHERE name = 'Lecturer');
DELETE FROM roles WHERE name = 'Lecturer';
