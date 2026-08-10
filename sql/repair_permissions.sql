-- ============================================================
-- REPAIR: complete the permission catalogue and grant it to School Admin.
--
-- Diagnosed live on bbas.ledasystems.site: the admin session held only a
-- handful of permissions (admissions, discipline, finance.accounts, store,
-- analytics, announcements) while students, teachers, classes, grades,
-- finance, staff, roles and settings were all absent — so those modules
-- redirected to /unauthorized. The base RBAC seed evidently aborted partway.
--
-- Every statement is idempotent: safe to run now and safe to re-run.
-- ============================================================

-- 1. Ensure every permission the application checks exists.
INSERT INTO permissions (name, module, action, description)
SELECT 'academic.manage', 'academic', 'manage', 'academic.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='academic' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'academics.manage', 'academics', 'manage', 'academics.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='academics' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'academics.view', 'academics', 'view', 'academics.view' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='academics' AND action='view');
INSERT INTO permissions (name, module, action, description)
SELECT 'admissions.manage', 'admissions', 'manage', 'admissions.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='admissions' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'analytics.view', 'analytics', 'view', 'analytics.view' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='analytics' AND action='view');
INSERT INTO permissions (name, module, action, description)
SELECT 'announcements.manage', 'announcements', 'manage', 'announcements.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='announcements' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'attendance.manage', 'attendance', 'manage', 'attendance.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='attendance' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'bus.fees', 'bus', 'fees', 'bus.fees' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='bus' AND action='fees');
INSERT INTO permissions (name, module, action, description)
SELECT 'bus.manage', 'bus', 'manage', 'bus.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='bus' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'certificates.manage', 'certificates', 'manage', 'certificates.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='certificates' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'classes.manage', 'classes', 'manage', 'classes.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='classes' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'classes.view', 'classes', 'view', 'classes.view' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='classes' AND action='view');
INSERT INTO permissions (name, module, action, description)
SELECT 'discipline.manage', 'discipline', 'manage', 'discipline.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='discipline' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'finance.accounts', 'finance', 'accounts', 'finance.accounts' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='finance' AND action='accounts');
INSERT INTO permissions (name, module, action, description)
SELECT 'finance.manage', 'finance', 'manage', 'finance.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='finance' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'grades.edit', 'grades', 'edit', 'grades.edit' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='grades' AND action='edit');
INSERT INTO permissions (name, module, action, description)
SELECT 'grades.manage', 'grades', 'manage', 'grades.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='grades' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'homework.manage', 'homework', 'manage', 'homework.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='homework' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'hr.manage', 'hr', 'manage', 'hr.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='hr' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'inventory.manage', 'inventory', 'manage', 'inventory.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='inventory' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'online_class.manage', 'online_class', 'manage', 'online_class.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='online_class' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'online_exam.manage', 'online_exam', 'manage', 'online_exam.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='online_exam' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'parents.manage', 'parents', 'manage', 'parents.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='parents' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'roles.manage', 'roles', 'manage', 'roles.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='roles' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'settings.manage', 'settings', 'manage', 'settings.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='settings' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'staff.delete', 'staff', 'delete', 'staff.delete' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='staff' AND action='delete');
INSERT INTO permissions (name, module, action, description)
SELECT 'staff.manage', 'staff', 'manage', 'staff.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='staff' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'store.manage', 'store', 'manage', 'store.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='store' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'store.sell', 'store', 'sell', 'store.sell' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='store' AND action='sell');
INSERT INTO permissions (name, module, action, description)
SELECT 'students.edit', 'students', 'edit', 'students.edit' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='students' AND action='edit');
INSERT INTO permissions (name, module, action, description)
SELECT 'students.manage', 'students', 'manage', 'students.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='students' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'students.view', 'students', 'view', 'students.view' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='students' AND action='view');
INSERT INTO permissions (name, module, action, description)
SELECT 'teachers.manage', 'teachers', 'manage', 'teachers.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='teachers' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'timetable.manage', 'timetable', 'manage', 'timetable.manage' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='timetable' AND action='manage');
INSERT INTO permissions (name, module, action, description)
SELECT 'timetable.view', 'timetable', 'view', 'timetable.view' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='timetable' AND action='view');

-- 2. Grant the full catalogue to School Admin.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'School Admin'
  AND NOT EXISTS (SELECT 1 FROM role_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id);

-- 3. Verify — should list all 35 as OK.
SELECT CONCAT(p.module,'.',p.action) AS permission,
       IF(rp.role_id IS NULL,'*** NOT GRANTED ***','OK') AS school_admin
FROM permissions p
LEFT JOIN roles r ON r.name='School Admin'
LEFT JOIN role_permissions rp ON rp.permission_id=p.id AND rp.role_id=r.id
ORDER BY p.module, p.action;
