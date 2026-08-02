-- Celdi Academy report card structure
-- ---------------------------------------------------------------------------
-- The printed card has a fixed 11-column grade grid per academic year:
--
--   SUBJECTS | 1st Pd. 2nd Pd. 3rd Pd. Exam Sem.Ave. | 4th Pd. 5th Pd. 6th Pd. Exam Sem.Ave. | Yearly Ave.
--
-- Only 8 of those are recorded by teachers (the six marking periods and the two
-- semester exams); Sem.Ave./Yearly Ave. are always computed, never stored, so a
-- corrected period mark flows through to them automatically.
--
-- Each recorded column is an ordinary `exams` row — that keeps grade entry,
-- publishing and the existing grades table untouched — tagged with the slot it
-- fills on the card. Matching on this tag rather than on exam NAME means an
-- admin can rename "1st Pd." to anything without breaking the report.
--
-- Safe to re-run: every statement is guarded.

-- --- exams.report_column ----------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exams' AND COLUMN_NAME = 'report_column');
SET @s := IF(@c = 0,
  "ALTER TABLE exams ADD COLUMN report_column ENUM('p1','p2','p3','e1','p4','p5','p6','e2') DEFAULT NULL COMMENT 'Slot this exam fills on the Celdi report card; NULL = ordinary one-off exam'",
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- One exam per slot per class per year. Rows with report_column NULL are
-- unaffected (MySQL treats NULLs as distinct in a UNIQUE index), so ad-hoc
-- exams can still be created freely alongside the report-card ones.
SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exams' AND INDEX_NAME = 'uniq_exam_report_slot');
SET @s := IF(@c = 0,
  'ALTER TABLE exams ADD UNIQUE KEY uniq_exam_report_slot (tenant_id, class_id, academic_year_id, report_column)',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --- classes.report_style ---------------------------------------------------
-- Nursery and Day Care cards carry letter grades (E/S/I/N/C) in every cell,
-- including the Average row; every other level prints numbers. Stored per class
-- rather than inferred from the class name so a school can move a level either
-- way without a code change.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'classes' AND COLUMN_NAME = 'report_style');
SET @s := IF(@c = 0,
  "ALTER TABLE classes ADD COLUMN report_style ENUM('numeric','letter') NOT NULL DEFAULT 'numeric' COMMENT 'letter = print E/S/I/N/C instead of scores (early years)'",
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE classes SET report_style = 'letter'
 WHERE report_style = 'numeric'
   AND (grade_level IN ('Nursery', 'Day Care') OR name IN ('Nursery', 'Day Care'));

-- --- letterhead room --------------------------------------------------------
-- The report card prints the school's contact line verbatim, and Liberian schools
-- commonly list three numbers ("0777982384 | 0886644657 | 0777362210" is 36
-- characters) which overflowed the original varchar(30).
SET @c := (SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'phone');
SET @s := IF(@c < 120, 'ALTER TABLE tenants MODIFY COLUMN phone VARCHAR(120) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- --- promotion statement ----------------------------------------------------
-- Left-hand panel of sheet 1. One row per student per year; absent row prints a
-- blank statement for the sponsor to fill in by hand, which is how the school
-- currently uses it.
CREATE TABLE IF NOT EXISTS student_promotions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    -- Mirrors the four lettered options printed on the card.
    decision ENUM('promoted','condition','repeat','not_enroll') DEFAULT NULL,
    -- Free text for the "Promoted to" / "Condition in" blanks.
    decision_detail VARCHAR(120) DEFAULT NULL,
    satisfactory TINYINT(1) DEFAULT NULL COMMENT '1 = HAS, 0 = HAS NOT completed the work',
    closing_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_promotion (student_id, academic_year_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
