-- ============================================================
-- Transcripts
--   UPLOAD   : store the transcript a transferring-in student brings
--              from their previous school (PDF/scan), attached to their
--              student record.
--   DOWNLOAD : the outgoing direction is generated on demand from the
--              grades already held in this system, so it needs no table.
-- ============================================================

CREATE TABLE IF NOT EXISTS student_transcripts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    previous_school VARCHAR(150) DEFAULT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);
