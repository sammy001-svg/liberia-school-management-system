<?php
/**
 * Core of the financial system: schema upgrades, per-school finance settings, money
 * formatting, and the posting rules every finance screen shares.
 *
 * Money model
 * -----------
 *  enrollments  a student registered into a class for an academic year, with a student
 *               type (Regular, Ward, Scholarship…) and a category (new / old student).
 *  fee_bills    what a class owes for a year, per category, per student type — usually
 *               installments with a payment window (start/end date).
 *  invoices     one row per (enrollment, bill): the student's own copy of each bill.
 *               syncEnrollment() keeps them matching the bills, so editing Billing Setup
 *               re-prices every unpaid bill and posts the difference to the ledger.
 *  payments     money received against one invoice. Only status='active' ever counts;
 *               'pending' waits for approval, 'cancelled'/'rejected' are kept for audit.
 *  student_ledger  running account per student (charges +, credits −) used by
 *               statements, balances and the arrears aging report.
 *
 * Every write that changes what a student owes goes through this class, so invoices,
 * payments and the ledger can never drift apart.
 */
class Finance {
    public const CURRENCIES = ['LRD' => 'L$', 'USD' => 'US$'];

    private const SCHEMA_VERSION = 1;

    public const DEFAULT_STUDENT_TYPES = [
        'Regular', 'Admin. Ward', 'Discount on Tuition', 'Full Scholarship', 'Partial Scholarship',
        'Proprietor Ward', 'Scholarship', "Teacher's Ward", 'Ward',
    ];
    public const DEFAULT_EXPENSE_CATEGORIES = [
        'Bank charges', 'Business Registration', 'Cafeteria Expense', 'Charity/General', 'Communication',
        'Extracurricular activities', 'General School supplies', 'Goodwill', 'Health/Sanitation', 'ID Cards',
        'Miscellaneous', 'Permit/Taxes', 'Professional fees/Honorarium', 'Public Relations', 'Refillable ink',
        'Rent', 'Repair/Maintenance', 'Salaries', 'School Construction', 'School Permit', 'School Project',
        'Stationeries', 'Supplies', 'Testing/Evaluations', 'Texts Books', 'Training', 'Transportation',
        'Uniform Expense', 'Utilities/Subscriptions', 'Workmanship',
    ];
    public const DEFAULT_COLLECTION_CATEGORIES = [
        'Uniform', 'Entrance', 'Vacation School', 'Information Brochure', 'Texts Books', 'Income from Cafeteria',
        'Donations', 'Fundraising', 'Sponsorship', 'Miscellaneous', 'Charity',
    ];
    public const PAYMENT_METHODS = ['cash' => 'Cash', 'cheque' => 'Cheque', 'bank' => 'Bank Payment', 'pos' => 'POS', 'mobile' => 'Mobile Money'];

    private const DEFAULT_SETTINGS = [
        'default_currency'   => 'LRD',
        'secondary_currency' => '',   // '' = single-currency school
        'payment_approval'   => '0',  // payments wait for an approver before counting
        'expense_approval'   => '0',
        'payment_audit'      => '1',  // reconciliation view in Financial Records
        'daily_receipts'     => '1',  // daily receipts summary
        'block_arrears_enrollment' => '1', // prior-year debt needs an approved override to enroll
    ];

    // ── Schema ────────────────────────────────────────────────────────

    /** Runs pending schema upgrades once; afterwards it costs one indexed lookup per request. */
    public static function ensureSchema(Database $db): void {
        static $done = false;
        if ($done) { return; }
        $done = true;
        try {
            $db->execute("CREATE TABLE IF NOT EXISTS app_schema_versions (name VARCHAR(50) PRIMARY KEY, version INT NOT NULL)");
            $row = $db->fetchOne("SELECT version FROM app_schema_versions WHERE name='finance'");
            if ($row && (int)$row['version'] >= self::SCHEMA_VERSION) { return; }
            self::migrate($db);
            $db->execute("INSERT INTO app_schema_versions (name, version) VALUES ('finance', ?) ON DUPLICATE KEY UPDATE version=VALUES(version)", [self::SCHEMA_VERSION]);
        } catch (\Throwable $e) {
            // Never take a page down over an upgrade: log it; the SQL file can be run by hand.
            error_log('Finance schema upgrade failed: ' . $e->getMessage());
        }
    }

    private static function columns(Database $db, string $table): array {
        try { return array_column($db->fetchAll("SHOW COLUMNS FROM {$table}"), 'Type', 'Field'); }
        catch (\Throwable $e) { return []; }
    }

    private static function addColumns(Database $db, string $table, array $defs): void {
        $cols = self::columns($db, $table);
        if (!$cols) { return; }
        foreach ($defs as $col => $def) {
            if (!isset($cols[$col])) { $db->execute("ALTER TABLE {$table} ADD COLUMN {$col} {$def}"); }
        }
    }

    private static function migrate(Database $db): void {
        $engine = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        // Tables older installs may not have yet (normally from add_budget_module / add_finance_student_accounts).
        $db->execute("CREATE TABLE IF NOT EXISTS incomes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, category VARCHAR(120) NOT NULL,
            description VARCHAR(255) DEFAULT NULL, amount DECIMAL(14,2) NOT NULL DEFAULT 0.00, income_date DATE NOT NULL,
            source VARCHAR(150) DEFAULT NULL, method VARCHAR(40) DEFAULT NULL, reference VARCHAR(60) DEFAULT NULL,
            recorded_by INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_incomes_period (tenant_id, income_date), FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS student_ledger (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
            entry_date DATE NOT NULL, entry_type ENUM('charge','payment','discount','scholarship','waiver','refund','adjustment') NOT NULL,
            description VARCHAR(255) NOT NULL, amount DECIMAL(12,2) NOT NULL, invoice_id INT UNSIGNED DEFAULT NULL,
            payment_id INT UNSIGNED DEFAULT NULL, fee_item_id INT UNSIGNED DEFAULT NULL, academic_year_id INT UNSIGNED DEFAULT NULL,
            term_id INT UNSIGNED DEFAULT NULL, reference VARCHAR(60) DEFAULT NULL, recorded_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_ledger_student (tenant_id, student_id, entry_date),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");

        $db->execute("CREATE TABLE IF NOT EXISTS finance_settings (
            tenant_id INT UNSIGNED NOT NULL, setting_key VARCHAR(60) NOT NULL, setting_value VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (tenant_id, setting_key), FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS student_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(80) NOT NULL,
            sort_order SMALLINT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uniq_student_type (tenant_id, name), FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS finance_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL,
            kind ENUM('expense','collection') NOT NULL, name VARCHAR(120) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uniq_fin_cat (tenant_id, kind, name), FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS enrollments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
            academic_year_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, student_type_id INT UNSIGNED DEFAULT NULL,
            category ENUM('new','old') NOT NULL DEFAULT 'new', status ENUM('active','withdrawn') NOT NULL DEFAULT 'active',
            override_request_id INT UNSIGNED DEFAULT NULL, enrolled_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_enrollment (student_id, academic_year_id), KEY idx_enroll_class (tenant_id, academic_year_id, class_id),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS fee_bills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, academic_year_id INT UNSIGNED NOT NULL,
            class_id INT UNSIGNED NOT NULL, category ENUM('new','old') NOT NULL DEFAULT 'new', description VARCHAR(150) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00, currency CHAR(3) NOT NULL DEFAULT 'LRD',
            start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, applies_all TINYINT(1) NOT NULL DEFAULT 1,
            once_per_year TINYINT(1) NOT NULL DEFAULT 0, sort_order SMALLINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_fee_bills (tenant_id, academic_year_id, class_id, category),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS fee_bill_types (
            bill_id INT UNSIGNED NOT NULL, student_type_id INT UNSIGNED NOT NULL, PRIMARY KEY (bill_id, student_type_id),
            FOREIGN KEY (bill_id) REFERENCES fee_bills(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS arrears_override_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
            academic_year_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, student_type_id INT UNSIGNED DEFAULT NULL,
            category ENUM('new','old') NOT NULL DEFAULT 'old', outstanding VARCHAR(120) DEFAULT NULL, reason VARCHAR(500) DEFAULT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', requested_by INT UNSIGNED DEFAULT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, reviewed_by INT UNSIGNED DEFAULT NULL, reviewed_at DATETIME DEFAULT NULL,
            review_note VARCHAR(500) DEFAULT NULL, KEY idx_override (tenant_id, status),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS balance_writeoffs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL,
            invoice_id INT UNSIGNED DEFAULT NULL, academic_year_id INT UNSIGNED DEFAULT NULL, amount DECIMAL(12,2) NOT NULL,
            currency CHAR(3) NOT NULL DEFAULT 'LRD', reason VARCHAR(500) NOT NULL, created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_writeoffs (tenant_id, student_id),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");

        // Payroll
        $db->execute("CREATE TABLE IF NOT EXISTS payroll_profiles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
            department VARCHAR(100) DEFAULT NULL, employment_type VARCHAR(20) NOT NULL DEFAULT 'Full Time',
            employment_period VARCHAR(20) NOT NULL DEFAULT '12 Months', hire_date DATE DEFAULT NULL,
            contract_start DATE DEFAULT NULL, contract_end DATE DEFAULT NULL, base_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency CHAR(3) NOT NULL DEFAULT 'LRD', pay_frequency VARCHAR(20) NOT NULL DEFAULT 'Monthly',
            payment_method VARCHAR(20) NOT NULL DEFAULT 'Cash', bank_name VARCHAR(100) DEFAULT NULL, bank_account VARCHAR(60) DEFAULT NULL,
            momo_provider VARCHAR(30) DEFAULT NULL, momo_number VARCHAR(30) DEFAULT NULL, is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_payroll_profile (tenant_id, user_id), FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS payroll_components (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL,
            kind ENUM('allowance','deduction') NOT NULL, calc ENUM('fixed','percent') NOT NULL DEFAULT 'fixed',
            default_value DECIMAL(12,2) NOT NULL DEFAULT 0.00, applies_to ENUM('all','specific') NOT NULL DEFAULT 'all',
            is_taxable TINYINT(1) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS payroll_staff_components (
            profile_id INT UNSIGNED NOT NULL, component_id INT UNSIGNED NOT NULL, value DECIMAL(12,2) DEFAULT NULL,
            PRIMARY KEY (profile_id, component_id), FOREIGN KEY (profile_id) REFERENCES payroll_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (component_id) REFERENCES payroll_components(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS payroll_taxes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL,
            calc ENUM('percent','fixed') NOT NULL DEFAULT 'percent', value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            currency CHAR(3) NOT NULL DEFAULT 'LRD', applies_to ENUM('all','range','employment') NOT NULL DEFAULT 'all',
            min_salary DECIMAL(12,2) DEFAULT NULL, max_salary DECIMAL(12,2) DEFAULT NULL, employment_types VARCHAR(120) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1, FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");
        $db->execute("CREATE TABLE IF NOT EXISTS payroll_records (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, profile_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, currency CHAR(3) NOT NULL DEFAULT 'LRD',
            base_pay DECIMAL(12,2) NOT NULL DEFAULT 0, allowances DECIMAL(12,2) NOT NULL DEFAULT 0, gross_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            deductions DECIMAL(12,2) NOT NULL DEFAULT 0, tax DECIMAL(12,2) NOT NULL DEFAULT 0, net_pay DECIMAL(12,2) NOT NULL DEFAULT 0,
            breakdown TEXT, status ENUM('calculated','approved','paid') NOT NULL DEFAULT 'calculated',
            created_by INT UNSIGNED DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            approved_by INT UNSIGNED DEFAULT NULL, approved_at DATETIME DEFAULT NULL, paid_by INT UNSIGNED DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL, expense_id INT UNSIGNED DEFAULT NULL,
            UNIQUE KEY uniq_payroll_period (profile_id, period_start, period_end), KEY idx_payroll_records (tenant_id, status),
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) $engine");

        // Existing tables gain what the financial system needs.
        self::addColumns($db, 'invoices', [
            'enrollment_id' => 'INT UNSIGNED DEFAULT NULL', 'fee_bill_id' => 'INT UNSIGNED DEFAULT NULL',
            'academic_year_id' => 'INT UNSIGNED DEFAULT NULL', 'currency' => 'CHAR(3) DEFAULT NULL',
            'description' => 'VARCHAR(150) DEFAULT NULL',
        ]);
        self::addColumns($db, 'payments', [
            'currency' => 'CHAR(3) DEFAULT NULL', 'payment_date' => 'DATE DEFAULT NULL',
            "status" => "ENUM('active','pending','cancelled','rejected') NOT NULL DEFAULT 'active'",
            'is_arrears' => 'TINYINT(1) NOT NULL DEFAULT 0', 'receipt_file' => 'VARCHAR(255) DEFAULT NULL',
            'cancelled_at' => 'DATETIME DEFAULT NULL', 'cancelled_by' => 'INT UNSIGNED DEFAULT NULL', 'cancel_reason' => 'VARCHAR(255) DEFAULT NULL',
            'approved_at' => 'DATETIME DEFAULT NULL', 'approved_by' => 'INT UNSIGNED DEFAULT NULL',
        ]);
        self::addColumns($db, 'expenses', [
            'currency' => 'CHAR(3) DEFAULT NULL', 'academic_year_id' => 'INT UNSIGNED DEFAULT NULL',
            'payee_user_id' => 'INT UNSIGNED DEFAULT NULL',
            "status" => "ENUM('active','pending','cancelled','rejected') NOT NULL DEFAULT 'active'",
            'cancelled_at' => 'DATETIME DEFAULT NULL', 'cancelled_by' => 'INT UNSIGNED DEFAULT NULL', 'cancel_reason' => 'VARCHAR(255) DEFAULT NULL',
            'approved_at' => 'DATETIME DEFAULT NULL', 'approved_by' => 'INT UNSIGNED DEFAULT NULL',
        ]);
        self::addColumns($db, 'incomes', [
            "payer_type" => "ENUM('student','parent','staff','other') NOT NULL DEFAULT 'other'", 'payer_id' => 'INT UNSIGNED DEFAULT NULL',
            'balance_owed' => 'DECIMAL(14,2) DEFAULT NULL', 'currency' => 'CHAR(3) DEFAULT NULL',
            'academic_year_id' => 'INT UNSIGNED DEFAULT NULL', 'receipt_file' => 'VARCHAR(255) DEFAULT NULL',
            "status" => "ENUM('active','cancelled') NOT NULL DEFAULT 'active'",
            'cancelled_at' => 'DATETIME DEFAULT NULL', 'cancelled_by' => 'INT UNSIGNED DEFAULT NULL', 'cancel_reason' => 'VARCHAR(255) DEFAULT NULL',
        ]);
        self::addColumns($db, 'student_ledger', ['currency' => 'CHAR(3) DEFAULT NULL']);

        // Methods were fixed ENUMs that couldn't hold POS / mobile money; money columns get headroom for LRD.
        $pay = self::columns($db, 'payments');
        if (isset($pay['method']) && str_starts_with(strtolower($pay['method']), 'enum')) {
            $db->execute("ALTER TABLE payments MODIFY COLUMN method VARCHAR(30) DEFAULT 'cash'");
        }
        $db->execute("ALTER TABLE payments MODIFY COLUMN amount DECIMAL(14,2) NOT NULL");
        $exp = self::columns($db, 'expenses');
        if (isset($exp['method']) && str_starts_with(strtolower($exp['method']), 'enum')) {
            $db->execute("ALTER TABLE expenses MODIFY COLUMN method VARCHAR(30) DEFAULT 'cash'");
        }
        $db->execute("ALTER TABLE expenses MODIFY COLUMN amount DECIMAL(14,2) NOT NULL");
        $db->execute("ALTER TABLE invoices MODIFY COLUMN amount_due DECIMAL(14,2) NOT NULL");
        $db->execute("ALTER TABLE invoices MODIFY COLUMN amount_paid DECIMAL(14,2) DEFAULT 0.00");
        $db->execute("UPDATE payments SET payment_date = DATE(paid_at) WHERE payment_date IS NULL");
    }

    // ── Per-school setup ──────────────────────────────────────────────

    /** Seeds a school's student types and categories the first time finance is opened. */
    public static function bootTenant(Database $db, int $tid): void {
        if (!$tid) { return; }
        self::ensureSchema($db);
        try {
            if (!$db->fetchOne("SELECT id FROM student_types WHERE tenant_id=? LIMIT 1", [$tid])) {
                foreach (self::DEFAULT_STUDENT_TYPES as $i => $name) {
                    $db->execute("INSERT IGNORE INTO student_types (tenant_id,name,sort_order) VALUES (?,?,?)", [$tid, $name, $i]);
                }
            }
            if (!$db->fetchOne("SELECT id FROM finance_categories WHERE tenant_id=? LIMIT 1", [$tid])) {
                // Categories the school already used come along, so nothing recorded goes uncategorised.
                $expense = array_merge(self::DEFAULT_EXPENSE_CATEGORIES, array_column(
                    $db->fetchAll("SELECT DISTINCT category FROM expenses WHERE tenant_id=? AND category<>''", [$tid]), 'category'));
                $collection = array_merge(self::DEFAULT_COLLECTION_CATEGORIES, array_column(
                    $db->fetchAll("SELECT DISTINCT category FROM incomes WHERE tenant_id=? AND category<>''", [$tid]), 'category'));
                foreach (array_unique($expense) as $n) { $db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,'expense',?)", [$tid, $n]); }
                foreach (array_unique($collection) as $n) { $db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,'collection',?)", [$tid, $n]); }
            }
            if (!$db->fetchOne("SELECT id FROM payroll_components WHERE tenant_id=? LIMIT 1", [$tid])) {
                foreach ([
                    ['Housing Allowance', 'allowance', 'fixed', 0, 1], ['Medical Allowance', 'allowance', 'fixed', 0, 1],
                    ['Overtime Pay', 'allowance', 'percent', 50, 1], ['Performance Bonus', 'allowance', 'fixed', 0, 1],
                    ['Transportation Allowance', 'allowance', 'fixed', 0, 1], ['Health Insurance', 'deduction', 'fixed', 0, 0],
                    ['Loan Repayment', 'deduction', 'fixed', 0, 0], ['Retirement Fund', 'deduction', 'percent', 0, 0],
                ] as [$n, $k, $c, $v, $tax]) {
                    $db->execute("INSERT INTO payroll_components (tenant_id,name,kind,calc,default_value,is_taxable,is_active) VALUES (?,?,?,?,?,?,0)", [$tid, $n, $k, $c, $v, $tax]);
                }
            }
        } catch (\Throwable $e) {
            error_log('Finance boot failed: ' . $e->getMessage());
        }
    }

    public static function settings(Database $db, int $tid): array {
        $s = self::DEFAULT_SETTINGS;
        try {
            foreach ($db->fetchAll("SELECT setting_key, setting_value FROM finance_settings WHERE tenant_id=?", [$tid]) as $r) {
                $s[$r['setting_key']] = (string)$r['setting_value'];
            }
        } catch (\Throwable $e) {}
        return $s;
    }

    public static function saveSetting(Database $db, int $tid, string $key, string $value): void {
        $db->execute("INSERT INTO finance_settings (tenant_id,setting_key,setting_value) VALUES (?,?,?)
                      ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)", [$tid, $key, $value]);
    }

    /** Currencies this school works in, default first. */
    public static function currencies(array $settings): array {
        $list = [$settings['default_currency'] ?: 'LRD'];
        if (!empty($settings['secondary_currency']) && $settings['secondary_currency'] !== $list[0]) {
            $list[] = $settings['secondary_currency'];
        }
        return $list;
    }

    public static function money(float|string|null $amount, ?string $currency = 'LRD'): string {
        $currency = $currency ?: 'LRD';
        $sym = self::CURRENCIES[$currency] ?? $currency;
        $a = (float)$amount;
        return ($a < 0 ? '−' : '') . $sym . ' ' . number_format(abs($a), 2);
    }

    /** The academic year marked current, or null. */
    public static function currentYear(Database $db, int $tid): ?array {
        return $db->fetchOne("SELECT * FROM academic_years WHERE tenant_id=? AND is_current=1 LIMIT 1", [$tid]) ?: null;
    }

    public static function canApprove(): bool {
        $perms = $_SESSION['permissions'] ?? [];
        return in_array('finance.approve', $perms, true)
            || in_array($_SESSION['role'] ?? '', ['School Admin', 'Super Admin'], true);
    }

    // ── Ledger ────────────────────────────────────────────────────────

    public static function ledger(Database $db, int $tid, int $studentId, string $type, string $description, float $amount, array $links = []): void {
        if (abs($amount) < 0.005) { return; }
        $db->insert(
            "INSERT INTO student_ledger (tenant_id,student_id,entry_date,entry_type,description,amount,invoice_id,payment_id,academic_year_id,reference,currency,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [$tid, $studentId, $links['date'] ?? date('Y-m-d'), $type, mb_substr($description, 0, 255), round($amount, 2),
             $links['invoice_id'] ?? null, $links['payment_id'] ?? null, $links['academic_year_id'] ?? null,
             $links['reference'] ?? null, $links['currency'] ?? null, $_SESSION['user_id'] ?? null]
        );
    }

    // ── Billing ───────────────────────────────────────────────────────

    /** Bills that apply to an enrollment (its class, year, category and student type). */
    public static function billsFor(Database $db, array $enrollment): array {
        return $db->fetchAll(
            "SELECT b.* FROM fee_bills b
             WHERE b.tenant_id=? AND b.academic_year_id=? AND b.class_id=? AND b.category=?
               AND (b.applies_all=1 OR EXISTS (SELECT 1 FROM fee_bill_types t WHERE t.bill_id=b.id AND t.student_type_id=?))
             ORDER BY b.sort_order, b.start_date, b.id",
            [$enrollment['tenant_id'], $enrollment['academic_year_id'], $enrollment['class_id'], $enrollment['category'], (int)$enrollment['student_type_id']]
        );
    }

    /** Recomputes an invoice's paid amount and status from its active payments. */
    public static function refreshInvoice(Database $db, int $invoiceId): void {
        $inv = $db->fetchOne("SELECT * FROM invoices WHERE id=?", [$invoiceId]);
        if (!$inv) { return; }
        $paid = (float)($db->fetchOne("SELECT COALESCE(SUM(amount),0) t FROM payments WHERE invoice_id=? AND status='active'", [$invoiceId])['t'] ?? 0);
        $due = (float)$inv['amount_due'] - (float)$inv['discount'];
        if ($inv['status'] === 'waived') {
            $status = 'waived';
        } elseif ($due <= 0.005 || $paid >= $due - 0.005) {
            $status = 'paid';
        } else {
            $status = $paid > 0 ? 'partial' : 'unpaid';
        }
        $db->execute("UPDATE invoices SET amount_paid=?, status=? WHERE id=?", [$paid, $status, $invoiceId]);
    }

    /**
     * Makes an enrollment's invoices match its current bills: creates missing ones,
     * re-prices changed ones (posting the difference to the ledger) and removes unpaid
     * invoices for bills that no longer apply. Paid invoices are never removed.
     * Returns [created, updated, removed, kept_paid].
     */
    public static function syncEnrollment(Database $db, int $enrollmentId): array {
        $e = $db->fetchOne("SELECT * FROM enrollments WHERE id=?", [$enrollmentId]);
        if (!$e) { return [0, 0, 0, 0]; }
        $tid = (int)$e['tenant_id'];
        $bills = $e['status'] === 'active' ? self::billsFor($db, $e) : [];
        $existing = [];
        foreach ($db->fetchAll("SELECT * FROM invoices WHERE enrollment_id=? AND fee_bill_id IS NOT NULL", [$enrollmentId]) as $inv) {
            $existing[(int)$inv['fee_bill_id']] = $inv;
        }
        $created = $updated = $removed = $kept = 0;
        $billIds = array_flip(array_map('intval', array_column($bills, 'id')));
        foreach ($bills as $b) {
            $bid = (int)$b['id'];
            $links = ['academic_year_id' => $e['academic_year_id'], 'currency' => $b['currency']];
            // A class change swaps the bills; reuse the student's invoice for the same item
            // (matched by description) rather than billing it twice.
            if (!isset($existing[$bid])) {
                foreach ($existing as $oldBid => $cand) {
                    if ($cand['description'] === $b['description'] && !isset($billIds[$oldBid])) {
                        $db->execute("UPDATE invoices SET fee_bill_id=? WHERE id=?", [$bid, $cand['id']]);
                        $cand['fee_bill_id'] = $bid;
                        if ($b['once_per_year']) { $cand['amount_due'] = $b['amount']; } // already charged this year: keep it
                        $existing[$bid] = $cand;
                        unset($existing[$oldBid]);
                        break;
                    }
                }
            }
            if (isset($existing[$bid])) {
                $inv = $existing[$bid];
                unset($existing[$bid]);
                $diff = round((float)$b['amount'] - (float)$inv['amount_due'], 2);
                if (abs($diff) >= 0.005 || $inv['description'] !== $b['description'] || $inv['due_date'] !== $b['end_date'] || $inv['currency'] !== $b['currency']) {
                    $db->execute("UPDATE invoices SET amount_due=?, description=?, due_date=?, currency=? WHERE id=?",
                        [$b['amount'], $b['description'], $b['end_date'], $b['currency'], $inv['id']]);
                    self::ledger($db, $tid, (int)$e['student_id'], 'adjustment', "Bill changed: {$b['description']}", $diff,
                        $links + ['invoice_id' => $inv['id'], 'reference' => $inv['invoice_no']]);
                    self::refreshInvoice($db, (int)$inv['id']);
                    $updated++;
                }
                continue;
            }
            $no = 'BILL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $invId = (int)$db->insert(
                "INSERT INTO invoices (tenant_id,student_id,enrollment_id,fee_bill_id,academic_year_id,invoice_no,description,amount_due,currency,due_date,status,notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$tid, $e['student_id'], $enrollmentId, $bid, $e['academic_year_id'], $no, $b['description'], $b['amount'],
                 $b['currency'], $b['end_date'], (float)$b['amount'] > 0 ? 'unpaid' : 'paid', $b['description']]
            );
            self::ledger($db, $tid, (int)$e['student_id'], 'charge', $b['description'], (float)$b['amount'],
                $links + ['invoice_id' => $invId, 'reference' => $no, 'date' => $b['start_date'] ?: date('Y-m-d')]);
            $created++;
        }
        // Bills that no longer apply: drop unpaid copies, keep anything money was received against.
        foreach ($existing as $inv) {
            $hasPayments = (int)($db->fetchOne("SELECT COUNT(*) c FROM payments WHERE invoice_id=? AND status IN ('active','pending')", [$inv['id']])['c'] ?? 0);
            if ($hasPayments) { $kept++; continue; }
            self::ledger($db, $tid, (int)$e['student_id'], 'adjustment', "Bill removed: {$inv['description']}", -(float)$inv['amount_due'],
                ['invoice_id' => $inv['id'], 'reference' => $inv['invoice_no'], 'academic_year_id' => $e['academic_year_id'], 'currency' => $inv['currency']]);
            $db->execute("DELETE FROM invoices WHERE id=?", [$inv['id']]);
            $removed++;
        }
        return [$created, $updated, $removed, $kept];
    }

    /** Re-syncs every active enrollment a bill change can affect. */
    public static function syncClass(Database $db, int $tid, int $yearId, int $classId): int {
        $n = 0;
        foreach ($db->fetchAll("SELECT id FROM enrollments WHERE tenant_id=? AND academic_year_id=? AND class_id=?", [$tid, $yearId, $classId]) as $e) {
            self::syncEnrollment($db, (int)$e['id']);
            $n++;
        }
        return $n;
    }

    /**
     * What a student still owes from years before $beforeYearId, per currency — the
     * "arrears" that block enrollment until paid, written off or overridden.
     */
    public static function priorArrears(Database $db, int $tid, int $studentId, int $beforeYearId): array {
        $rows = $db->fetchAll(
            "SELECT COALESCE(i.currency, ?) cur, SUM(i.amount_due - i.discount - i.amount_paid) bal
             FROM invoices i JOIN academic_years ay ON ay.id=i.academic_year_id
             WHERE i.tenant_id=? AND i.student_id=? AND i.status IN ('unpaid','partial','overdue')
               AND ay.start_date < (SELECT start_date FROM academic_years WHERE id=?)
             GROUP BY cur HAVING bal > 0.005",
            [self::settings($db, $tid)['default_currency'], $tid, $studentId, $beforeYearId]
        );
        return array_column($rows, 'bal', 'cur');
    }

    // ── Payments ──────────────────────────────────────────────────────

    /**
     * Records money received against one invoice. With payment approval switched on, a
     * payment from someone who can't approve is held as 'pending' and changes nothing yet.
     */
    public static function recordPayment(Database $db, int $tid, int $invoiceId, float $amount, array $data): int {
        $inv = $db->fetchOne("SELECT * FROM invoices WHERE id=? AND tenant_id=?", [$invoiceId, $tid]);
        if (!$inv) { throw new \RuntimeException('That bill no longer exists.'); }
        $settings = self::settings($db, $tid);
        $pending = $settings['payment_approval'] === '1' && !self::canApprove();

        $year = self::currentYear($db, $tid);
        $isArrears = 0;
        if ($year && $inv['academic_year_id'] && (int)$inv['academic_year_id'] !== (int)$year['id']) {
            $billYear = $db->fetchOne("SELECT start_date FROM academic_years WHERE id=?", [$inv['academic_year_id']]);
            $isArrears = $billYear && $billYear['start_date'] < $year['start_date'] ? 1 : 0;
        }
        $date = $data['payment_date'] ?? date('Y-m-d');
        $paymentId = (int)$db->insert(
            "INSERT INTO payments (tenant_id,invoice_id,amount,currency,method,reference,received_by,notes,paid_at,payment_date,status,is_arrears,receipt_file)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$tid, $invoiceId, round($amount, 2), $inv['currency'] ?: $settings['default_currency'], $data['method'] ?? 'cash',
             $data['reference'] ?? null, $_SESSION['user_id'] ?? null, $data['notes'] ?? null, $date . ' ' . date('H:i:s'), $date,
             $pending ? 'pending' : 'active', $isArrears, $data['receipt_file'] ?? null]
        );
        if (!$pending) { self::applyPayment($db, $tid, $paymentId); }
        return $paymentId;
    }

    /** Makes an active payment count: invoice paid-to-date and the student's ledger credit. */
    private static function applyPayment(Database $db, int $tid, int $paymentId): void {
        $p = $db->fetchOne("SELECT p.*, i.student_id, i.invoice_no, i.description, i.academic_year_id AS year_id FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE p.id=?", [$paymentId]);
        if (!$p) { return; }
        self::refreshInvoice($db, (int)$p['invoice_id']);
        $label = self::PAYMENT_METHODS[$p['method']] ?? ucfirst((string)$p['method']);
        self::ledger($db, $tid, (int)$p['student_id'], 'payment', 'Payment — ' . ($p['description'] ?: $p['invoice_no']) . " ({$label})", -(float)$p['amount'],
            ['invoice_id' => $p['invoice_id'], 'payment_id' => $paymentId, 'reference' => $p['reference'] ?: ('PMT-' . $paymentId),
             'date' => $p['payment_date'] ?: date('Y-m-d'), 'academic_year_id' => $p['year_id'], 'currency' => $p['currency']]);
    }

    public static function approvePayment(Database $db, int $tid, int $paymentId): bool {
        $p = $db->fetchOne("SELECT id FROM payments WHERE id=? AND tenant_id=? AND status='pending'", [$paymentId, $tid]);
        if (!$p) { return false; }
        $db->execute("UPDATE payments SET status='active', approved_by=?, approved_at=NOW() WHERE id=?", [$_SESSION['user_id'] ?? null, $paymentId]);
        self::applyPayment($db, $tid, $paymentId);
        return true;
    }

    public static function rejectPayment(Database $db, int $tid, int $paymentId, string $reason): bool {
        return $db->execute("UPDATE payments SET status='rejected', cancel_reason=?, cancelled_by=?, cancelled_at=NOW() WHERE id=? AND tenant_id=? AND status='pending'",
            [$reason, $_SESSION['user_id'] ?? null, $paymentId, $tid]) > 0;
    }

    /** Cancels a payment: it stops counting and the student owes that amount again. */
    public static function cancelPayment(Database $db, int $tid, int $paymentId, string $reason): bool {
        $p = $db->fetchOne("SELECT p.*, i.student_id, i.invoice_no, i.description, i.academic_year_id AS year_id FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE p.id=? AND p.tenant_id=?", [$paymentId, $tid]);
        if (!$p || $p['status'] !== 'active') { return false; }
        $db->execute("UPDATE payments SET status='cancelled', cancelled_at=NOW(), cancelled_by=?, cancel_reason=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, $reason, $paymentId]);
        self::refreshInvoice($db, (int)$p['invoice_id']);
        self::ledger($db, $tid, (int)$p['student_id'], 'adjustment', "Payment #{$paymentId} cancelled — " . ($p['description'] ?: $p['invoice_no']), (float)$p['amount'],
            ['invoice_id' => $p['invoice_id'], 'payment_id' => $paymentId, 'reference' => 'PMT-' . $paymentId, 'academic_year_id' => $p['year_id'], 'currency' => $p['currency']]);
        return true;
    }

    /** Writes off what's left on an invoice (e.g. wrongly billed), with a reason on record. */
    public static function writeOff(Database $db, int $tid, int $invoiceId, string $reason): float {
        $inv = $db->fetchOne("SELECT * FROM invoices WHERE id=? AND tenant_id=?", [$invoiceId, $tid]);
        if (!$inv) { return 0; }
        $left = round((float)$inv['amount_due'] - (float)$inv['discount'] - (float)$inv['amount_paid'], 2);
        if ($left <= 0.005) { return 0; }
        $db->execute("UPDATE invoices SET status='waived' WHERE id=?", [$invoiceId]);
        $db->insert("INSERT INTO balance_writeoffs (tenant_id,student_id,invoice_id,academic_year_id,amount,currency,reason,created_by) VALUES (?,?,?,?,?,?,?,?)",
            [$tid, $inv['student_id'], $invoiceId, $inv['academic_year_id'], $left, $inv['currency'] ?: 'LRD', $reason, $_SESSION['user_id'] ?? null]);
        self::ledger($db, $tid, (int)$inv['student_id'], 'waiver', 'Balance cleared: ' . ($inv['description'] ?: $inv['invoice_no']) . " — {$reason}", -$left,
            ['invoice_id' => $invoiceId, 'reference' => $inv['invoice_no'], 'academic_year_id' => $inv['academic_year_id'], 'currency' => $inv['currency']]);
        return $left;
    }

    // ── Shared queries ────────────────────────────────────────────────

    /** [from, to, label] for an academic year id, or the current/calendar year. */
    public static function yearRange(Database $db, int $tid, $yearId = null): array {
        $y = $yearId ? $db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [$yearId, $tid]) : self::currentYear($db, $tid);
        if ($y) { return [$y['start_date'], $y['end_date'], $y['name'], (int)$y['id']]; }
        return [date('Y-01-01'), date('Y-12-31'), date('Y'), 0];
    }

    /**
     * Income and expenses between two dates, per currency:
     * ['LRD' => ['fees' => [desc => amt], 'arrears' => amt, 'collections' => [cat => amt], 'expenses' => [cat => amt], ...totals]]
     */
    public static function profitAndLoss(Database $db, int $tid, string $from, string $to, ?int $classId = null): array {
        $def = self::settings($db, $tid)['default_currency'];
        $out = [];
        $bucket = function (string $cur) use (&$out) {
            $out[$cur] ??= ['fees' => [], 'arrears' => 0.0, 'collections' => [], 'expenses' => [], 'income' => 0.0, 'expense' => 0.0];
            return $cur;
        };
        $classJoin = $classId ? " JOIN students s ON s.id=i.student_id AND s.class_id=" . (int)$classId : '';
        foreach ($db->fetchAll(
            "SELECT COALESCE(p.currency, i.currency, ?) cur, p.is_arrears,
                    COALESCE(i.description, fs.name, CASE WHEN i.notes LIKE 'Bus Fee%' THEN 'Bus Fees' WHEN i.notes LIKE 'School store%' THEN 'School Store' ELSE 'Other fees' END) label,
                    SUM(p.amount) amt
             FROM payments p JOIN invoices i ON i.id=p.invoice_id LEFT JOIN fee_structures fs ON fs.id=i.fee_structure_id {$classJoin}
             WHERE p.tenant_id=? AND p.status='active' AND COALESCE(p.payment_date, DATE(p.paid_at)) BETWEEN ? AND ?
             GROUP BY cur, p.is_arrears, label ORDER BY MIN(COALESCE(i.due_date, p.paid_at))", [$def, $tid, $from, $to]) as $r) {
            $c = $bucket($r['cur']);
            if ((int)$r['is_arrears']) { $out[$c]['arrears'] += (float)$r['amt']; }
            else { $out[$c]['fees'][$r['label']] = ($out[$c]['fees'][$r['label']] ?? 0) + (float)$r['amt']; }
            $out[$c]['income'] += (float)$r['amt'];
        }
        if (!$classId) {
            foreach ($db->fetchAll("SELECT COALESCE(currency, ?) cur, category, SUM(amount) amt FROM incomes
                                    WHERE tenant_id=? AND status='active' AND income_date BETWEEN ? AND ? GROUP BY cur, category ORDER BY category",
                                    [$def, $tid, $from, $to]) as $r) {
                $c = $bucket($r['cur']);
                $out[$c]['collections'][$r['category']] = (float)$r['amt'];
                $out[$c]['income'] += (float)$r['amt'];
            }
            foreach ($db->fetchAll("SELECT COALESCE(currency, ?) cur, category, SUM(amount) amt FROM expenses
                                    WHERE tenant_id=? AND status='active' AND expense_date BETWEEN ? AND ? GROUP BY cur, category ORDER BY category",
                                    [$def, $tid, $from, $to]) as $r) {
                $c = $bucket($r['cur']);
                $out[$c]['expenses'][$r['category']] = (float)$r['amt'];
                $out[$c]['expense'] += (float)$r['amt'];
            }
        }
        if (!$out) { $bucket($def); }
        uksort($out, fn($a, $b) => ($a === $def ? -1 : 0) - ($b === $def ? -1 : 0));
        return $out;
    }
}
