-- ============================================================
-- The `grades` table has never had a unique key, so the existing
-- `ON DUPLICATE KEY UPDATE` clause in GradeController::store() was dead code:
-- re-entering a grade for the same student/subject/exam silently inserted a
-- duplicate row instead of updating the original one. This is why grades
-- have effectively been "not editable" — re-entering never actually changed
-- anything, it just piled up duplicates.
--
-- This migration (1) removes duplicate rows already created by that bug,
-- keeping only the most recently-entered one per (student, course, exam),
-- (2) adds the missing unique key, and (3) adds a School-Admin-only
-- `grades.edit` permission so that, going forward, only Admin can overwrite
-- an already-recorded grade — Teachers can still enter a grade for the
-- first time via the same screen.
-- (run once against a database created before this migration)
-- ============================================================

DELETE g1 FROM grades g1
INNER JOIN grades g2
  ON g1.student_id = g2.student_id
 AND g1.course_id <=> g2.course_id
 AND g1.exam_id = g2.exam_id
 AND g1.id < g2.id
WHERE g1.exam_id IS NOT NULL;

ALTER TABLE grades ADD UNIQUE KEY unique_grade_entry (student_id, course_id, exam_id);

INSERT INTO permissions (name, module, action, description) VALUES
('grades.edit', 'grades', 'edit', 'Overwrite an already-recorded grade (School Admin only)');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'School Admin' AND r.tenant_id IS NULL AND p.name = 'grades.edit';
