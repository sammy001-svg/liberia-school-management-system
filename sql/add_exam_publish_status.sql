-- ============================================================
-- Adds a draft/published gate on exams so grades entered by a teacher
-- aren't visible to students/parents until explicitly published.
-- Existing exams are grandfathered to 'published' so historical results
-- stay visible; only exams created after this migration start as drafts.
-- (run once against a database created before this migration)
-- ============================================================

ALTER TABLE exams
    ADD COLUMN status ENUM('draft','published') NOT NULL DEFAULT 'draft' AFTER pass_marks;

UPDATE exams SET status = 'published';
