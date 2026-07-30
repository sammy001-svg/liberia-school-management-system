-- ============================================================
-- CATCH-UP MIGRATION  (safe to run repeatedly)
--
-- Brings a database that only ever ran schema.sql up to date with the
-- deployed code. Every step checks information_schema first and skips
-- anything already present, so this can be re-run after a partial
-- failure without "Duplicate column" errors and without clobbering data.
--
-- Fixes the /login <-> dashboard redirect loop, whose real cause is a
-- missing column throwing an exception that Router.php turns into a
-- redirect back to the referring page.
--
-- Run in phpMyAdmin -> (your database) -> SQL tab, or:
--   mysql -u USER -p DBNAME < sql/catch_up_migrations.sql
--
-- The 'SELECT 1' no-op branches are deliberate: MySQL only accepts a
-- limited set of statements in PREPARE, and SELECT is the portable one.
-- They print harmless single-row results as the script runs.
-- ============================================================

-- ── 1. users.username + unique key (add_login_credentials.sql) ──
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='username')=0,
    'ALTER TABLE users ADD COLUMN username VARCHAR(60) DEFAULT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='unique_username_tenant')=0,
    'ALTER TABLE users ADD UNIQUE KEY unique_username_tenant (username, tenant_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 2. tenants login-mode columns (add_login_credentials.sql) ──
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenants' AND COLUMN_NAME='student_login_mode')=0,
    'ALTER TABLE tenants ADD COLUMN student_login_mode ENUM(''email_password'',''admission_pin'') NOT NULL DEFAULT ''admission_pin''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenants' AND COLUMN_NAME='parent_login_mode')=0,
    'ALTER TABLE tenants ADD COLUMN parent_login_mode ENUM(''email_password'',''username_password'') NOT NULL DEFAULT ''username_password''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 3. tenants.restrict_parent_arrears  <-- fixes PARENT login loop ──
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenants' AND COLUMN_NAME='restrict_parent_arrears')=0,
    'ALTER TABLE tenants ADD COLUMN restrict_parent_arrears TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 4. exams.status  <-- fixes STUDENT login loop ──
-- Remember whether the column pre-existed, so the one-time backfill below
-- only runs on a genuinely fresh add and never re-publishes existing drafts.
SET @had_exam_status := (SELECT COUNT(*) FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='exams' AND COLUMN_NAME='status');

SET @sql := IF(@had_exam_status=0,
    'ALTER TABLE exams ADD COLUMN status ENUM(''draft'',''published'') NOT NULL DEFAULT ''draft''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@had_exam_status=0, 'UPDATE exams SET status = ''published''', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 5. course_classes many-to-many (add_course_classes.sql) ──
CREATE TABLE IF NOT EXISTS course_classes (
    course_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (course_id, class_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Copy the old one-to-one mapping across, then retire courses.class_id —
-- but only while that column still exists.
SET @has_class_id := (SELECT COUNT(*) FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='class_id');

SET @sql := IF(@has_class_id>0,
    'INSERT IGNORE INTO course_classes (course_id, class_id) SELECT id, class_id FROM courses WHERE class_id IS NOT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courses' AND COLUMN_NAME='class_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @sql := IF(@fk IS NOT NULL, CONCAT('ALTER TABLE courses DROP FOREIGN KEY ', @fk), 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@has_class_id>0, 'ALTER TABLE courses DROP COLUMN class_id', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── 6. disciplinary_records (add_disciplinary_records.sql) ──
CREATE TABLE IF NOT EXISTS disciplinary_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    incident_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    severity ENUM('minor','moderate','severe','commendation') NOT NULL DEFAULT 'minor',
    description TEXT DEFAULT NULL,
    action_taken TEXT DEFAULT NULL,
    reported_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── 7. admission_applications (add_admission_applications.sql) ──
CREATE TABLE IF NOT EXISTS admission_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    reference_no VARCHAR(30) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('male','female','other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    desired_class_id INT UNSIGNED DEFAULT NULL,
    guardian_name VARCHAR(150) NOT NULL,
    guardian_relationship VARCHAR(60) DEFAULT NULL,
    guardian_phone VARCHAR(30) NOT NULL,
    guardian_email VARCHAR(150) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    previous_school VARCHAR(150) DEFAULT NULL,
    previous_class VARCHAR(60) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    review_notes VARCHAR(255) DEFAULT NULL,
    student_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reference (reference_no),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (desired_class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

-- ── 8. Permissions added after the base seed (idempotent inserts) ──
INSERT INTO permissions (name, module, action, description)
SELECT 'grades.edit','grades','edit','Overwrite an already-recorded grade (School Admin only)' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='grades.edit');

INSERT INTO permissions (name, module, action, description)
SELECT 'discipline.manage','discipline','manage','Record and view student disciplinary/behavior incidents' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='discipline.manage');

INSERT INTO permissions (name, module, action, description)
SELECT 'admissions.manage','admissions','manage','Review and approve/reject online admission applications' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name='admissions.manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.name IN ('grades.edit','admissions.manage')
WHERE r.name='School Admin' AND r.tenant_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id=r.id AND rp.permission_id=p.id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name='discipline.manage'
WHERE r.name IN ('School Admin','Teacher') AND r.tenant_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id=r.id AND rp.permission_id=p.id);

-- ── Report: anything still MISSING needs its own migration run ──
SELECT 'users.username' AS check_item, IF(COUNT(*)>0,'OK','MISSING') AS state FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='username'
UNION ALL SELECT 'tenants.restrict_parent_arrears', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tenants' AND COLUMN_NAME='restrict_parent_arrears'
UNION ALL SELECT 'exams.status', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='exams' AND COLUMN_NAME='status'
UNION ALL SELECT 'course_classes', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='course_classes'
UNION ALL SELECT 'disciplinary_records', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='disciplinary_records'
UNION ALL SELECT 'admission_applications', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admission_applications'
UNION ALL SELECT 'certificate_types (run its own migration)', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='certificate_types'
UNION ALL SELECT 'permissions seeded', IF(COUNT(*)>0,'OK','MISSING') FROM permissions;
