-- ============================================================
-- FINANCE — Phase 1: student accounts & receivables
--
-- Adds a per-student ledger as the spine of the finance module. Every
-- charge, payment, discount and scholarship becomes a dated entry, which
-- is what makes a statement of account, a running balance and an arrears
-- aging report possible — none of which can be derived from the invoice
-- table alone.
--
-- Sign convention: amount > 0 increases what the student owes (a charge);
-- amount < 0 reduces it (payment, discount, scholarship, waiver).
-- ============================================================

-- Catalogue of chargeable items, so optional extras (bus, lab, uniform,
-- exam) are billed only to the students who take them.
CREATE TABLE IF NOT EXISTS fee_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(60) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    default_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_optional TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fee_items_tenant (tenant_id, is_active),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    entry_date DATE NOT NULL,
    entry_type ENUM('charge','payment','discount','scholarship','waiver','refund','adjustment') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    invoice_id INT UNSIGNED DEFAULT NULL,
    payment_id INT UNSIGNED DEFAULT NULL,
    fee_item_id INT UNSIGNED DEFAULT NULL,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    term_id INT UNSIGNED DEFAULT NULL,
    reference VARCHAR(60) DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ledger_student (tenant_id, student_id, entry_date),
    KEY idx_ledger_invoice (invoice_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Scholarships / bursaries / concessions. Percentage or fixed, optionally
-- scoped to one academic year so an award doesn't silently roll forever.
CREATE TABLE IF NOT EXISTS scholarships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    award_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    award_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    academic_year_id INT UNSIGNED DEFAULT NULL,
    status ENUM('active','ended') NOT NULL DEFAULT 'active',
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scholarship_student (tenant_id, student_id, status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Installment plans: an agreed schedule against an invoice.
CREATE TABLE IF NOT EXISTS payment_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED DEFAULT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
    notes VARCHAR(255) DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_plan_student (tenant_id, student_id, status),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment_plan_installments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    seq TINYINT UNSIGNED NOT NULL DEFAULT 1,
    due_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','part_paid','paid','overdue') NOT NULL DEFAULT 'pending',
    KEY idx_installment_plan (plan_id, seq),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES payment_plans(id) ON DELETE CASCADE
);

-- Termly billing: tie an invoice to the period it covers so statements and
-- the income & expenditure report can be filtered by term or year.
ALTER TABLE invoices
    ADD COLUMN academic_year_id INT UNSIGNED DEFAULT NULL,
    ADD COLUMN term_id INT UNSIGNED DEFAULT NULL,
    ADD COLUMN fee_item_id INT UNSIGNED DEFAULT NULL;

-- Backfill the ledger from invoices and payments already recorded, so existing
-- balances and statements are correct from day one rather than starting at zero.
INSERT INTO student_ledger (tenant_id, student_id, entry_date, entry_type, description, amount, invoice_id, reference, created_at)
SELECT i.tenant_id, i.student_id, COALESCE(i.due_date, DATE(i.created_at)), 'charge',
       CONCAT('Invoice ', i.invoice_no), i.amount_due, i.id, i.invoice_no, i.created_at
FROM invoices i
WHERE NOT EXISTS (SELECT 1 FROM student_ledger l WHERE l.invoice_id = i.id AND l.entry_type = 'charge');

INSERT INTO student_ledger (tenant_id, student_id, entry_date, entry_type, description, amount, invoice_id, payment_id, reference, created_at)
SELECT p.tenant_id, i.student_id, DATE(p.paid_at), 'payment',
       CONCAT('Payment received', COALESCE(CONCAT(' — ', p.method), '')), -p.amount, p.invoice_id, p.id, p.reference, p.paid_at
FROM payments p JOIN invoices i ON p.invoice_id = i.id
WHERE NOT EXISTS (SELECT 1 FROM student_ledger l WHERE l.payment_id = p.id);

INSERT INTO student_ledger (tenant_id, student_id, entry_date, entry_type, description, amount, invoice_id, reference, created_at)
SELECT i.tenant_id, i.student_id, COALESCE(i.due_date, DATE(i.created_at)), 'discount',
       CONCAT('Discount on ', i.invoice_no), -i.discount, i.id, i.invoice_no, i.created_at
FROM invoices i
WHERE i.discount > 0
  AND NOT EXISTS (SELECT 1 FROM student_ledger l WHERE l.invoice_id = i.id AND l.entry_type = 'discount');

INSERT INTO permissions (name, module, action, description)
SELECT 'finance.accounts', 'finance', 'accounts', 'View student accounts, statements and arrears reports' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE module = 'finance' AND action = 'accounts');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module = 'finance' AND p.action = 'accounts'
WHERE r.name IN ('School Admin','Accountant')
  AND NOT EXISTS (SELECT 1 FROM role_permissions x WHERE x.role_id = r.id AND x.permission_id = p.id);
