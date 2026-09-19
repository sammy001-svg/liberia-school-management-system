-- ============================================================
-- Financial system (enrollment → billing → payments, expenses, extra collections,
-- arrears & overrides, statements, P&L, payroll).
--
-- The tables and columns are created automatically by app/Services/Finance.php the
-- first time any page loads (tracked in app_schema_versions), so this file only adds
-- what the app can't: the finance.approve permission.
--
-- finance.approve lets a role approve payments/expenses (when approval is switched on
-- in Finance Settings), approve arrears overrides, clear balances and approve/pay
-- payroll. School Admins can always do these; grant it to e.g. a Principal role.
-- It is granted here to every role that can already manage settings.
--
-- Safe to re-run.
-- ============================================================

INSERT INTO permissions (name, module, action, description)
SELECT 'finance.approve', 'finance', 'approve', 'Approve payments, expenses, arrears overrides and payroll' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module='finance' AND action='approve');

INSERT INTO role_permissions (role_id, permission_id)
SELECT rp.role_id, pa.id
  FROM role_permissions rp
  JOIN permissions ps ON ps.id = rp.permission_id AND ps.module = 'settings' AND ps.action = 'manage'
  JOIN permissions pa ON pa.module = 'finance' AND pa.action = 'approve'
 WHERE NOT EXISTS (SELECT 1 FROM role_permissions x WHERE x.role_id = rp.role_id AND x.permission_id = pa.id);
