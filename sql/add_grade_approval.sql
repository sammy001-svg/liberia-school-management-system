-- Grade approval — the principal signs off before marks reach parents
-- ---------------------------------------------------------------------------
-- Previously a class's report card went straight from "marks entered" to
-- "released to parents" with one button. This inserts a review step:
--
--   entered  →  submitted for approval  →  approved  →  released
--                        ↑                     |
--                        └──── returned ───────┘
--
-- Approval is tracked on the same eight `exams` rows the report card is built
-- from, and flipped together, because the card is only meaningful as a set — a
-- principal approves "9th Grade, 2025-2026", not eight separate exams.
--
-- `status` (draft/published) is left alone: it still controls what parents can
-- see. Approval is a separate gate in front of it, so an approved card can still
-- be held back, and a released card can be withdrawn without losing the sign-off.
--
-- Safe to re-run: every statement is guarded.

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exams' AND COLUMN_NAME = 'approval_status');
SET @s := IF(@c = 0,
  "ALTER TABLE exams
     ADD COLUMN approval_status ENUM('entered','submitted','approved','returned') NOT NULL DEFAULT 'entered'
         COMMENT 'Report-card approval gate; only approved sets may be released',
     ADD COLUMN submitted_by INT UNSIGNED DEFAULT NULL,
     ADD COLUMN submitted_at DATETIME DEFAULT NULL,
     ADD COLUMN approved_by INT UNSIGNED DEFAULT NULL,
     ADD COLUMN approved_at DATETIME DEFAULT NULL,
     ADD COLUMN review_note VARCHAR(255) DEFAULT NULL",
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exams' AND INDEX_NAME = 'idx_exam_approval');
SET @s := IF(@c = 0,
  'ALTER TABLE exams ADD KEY idx_exam_approval (tenant_id, approval_status)',
  'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Anything already released predates this workflow; treat it as approved so the
-- new gate doesn't retroactively hide report cards parents can already see.
UPDATE exams
   SET approval_status = 'approved'
 WHERE report_column IS NOT NULL
   AND status = 'published'
   AND approval_status = 'entered';

-- --- permissions ------------------------------------------------------------
-- Separate from grades.manage so a teacher can enter and submit marks without
-- being able to approve their own work. Granted to School Admin (who acts as
-- principal here); a school wanting a dedicated Principal role can grant it
-- there from Roles & Permissions.
INSERT IGNORE INTO permissions (name, module, action, description) VALUES
  ('grades.approve', 'grades', 'approve', 'Approve or return submitted report card grades before release');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.module = 'grades' AND p.action = 'approve'
 WHERE r.tenant_id IS NULL AND r.name = 'School Admin';
