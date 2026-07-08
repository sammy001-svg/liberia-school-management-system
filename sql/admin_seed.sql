-- ============================================================
-- SCHOOL ADMIN SEED (single-school deployment, no Super Admin/Reseller)
-- ============================================================

-- Reuse the platform tenant slot as the one real school
UPDATE tenants SET
    name = 'Liberia School Management System',
    slug = 'liberia-school',
    institution_type = 'high_school',
    status = 'active'
WHERE id = 1;

-- Remove leftover Super Admin accounts from earlier seeds
DELETE FROM users WHERE email IN ('info@shanfixtechnology.com', 'admin@schoolms.com');

-- School Admin login
-- Email: admin@liberiaschool.com
-- Password: Admin@123
INSERT INTO users (tenant_id, role_id, name, email, password_hash, status)
VALUES (1, 4, 'School Admin', 'admin@liberiaschool.com', '$2y$10$hKTc7Uxly5KYnG2hazFmTegLChiXSo26paVbeUzbphDphOvNN7nv2', 'active')
ON DUPLICATE KEY UPDATE password_hash = '$2y$10$hKTc7Uxly5KYnG2hazFmTegLChiXSo26paVbeUzbphDphOvNN7nv2', status = 'active';
