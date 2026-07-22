-- ============================================================
-- Online Application Module — a public, no-login admission
-- application form (linked from the login page next to Parent),
-- reviewed by School Admin, which on approval auto-enrols the
-- applicant as a student (same data path as manual admission).
-- ============================================================

CREATE TABLE admission_applications (
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

INSERT INTO permissions (name, module, action, description) VALUES
('admissions.manage', 'admissions', 'manage', 'Review and approve/reject online admission applications');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'School Admin' AND r.tenant_id IS NULL AND p.name = 'admissions.manage';
