-- ============================================================
-- Align the Principal role's permissions with the VPI role.
--
-- Run STEP 1 first to confirm the exact role names in your database, then
-- adjust the names in STEP 2/3 if they differ. Every statement is idempotent.
--
-- Permissions are cached in the session at login, so whoever holds the
-- Principal role must LOG OUT AND BACK IN before any of this takes effect.
-- ============================================================

-- ── STEP 1: what roles exist, and what does each hold? ──────────────
SELECT r.id, r.name, r.tenant_id,
       COUNT(rp.permission_id) AS permission_count,
       GROUP_CONCAT(CONCAT(p.module,'.',p.action) ORDER BY p.module, p.action SEPARATOR ', ') AS permissions
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id = r.id
LEFT JOIN permissions p ON p.id = rp.permission_id
GROUP BY r.id, r.name, r.tenant_id
ORDER BY r.tenant_id IS NULL DESC, r.name;

-- ── STEP 2: make sure the grade permissions exist at all ────────────
INSERT INTO permissions (name, module, action, description)
SELECT 'grades.manage', 'grades', 'manage', 'Enter grades and manage exams' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='grades' AND action='manage');

INSERT INTO permissions (name, module, action, description)
SELECT 'grades.edit', 'grades', 'edit', 'Overwrite an already-recorded grade' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='grades' AND action='edit');

-- ── STEP 3: give Principal everything VPI has ───────────────────────
-- Matches the two roles within the same tenant. Change the two names below if
-- STEP 1 shows them spelled differently (e.g. 'Vice Principal for Instructions').
INSERT INTO role_permissions (role_id, permission_id)
SELECT principal.id, rp.permission_id
FROM roles principal
JOIN roles vpi
  ON vpi.tenant_id <=> principal.tenant_id
 AND vpi.name = 'VPI'
JOIN role_permissions rp ON rp.role_id = vpi.id
WHERE principal.name = 'Principal'
  AND NOT EXISTS (
        SELECT 1 FROM role_permissions x
         WHERE x.role_id = principal.id AND x.permission_id = rp.permission_id);

-- ── STEP 4: explicitly guarantee grade view + edit for Principal ────
-- Independent of VPI, in case VPI itself is missing grades.edit.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.module = 'grades' AND p.action IN ('manage','edit')
WHERE r.name = 'Principal'
  AND NOT EXISTS (
        SELECT 1 FROM role_permissions x
         WHERE x.role_id = r.id AND x.permission_id = p.id);

-- ── STEP 5: verify — Principal should now match or exceed VPI ───────
SELECT r.name AS role,
       COUNT(rp.permission_id) AS permission_count,
       MAX(p.module='grades' AND p.action='manage') AS has_grades_manage,
       MAX(p.module='grades' AND p.action='edit')   AS has_grades_edit
FROM roles r
LEFT JOIN role_permissions rp ON rp.role_id = r.id
LEFT JOIN permissions p ON p.id = rp.permission_id
WHERE r.name IN ('Principal','VPI')
GROUP BY r.id, r.name;
