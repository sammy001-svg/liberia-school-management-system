-- ============================================================
-- Student Documents
--   A single attachment area on the student profile holding any
--   supporting document: Transcript, Report Card, Letter of
--   Recommendation, Birth Certificate, and so on. `document_type`
--   is free text backed by a preset dropdown, so new categories
--   need no migration.
--
--   Note: the OUTGOING transcript (for a student leaving for another
--   school) is generated on demand from grades already in the system
--   and is not stored here.
-- ============================================================

-- ── FRESH INSTALL: run this if you have NOT already run add_student_transcripts.sql ──
CREATE TABLE IF NOT EXISTS student_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(80) NOT NULL DEFAULT 'Other',
    title VARCHAR(150) NOT NULL,
    issued_by VARCHAR(150) DEFAULT NULL,
    issue_date DATE DEFAULT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student_documents_student (student_id, document_type),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ── UPGRADE PATH ──────────────────────────────────────────────
-- ONLY if you already created `student_transcripts` from the earlier migration.
-- Uncomment these four lines and run them INSTEAD of the CREATE TABLE above;
-- they convert that table in place and keep any files already uploaded.
--
-- RENAME TABLE student_transcripts TO student_documents;
-- ALTER TABLE student_documents ADD COLUMN document_type VARCHAR(80) NOT NULL DEFAULT 'Transcript' AFTER student_id;
-- ALTER TABLE student_documents CHANGE previous_school issued_by VARCHAR(150) DEFAULT NULL;
-- ALTER TABLE student_documents ADD COLUMN issue_date DATE DEFAULT NULL AFTER issued_by;
