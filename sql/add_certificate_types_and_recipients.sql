-- ============================================================
-- Generalizes certificates beyond students (to Teachers/Staff/anyone in
-- `users`), and replaces the hardcoded 4-value `type` ENUM with an
-- admin-managed `certificate_types` table that also records which
-- recipient category each type applies to (student/staff/any).
-- (run once against a database created before this migration)
-- ============================================================

CREATE TABLE certificate_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    recipient_category ENUM('student','staff','any') NOT NULL DEFAULT 'any',
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_name (tenant_id, name),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Seed the 4 existing hardcoded types per tenant, all 'student' (preserves current behavior exactly).
INSERT INTO certificate_types (tenant_id, name, recipient_category)
SELECT id, t.name, 'student' FROM tenants
CROSS JOIN (SELECT 'Completion' name UNION SELECT 'Promotion' UNION SELECT 'Graduation' UNION SELECT 'Achievement') t;

ALTER TABLE certificates
    ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER student_id,
    ADD COLUMN certificate_type_id INT UNSIGNED DEFAULT NULL AFTER user_id;

UPDATE certificates c JOIN students s ON c.student_id = s.id SET c.user_id = s.user_id;
UPDATE certificates c JOIN certificate_types ct
    ON ct.tenant_id = c.tenant_id
   AND ct.name = CASE c.type WHEN 'completion' THEN 'Completion' WHEN 'promotion' THEN 'Promotion'
                              WHEN 'graduation' THEN 'Graduation' WHEN 'achievement' THEN 'Achievement' END
SET c.certificate_type_id = ct.id;

-- Drop the old student_id FK (unnamed in schema.sql, so looked up dynamically —
-- same technique as sql/remove_university_features.sql).
SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'certificates' AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE certificates DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE certificates
    DROP KEY unique_student_year_type,
    DROP COLUMN student_id,
    DROP COLUMN type,
    MODIFY user_id INT UNSIGNED NOT NULL,
    MODIFY certificate_type_id INT UNSIGNED NOT NULL,
    ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD FOREIGN KEY (certificate_type_id) REFERENCES certificate_types(id),
    ADD UNIQUE KEY unique_recipient_year_type (tenant_id, user_id, academic_year_id, certificate_type_id);
