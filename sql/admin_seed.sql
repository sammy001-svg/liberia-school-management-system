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
DELETE FROM users WHERE email IN ('info@shanfixtechnology.com', 'admin@schoolms.com', 'admin@liberiaschool.com');

-- School Admin login
-- Email: admin@celdiacademy.com
-- Password: Admin@123@1c
INSERT INTO users (tenant_id, role_id, name, email, password_hash, status)
VALUES (1, 4, 'School Admin', 'admin@celdiacademy.com', '$2y$10$kEoErxUs/g9jb.E.pN0p9.NLjD.6Ep2LXDuLTlhBPguTBOKOUGJtG', 'active')
ON DUPLICATE KEY UPDATE password_hash = '$2y$10$kEoErxUs/g9jb.E.pN0p9.NLjD.6Ep2LXDuLTlhBPguTBOKOUGJtG', status = 'active';
