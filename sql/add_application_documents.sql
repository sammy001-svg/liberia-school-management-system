-- ============================================================
-- Documents attached to a public online admission application.
--
-- Kept separate from `student_documents` because an applicant has no
-- students row yet. On approval, AdmissionController::approve() copies
-- these across to student_documents for the newly enrolled student, so
-- the paperwork follows them into enrolment automatically.
--
-- Rows cascade-delete with their application.
-- ============================================================

CREATE TABLE IF NOT EXISTS application_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(80) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_application_documents_app (application_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES admission_applications(id) ON DELETE CASCADE
);
