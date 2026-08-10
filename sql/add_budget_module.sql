-- ============================================================
-- FINANCE — Budget module (Income & Expenses)
--
-- A budget is a named period holding income and expense lines. Each line
-- carries a budgeted figure; the actual is computed live from real data so
-- variance is never hand-maintained:
--   expense lines -> expenses.category
--   income lines  -> payments (fee collection) or incomes.category
--
-- `incomes` is new: the system already recorded expenses but had no way to
-- record income that isn't student fees (donations, grants, rentals), which
-- would have left every non-fee budget line permanently reading zero.
-- ============================================================

CREATE TABLE IF NOT EXISTS incomes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    category VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    income_date DATE NOT NULL,
    source VARCHAR(150) DEFAULT NULL,
    method VARCHAR(40) DEFAULT NULL,
    reference VARCHAR(60) DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_incomes_period (tenant_id, income_date),
    KEY idx_incomes_category (tenant_id, category),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_budgets_tenant (tenant_id, status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS budget_lines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    budget_id INT UNSIGNED NOT NULL,
    line_type ENUM('income','expense') NOT NULL,
    category VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    budgeted_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    -- Income only: 'fees' draws its actual from student fee payments,
    -- 'other' matches the incomes table on category.
    source ENUM('fees','other') NOT NULL DEFAULT 'other',
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_budget_lines (budget_id, line_type, sort_order),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

INSERT INTO permissions (name, module, action, description)
SELECT 'budget.manage', 'budget', 'manage', 'Create and manage school budgets, income and expenditure' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module = 'budget' AND action = 'manage');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module = 'budget' AND p.action = 'manage'
WHERE r.name IN ('School Admin','Accountant')
  AND NOT EXISTS (SELECT 1 FROM role_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id);
