-- ============================================================
-- Disciplinary Module — tracks behavior incidents per student
-- (positive commendations or negative infractions), visible on the
-- student's profile and via a tenant-wide Discipline log.
-- ============================================================

CREATE TABLE disciplinary_records (
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

INSERT INTO permissions (name, module, action, description) VALUES
('discipline.manage', 'discipline', 'manage', 'Record and view student disciplinary/behavior incidents');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name IN ('School Admin','Teacher') AND r.tenant_id IS NULL AND p.name = 'discipline.manage';
