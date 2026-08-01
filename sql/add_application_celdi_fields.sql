-- ============================================================
-- Expands the online application to mirror the school's paper
-- "Student Admission / Enrollment Form" section for section:
-- photos, mother/father details, previous-school principal and
-- sponsor, emergency contact, medical information, and the
-- authorised pick-up persons list.
--
-- Run AFTER add_admission_applications.sql.
-- ============================================================

ALTER TABLE admission_applications
    ADD COLUMN student_photo VARCHAR(255) DEFAULT NULL,
    ADD COLUMN parent_photo VARCHAR(255) DEFAULT NULL,
    ADD COLUMN mother_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN mother_phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN father_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN father_phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN previous_school_address VARCHAR(255) DEFAULT NULL,
    ADD COLUMN principal_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN principal_phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN sponsor_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN sponsor_phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN last_class VARCHAR(80) DEFAULT NULL,
    ADD COLUMN class_promoted_to VARCHAR(80) DEFAULT NULL,
    ADD COLUMN emergency_name VARCHAR(150) DEFAULT NULL,
    ADD COLUMN emergency_phone VARCHAR(30) DEFAULT NULL,
    ADD COLUMN emergency_relationship VARCHAR(60) DEFAULT NULL,
    ADD COLUMN emergency_address VARCHAR(255) DEFAULT NULL,
    ADD COLUMN emergency_email VARCHAR(150) DEFAULT NULL,
    ADD COLUMN medical_conditions TEXT DEFAULT NULL,
    ADD COLUMN allergies TEXT DEFAULT NULL;

-- The paper form collects mother and father separately, so the original
-- single "guardian" pair is now derived rather than typed. Relax it so a
-- form that only lists one parent still saves.
ALTER TABLE admission_applications
    MODIFY guardian_name VARCHAR(150) DEFAULT NULL,
    MODIFY guardian_phone VARCHAR(30) DEFAULT NULL;

-- "Name of persons authorized to pick up your child/children" — the paper
-- form provides three lines; a child table keeps it open-ended.
CREATE TABLE IF NOT EXISTS application_pickup_persons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
    KEY idx_pickup_application (application_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES admission_applications(id) ON DELETE CASCADE
);
