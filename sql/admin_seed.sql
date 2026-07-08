-- ============================================================
-- SUPER ADMIN SEED
-- ============================================================

-- Ensure the Platform Reseller exists (ID 1)
INSERT IGNORE INTO resellers (id, name, slug, email, status) 
VALUES (1, 'Platform', 'platform', 'platform@internal', 'active');

-- Ensure the Platform Tenant exists (ID 1)
INSERT IGNORE INTO tenants (id, name, slug, institution_type, status) 
VALUES (1, 'Platform Admin', 'platform', 'high_school', 'active');

-- Insert Super Admin User
-- Email: info@shanfixtechnology.com
-- Password: Sam@123@1s
INSERT INTO users (tenant_id, reseller_id, role_id, name, email, password_hash, status) 
VALUES (1, 1, 1, 'Super Admin', 'info@shanfixtechnology.com', '$2y$10$mvwQ38GxJmqgFLgwg6XAhOheCcqeixlSPLNYmnJzrQt1S00gESJQi', 'active')
ON DUPLICATE KEY UPDATE password_hash = '$2y$10$mvwQ38GxJmqgFLgwg6XAhOheCcqeixlSPLNYmnJzrQt1S00gESJQi', status = 'active';
