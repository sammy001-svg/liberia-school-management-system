-- ============================================================
-- Lets a subject (course) be taught to more than one class. Adds the
-- course_classes join table, backfills it from the existing single
-- courses.class_id column, then drops that column.
-- (run once against a database created before this migration)
-- ============================================================

CREATE TABLE IF NOT EXISTS course_classes (
    course_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (course_id, class_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

INSERT IGNORE INTO course_classes (course_id, class_id)
SELECT id, class_id FROM courses WHERE class_id IS NOT NULL;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'class_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE courses DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE courses DROP COLUMN class_id;
