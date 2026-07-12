-- ============================================================
-- Seeds the (previously unused) permissions/role_permissions tables so
-- that access control can move from hardcoded role-name checks to real
-- per-module/action permissions. role_permissions grants below reproduce
-- exactly the access each default role already had via requireAuth([...])
-- calls in app/Controllers/*.php, so existing users see no behavior change.
-- Also adds roles.manage, used to gate the new Role Management screen
-- (School Admin only).
-- (run once against a database created before this migration)
-- ============================================================

INSERT INTO permissions (name, module, action, description) VALUES
('academic.manage',    'academic',    'manage', 'Manage academic structure (subjects, curriculum)'),
('attendance.manage',  'attendance',  'manage', 'Mark and view attendance'),
('analytics.view',     'analytics',   'view',   'View school analytics and reports'),
('announcements.manage','announcements','manage','Post and manage announcements'),
('grades.manage',      'grades',      'manage', 'Enter and manage grades/exams'),
('timetable.view',     'timetable',   'view',   'View the class timetable'),
('timetable.manage',   'timetable',   'manage', 'Create and edit the timetable'),
('parents.manage',     'parents',     'manage', 'Manage parent records'),
('settings.manage',    'settings',    'manage', 'Manage school settings/branding'),
('finance.manage',     'finance',     'manage', 'Manage fees, invoices, payments, financial reports'),
('online_class.manage','online_class','manage', 'Manage scheduled online classes'),
('bus.manage',         'bus',         'manage', 'Manage transport routes, buses, drivers'),
('bus.fees',           'bus',         'fees',   'Manage transport fee assignments'),
('classes.view',       'classes',     'view',   'View classes'),
('classes.manage',     'classes',     'manage', 'Create and edit classes'),
('inventory.manage',   'inventory',   'manage', 'Manage school inventory'),
('academics.manage',   'academics',   'manage', 'Manage departments and subjects'),
('academics.view',     'academics',   'view',   'View departments and subjects'),
('certificates.manage','certificates','manage', 'Issue and manage certificates'),
('hr.manage',          'hr',          'manage', 'Manage staff payroll and leave applications'),
('homework.manage',    'homework',    'manage', 'Assign and manage homework'),
('teachers.manage',    'teachers',    'manage', 'Manage teacher records'),
('staff.manage',       'staff',       'manage', 'Create and edit staff records and salaries'),
('staff.delete',       'staff',       'delete', 'Delete staff records'),
('online_exam.manage', 'online_exam', 'manage', 'Create and manage online exams'),
('students.view',      'students',    'view',   'View student records'),
('students.edit',      'students',    'edit',   'Edit student academic records'),
('students.manage',    'students',    'manage', 'Create, delete, and bulk-import students'),
('roles.manage',       'roles',       'manage', 'Create and manage custom roles and permissions');

-- School Admin: every permission (matches 'School Admin' appearing in nearly every requireAuth() call today)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'School Admin' AND r.tenant_id IS NULL;

-- Teacher
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'Teacher' AND r.tenant_id IS NULL
AND p.name IN (
    'attendance.manage','analytics.view','announcements.manage','grades.manage','timetable.view',
    'online_class.manage','classes.view','academics.view','homework.manage','online_exam.manage',
    'students.view','students.edit'
);

-- Accountant
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'Accountant' AND r.tenant_id IS NULL
AND p.name IN ('finance.manage','bus.fees','hr.manage','staff.manage');

-- Staff
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'Staff' AND r.tenant_id IS NULL
AND p.name IN ('inventory.manage','students.view');

-- Student (school-panel timetable view only; the Student portal itself stays gated by requireAuth(['Student']))
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'Student' AND r.tenant_id IS NULL
AND p.name IN ('timetable.view');
