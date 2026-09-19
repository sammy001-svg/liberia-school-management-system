<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Payroll: staff salary set-up, allowance/deduction components, tax rules, and pay runs
 * that go Calculated → Approved → Paid. Paying a record posts it to Expenses (category
 * "Salaries"), so the P&L reflects real payroll cost; staff see their payslips on My Payroll.
 */
class PayrollController extends FinanceBaseController {
    private const EMPLOYMENT_TYPES = ['Full Time', 'Part Time', 'Contract', 'Temporary'];
    private const PERIODS = ['12 Months', '10 Months', 'Semester', 'Term', 'Monthly'];
    private const FREQUENCIES = ['Monthly', 'Bi-weekly', 'Weekly'];
    private const PAY_METHODS = ['Cash', 'Bank', 'Mobile Money'];
    private const MOMO = ['MoMo Pay (MTN)', 'Orange Money'];

    private function payrollGuard(): void { $this->guard(['hr.manage', 'finance.manage']); }

    private function staff(): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.position, u.employee_no, r.name AS role_name, p.*, p.id AS profile_id, u.id AS user_id
             FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN payroll_profiles p ON p.user_id=u.id AND p.tenant_id=u.tenant_id
             WHERE u.tenant_id=? AND r.name NOT IN ('Student','Parent') AND u.status='active' ORDER BY u.name", [$this->tid]);
    }

    public function index(): void {
        $this->payrollGuard();
        $tab = in_array($_GET['tab'] ?? '', ['overview', 'staff', 'components', 'process', 'reports', 'tax'], true) ? $_GET['tab'] : 'overview';
        $data = ['pageTitle' => 'Payroll', 'tab' => $tab, 'consts' => [
            'types' => self::EMPLOYMENT_TYPES, 'periods' => self::PERIODS, 'freq' => self::FREQUENCIES, 'methods' => self::PAY_METHODS, 'momo' => self::MOMO]];
        $data['stats'] = [
            'staff' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payroll_profiles WHERE tenant_id=? AND is_enabled=1", [$this->tid])['c'] ?? 0),
            'components' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payroll_components WHERE tenant_id=? AND is_active=1", [$this->tid])['c'] ?? 0),
            'pending' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payroll_records WHERE tenant_id=? AND status IN ('calculated','approved')", [$this->tid])['c'] ?? 0),
            'paid' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payroll_records WHERE tenant_id=? AND status='paid'", [$this->tid])['c'] ?? 0),
        ];
        $data['components'] = $this->db->fetchAll("SELECT * FROM payroll_components WHERE tenant_id=? ORDER BY kind, name", [$this->tid]);
        if ($tab === 'staff' || $tab === 'process') {
            $data['staff'] = $this->staff();
            $data['overrides'] = [];
            foreach ($this->db->fetchAll("SELECT sc.* FROM payroll_staff_components sc JOIN payroll_profiles p ON p.id=sc.profile_id WHERE p.tenant_id=?", [$this->tid]) as $o) {
                $data['overrides'][(int)$o['profile_id']][(int)$o['component_id']] = $o['value'];
            }
        }
        if ($tab === 'process') {
            $data['recent'] = $this->db->fetchAll(
                "SELECT pr.*, u.name FROM payroll_records pr JOIN users u ON u.id=pr.user_id WHERE pr.tenant_id=? AND pr.status IN ('calculated','approved')
                 ORDER BY pr.period_start DESC, u.name", [$this->tid]);
            $data['lastRun'] = array_column($this->db->fetchAll("SELECT profile_id, MAX(period_end) last FROM payroll_records WHERE tenant_id=? GROUP BY profile_id", [$this->tid]), 'last', 'profile_id');
            $data['hasTax'] = (bool)$this->db->fetchOne("SELECT id FROM payroll_taxes WHERE tenant_id=? AND is_active=1 LIMIT 1", [$this->tid]);
        }
        if ($tab === 'reports') {
            $f = ['from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '', 'status' => $_GET['status'] ?? '', 'staff' => (int)($_GET['staff'] ?? 0)];
            [$w, $p] = $this->reportWhere($f);
            $data['filters'] = $f;
            $data['records'] = $this->db->fetchAll("SELECT pr.*, u.name, pp.department FROM payroll_records pr JOIN users u ON u.id=pr.user_id LEFT JOIN payroll_profiles pp ON pp.id=pr.profile_id WHERE $w ORDER BY pr.period_start DESC, u.name", $p);
            $data['staffList'] = $this->db->fetchAll("SELECT DISTINCT u.id, u.name FROM payroll_records pr JOIN users u ON u.id=pr.user_id WHERE pr.tenant_id=? ORDER BY u.name", [$this->tid]);
        }
        if ($tab === 'tax') {
            $data['taxes'] = $this->db->fetchAll("SELECT * FROM payroll_taxes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        }
        $this->financeView('payroll', $data);
    }

    private function back(string $tab): never { $this->redirect('/school/payroll?tab=' . $tab); }

    // ── Staff profiles ─────────────────────────────────────────────

    public function saveProfile(): void {
        $this->payrollGuard();
        $userId = (int)($_POST['user_id'] ?? 0);
        $user = $this->db->fetchOne("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.tenant_id=? AND r.name NOT IN ('Student','Parent')", [$userId, $this->tid]);
        $salary = (float)($_POST['base_salary'] ?? 0);
        if (!$user || $salary < 0) { $this->flash('danger', 'Choose a staff member and enter a valid salary.'); $this->back('staff'); }
        $pick = fn($k, $list, $d) => in_array($_POST[$k] ?? '', $list, true) ? $_POST[$k] : $d;
        $vals = [
            trim((string)($_POST['department'] ?? '')) ?: null, $pick('employment_type', self::EMPLOYMENT_TYPES, 'Full Time'), $pick('employment_period', self::PERIODS, '12 Months'),
            ($_POST['hire_date'] ?? '') ?: null, ($_POST['contract_start'] ?? '') ?: null, ($_POST['contract_end'] ?? '') ?: null,
            round($salary, 2), $this->currencyOrDefault($_POST['currency'] ?? null), $pick('pay_frequency', self::FREQUENCIES, 'Monthly'),
            $pick('payment_method', self::PAY_METHODS, 'Cash'), trim((string)($_POST['bank_name'] ?? '')) ?: null, trim((string)($_POST['bank_account'] ?? '')) ?: null,
            $pick('momo_provider', self::MOMO, null), trim((string)($_POST['momo_number'] ?? '')) ?: null, !empty($_POST['is_enabled']) ? 1 : 0,
        ];
        $existing = $this->db->fetchOne("SELECT id FROM payroll_profiles WHERE tenant_id=? AND user_id=?", [$this->tid, $userId]);
        if ($existing) {
            $this->db->execute("UPDATE payroll_profiles SET department=?, employment_type=?, employment_period=?, hire_date=?, contract_start=?, contract_end=?, base_salary=?, currency=?,
                pay_frequency=?, payment_method=?, bank_name=?, bank_account=?, momo_provider=?, momo_number=?, is_enabled=? WHERE id=?", array_merge($vals, [$existing['id']]));
            $pid = (int)$existing['id'];
        } else {
            $pid = (int)$this->db->insert("INSERT INTO payroll_profiles (department, employment_type, employment_period, hire_date, contract_start, contract_end, base_salary, currency,
                pay_frequency, payment_method, bank_name, bank_account, momo_provider, momo_number, is_enabled, tenant_id, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array_merge($vals, [$this->tid, $userId]));
        }
        // Per-staff component values: blank = use the component default; "specific" components only apply when ticked.
        $this->db->execute("DELETE FROM payroll_staff_components WHERE profile_id=?", [$pid]);
        foreach ((array)($_POST['comp'] ?? []) as $cid => $c) {
            if (empty($c['on'])) { continue; }
            if (!$this->db->fetchOne("SELECT id FROM payroll_components WHERE id=? AND tenant_id=?", [(int)$cid, $this->tid])) { continue; }
            $v = trim((string)($c['value'] ?? ''));
            $this->db->execute("INSERT INTO payroll_staff_components (profile_id, component_id, value) VALUES (?,?,?)", [$pid, (int)$cid, $v === '' ? null : (float)$v]);
        }
        $this->flash('success', 'Payroll details saved.');
        $this->back('staff');
    }

    // ── Components & taxes ─────────────────────────────────────────

    public function saveComponent(): void {
        $this->payrollGuard();
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { $this->flash('danger', 'Give the component a name.'); $this->back('components'); }
        $vals = [mb_substr($name, 0, 100), ($_POST['kind'] ?? '') === 'deduction' ? 'deduction' : 'allowance', ($_POST['calc'] ?? '') === 'percent' ? 'percent' : 'fixed',
                 max(0, round((float)($_POST['default_value'] ?? 0), 2)), ($_POST['applies_to'] ?? '') === 'specific' ? 'specific' : 'all',
                 !empty($_POST['is_taxable']) && ($_POST['kind'] ?? '') !== 'deduction' ? 1 : 0, !empty($_POST['is_active']) ? 1 : 0];
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $this->db->fetchOne("SELECT id FROM payroll_components WHERE id=? AND tenant_id=?", [$id, $this->tid])) {
            $this->db->execute("UPDATE payroll_components SET name=?, kind=?, calc=?, default_value=?, applies_to=?, is_taxable=?, is_active=? WHERE id=?", array_merge($vals, [$id]));
        } else {
            $this->db->insert("INSERT INTO payroll_components (name, kind, calc, default_value, applies_to, is_taxable, is_active, tenant_id) VALUES (?,?,?,?,?,?,?,?)", array_merge($vals, [$this->tid]));
        }
        $this->flash('success', 'Component saved.');
        $this->back('components');
    }

    public function deleteComponent(string $id): void {
        $this->payrollGuard();
        $this->db->execute("DELETE FROM payroll_components WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Component deleted.');
        $this->back('components');
    }

    public function saveTax(): void {
        $this->payrollGuard();
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { $this->flash('danger', 'Give the tax a name.'); $this->back('tax'); }
        $applies = in_array($_POST['applies_to'] ?? '', ['all', 'range', 'employment'], true) ? $_POST['applies_to'] : 'all';
        $types = array_values(array_intersect((array)($_POST['employment_types'] ?? []), self::EMPLOYMENT_TYPES));
        $vals = [mb_substr($name, 0, 100), ($_POST['calc'] ?? '') === 'fixed' ? 'fixed' : 'percent', max(0, round((float)($_POST['value'] ?? 0), 2)),
                 $this->currencyOrDefault($_POST['currency'] ?? null), $applies,
                 ($_POST['min_salary'] ?? '') === '' ? null : (float)$_POST['min_salary'], ($_POST['max_salary'] ?? '') === '' ? null : (float)$_POST['max_salary'],
                 $types ? implode(',', $types) : null, !empty($_POST['is_active']) ? 1 : 0];
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $this->db->fetchOne("SELECT id FROM payroll_taxes WHERE id=? AND tenant_id=?", [$id, $this->tid])) {
            $this->db->execute("UPDATE payroll_taxes SET name=?, calc=?, value=?, currency=?, applies_to=?, min_salary=?, max_salary=?, employment_types=?, is_active=? WHERE id=?", array_merge($vals, [$id]));
        } else {
            $this->db->insert("INSERT INTO payroll_taxes (name, calc, value, currency, applies_to, min_salary, max_salary, employment_types, is_active, tenant_id) VALUES (?,?,?,?,?,?,?,?,?,?)", array_merge($vals, [$this->tid]));
        }
        $this->flash('success', 'Tax setting saved.');
        $this->back('tax');
    }

    public function deleteTax(string $id): void {
        $this->payrollGuard();
        $this->db->execute("DELETE FROM payroll_taxes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Tax setting deleted.');
        $this->back('tax');
    }

    // ── Processing ─────────────────────────────────────────────────

    /** Base pay for a period: the salary is per pay-frequency unit, prorated by days. */
    private function basePay(array $p, string $from, string $to): float {
        $base = (float)$p['base_salary'];
        if ($p['pay_frequency'] === 'Weekly' || $p['pay_frequency'] === 'Bi-weekly') {
            $days = (strtotime($to) - strtotime($from)) / 86400 + 1;
            return round($base * $days / ($p['pay_frequency'] === 'Weekly' ? 7 : 14), 2);
        }
        // Monthly: sum each calendar month's share, so 1st–last of a month is exactly one salary.
        $months = 0.0;
        for ($t = strtotime(date('Y-m-01', strtotime($from))); $t <= strtotime($to); $t = strtotime('+1 month', $t)) {
            $mStart = max(strtotime($from), $t);
            $mEnd = min(strtotime($to), strtotime(date('Y-m-t', $t)));
            if ($mEnd >= $mStart) { $months += (($mEnd - $mStart) / 86400 + 1) / (int)date('t', $t); }
        }
        return round($base * $months, 2);
    }

    public function calculate(): void {
        $this->payrollGuard();
        $from = $_POST['pay_period_start'] ?? '';
        $to = $_POST['pay_period_end'] ?? '';
        $ids = array_map('intval', (array)($_POST['profiles'] ?? []));
        if (!strtotime($from) || !strtotime($to) || $to < $from || !$ids) {
            $this->flash('danger', 'Choose a valid pay period and at least one staff member.');
            $this->back('process');
        }
        $withTax = !empty($_POST['include_taxes']);
        $components = $this->db->fetchAll("SELECT * FROM payroll_components WHERE tenant_id=? AND is_active=1", [$this->tid]);
        $taxes = $this->db->fetchAll("SELECT * FROM payroll_taxes WHERE tenant_id=? AND is_active=1", [$this->tid]);
        $done = $skipped = 0;
        foreach ($ids as $pid) {
            $p = $this->db->fetchOne("SELECT pp.*, u.name FROM payroll_profiles pp JOIN users u ON u.id=pp.user_id WHERE pp.id=? AND pp.tenant_id=? AND pp.is_enabled=1", [$pid, $this->tid]);
            if (!$p) { continue; }
            $existing = $this->db->fetchOne("SELECT id, status FROM payroll_records WHERE profile_id=? AND period_start=? AND period_end=?", [$pid, $from, $to]);
            if ($existing && $existing['status'] !== 'calculated') { $skipped++; continue; }

            $overrides = array_column($this->db->fetchAll("SELECT component_id, value FROM payroll_staff_components WHERE profile_id=?", [$pid]), 'value', 'component_id');
            $base = $this->basePay($p, $from, $to);
            $lines = [['Basic salary', 'base', $base]];
            $allow = 0.0; $taxable = $base; $deduct = 0.0; $tax = 0.0;
            foreach ($components as $c) {
                $assigned = array_key_exists($c['id'], $overrides);
                if ($c['applies_to'] === 'specific' && !$assigned) { continue; }
                $v = $assigned && $overrides[$c['id']] !== null ? (float)$overrides[$c['id']] : (float)$c['default_value'];
                if ($c['kind'] === 'allowance') {
                    $amt = round($c['calc'] === 'percent' ? $base * $v / 100 : $v, 2);
                    if ($amt <= 0) { continue; }
                    $allow += $amt; if ($c['is_taxable']) { $taxable += $amt; }
                    $lines[] = [$c['name'] . ($c['calc'] === 'percent' ? " ({$v}%)" : ''), 'allowance', $amt];
                }
            }
            $gross = round($base + $allow, 2);
            foreach ($components as $c) {
                if ($c['kind'] !== 'deduction') { continue; }
                $assigned = array_key_exists($c['id'], $overrides);
                if ($c['applies_to'] === 'specific' && !$assigned) { continue; }
                $v = $assigned && $overrides[$c['id']] !== null ? (float)$overrides[$c['id']] : (float)$c['default_value'];
                $amt = round($c['calc'] === 'percent' ? $gross * $v / 100 : $v, 2);
                if ($amt <= 0) { continue; }
                $deduct += $amt;
                $lines[] = [$c['name'] . ($c['calc'] === 'percent' ? " ({$v}%)" : ''), 'deduction', $amt];
            }
            if ($withTax) {
                if (!$taxes) {
                    $tax = round($taxable * 0.02, 2);
                    $lines[] = ['Social Security (2%)', 'tax', $tax];
                }
                foreach ($taxes as $t) {
                    if ($t['applies_to'] === 'range' && (($t['min_salary'] !== null && (float)$p['base_salary'] < (float)$t['min_salary']) || ($t['max_salary'] !== null && (float)$p['base_salary'] > (float)$t['max_salary']))) { continue; }
                    if ($t['applies_to'] === 'employment' && !in_array($p['employment_type'], explode(',', (string)$t['employment_types']), true)) { continue; }
                    $amt = round($t['calc'] === 'percent' ? $taxable * (float)$t['value'] / 100 : (float)$t['value'], 2);
                    if ($amt <= 0) { continue; }
                    $tax += $amt;
                    $lines[] = [$t['name'] . ($t['calc'] === 'percent' ? " ({$t['value']}%)" : ''), 'tax', $amt];
                }
            }
            $net = round($gross - $deduct - $tax, 2);
            $vals = [$base, $allow, $gross, $deduct, $tax, $net, json_encode($lines), $p['currency']];
            if ($existing) {
                $this->db->execute("UPDATE payroll_records SET base_pay=?, allowances=?, gross_pay=?, deductions=?, tax=?, net_pay=?, breakdown=?, currency=?, created_by=?, created_at=NOW() WHERE id=?",
                    array_merge($vals, [$_SESSION['user_id'] ?? null, $existing['id']]));
            } else {
                $this->db->insert("INSERT INTO payroll_records (base_pay, allowances, gross_pay, deductions, tax, net_pay, breakdown, currency, tenant_id, profile_id, user_id, period_start, period_end, created_by)
                                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array_merge($vals, [$this->tid, $pid, $p['user_id'], $from, $to, $_SESSION['user_id'] ?? null]));
            }
            $done++;
        }
        $this->flash('success', "Calculated payroll for {$done} staff member(s)." . ($skipped ? " {$skipped} already approved or paid for this period were left alone." : '') . ' Review and approve below.');
        $this->back('process');
    }

    /** Approve / pay / delete one record, or many at once (ids[]). */
    public function action(): void {
        $this->payrollGuard();
        $act = $_POST['act'] ?? '';
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        if (!$ids) { $this->flash('warning', 'Select at least one payroll record.'); $this->back($_POST['tab'] ?? 'process'); }
        if (in_array($act, ['approve', 'pay'], true) && !Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $n = 0;
        foreach ($ids as $id) {
            $r = $this->db->fetchOne("SELECT pr.*, u.name, pp.payment_method FROM payroll_records pr JOIN users u ON u.id=pr.user_id LEFT JOIN payroll_profiles pp ON pp.id=pr.profile_id WHERE pr.id=? AND pr.tenant_id=?", [$id, $this->tid]);
            if (!$r) { continue; }
            if ($act === 'approve' && $r['status'] === 'calculated') {
                $this->db->execute("UPDATE payroll_records SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?", [$_SESSION['user_id'] ?? null, $id]);
                $n++;
            } elseif ($act === 'pay' && $r['status'] === 'approved') {
                $period = date('M j', strtotime($r['period_start'])) . ' – ' . date('M j, Y', strtotime($r['period_end']));
                $method = ['Cash' => 'cash', 'Bank' => 'bank', 'Mobile Money' => 'mobile'][$r['payment_method'] ?? 'Cash'] ?? 'cash';
                $yearId = (int)($this->db->fetchOne("SELECT id FROM academic_years WHERE tenant_id=? AND ? BETWEEN start_date AND end_date LIMIT 1", [$this->tid, date('Y-m-d')])['id'] ?? 0);
                $expenseId = $this->db->insert(
                    "INSERT INTO expenses (tenant_id,category,description,amount,currency,expense_date,payee,payee_user_id,method,reference,academic_year_id,status,recorded_by)
                     VALUES (?,'Salaries',?,?,?,CURDATE(),?,?,?,?,?,'active',?)",
                    [$this->tid, "Salary — {$r['name']} — {$period}", $r['net_pay'], $r['currency'], $r['name'], $r['user_id'], $method, 'PAYROLL-' . $id, $yearId ?: null, $_SESSION['user_id'] ?? null]);
                $this->db->execute("UPDATE payroll_records SET status='paid', paid_by=?, paid_at=NOW(), expense_id=? WHERE id=?", [$_SESSION['user_id'] ?? null, $expenseId, $id]);
                $n++;
            } elseif ($act === 'delete' && $r['status'] !== 'paid') {
                $this->db->execute("DELETE FROM payroll_records WHERE id=?", [$id]);
                $n++;
            }
        }
        $label = ['approve' => 'approved', 'pay' => 'marked paid and posted to Expenses', 'delete' => 'deleted'][$act] ?? 'updated';
        $this->flash($n ? 'success' : 'warning', $n ? "{$n} payroll record(s) {$label}." : 'Nothing changed — check the records’ status.');
        $this->back($_POST['tab'] ?? 'process');
    }

    // ── Payslips & reports ─────────────────────────────────────────

    private function reportWhere(array $f): array {
        $w = "pr.tenant_id=?"; $p = [$this->tid];
        if ($f['from'] !== '') { $w .= " AND pr.period_end >= ?"; $p[] = $f['from']; }
        if ($f['to'] !== '') { $w .= " AND pr.period_start <= ?"; $p[] = $f['to']; }
        if (in_array($f['status'], ['calculated', 'approved', 'paid'], true)) { $w .= " AND pr.status=?"; $p[] = $f['status']; }
        if ($f['staff']) { $w .= " AND pr.user_id=?"; $p[] = $f['staff']; }
        return [$w, $p];
    }

    public function exportCsv(): void {
        $this->payrollGuard();
        [$w, $p] = $this->reportWhere(['from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '', 'status' => $_GET['status'] ?? '', 'staff' => (int)($_GET['staff'] ?? 0)]);
        $rows = $this->db->fetchAll("SELECT pr.*, u.name, u.employee_no, pp.department FROM payroll_records pr JOIN users u ON u.id=pr.user_id LEFT JOIN payroll_profiles pp ON pp.id=pr.profile_id WHERE $w ORDER BY pr.period_start, u.name", $p);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="payroll-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Staff ID', 'Name', 'Department', 'Period Start', 'Period End', 'Currency', 'Base', 'Allowances', 'Gross', 'Deductions', 'Tax', 'Net', 'Status']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['employee_no'], $r['name'], $r['department'], $r['period_start'], $r['period_end'], $r['currency'], $r['base_pay'], $r['allowances'], $r['gross_pay'], $r['deductions'], $r['tax'], $r['net_pay'], $r['status']]);
        }
        fclose($out);
        exit;
    }

    public function payslip(string $id): void {
        if (!isset($_SESSION['user_id'])) { $this->redirect('/login'); }
        $r = $this->db->fetchOne(
            "SELECT pr.*, u.name, u.employee_no, u.position, pp.department, pp.employment_type, pp.payment_method, pp.bank_name, pp.bank_account, pp.momo_provider, pp.momo_number
             FROM payroll_records pr JOIN users u ON u.id=pr.user_id LEFT JOIN payroll_profiles pp ON pp.id=pr.profile_id WHERE pr.id=? AND pr.tenant_id=?", [$id, $this->tid]);
        $own = $r && (int)$r['user_id'] === (int)$_SESSION['user_id'] && in_array($r['status'], ['approved', 'paid'], true);
        if (!$r || (!$own && !$this->hasPermission('hr.manage') && !$this->hasPermission('finance.manage'))) { $this->redirect('/unauthorized'); }
        $this->view('school/highschool/finance/payslip', ['pageTitle' => 'Payslip', 'r' => $r, 'lines' => json_decode((string)$r['breakdown'], true) ?: [],
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid])]);
    }

    /** Any staff member: their own salary details and approved/paid payslips. */
    public function mine(): void {
        if (!isset($_SESSION['user_id'])) { $this->redirect('/login'); }
        $profile = $this->db->fetchOne("SELECT * FROM payroll_profiles WHERE tenant_id=? AND user_id=?", [$this->tid, $_SESSION['user_id']]);
        $records = $this->db->fetchAll("SELECT * FROM payroll_records WHERE tenant_id=? AND user_id=? AND status IN ('approved','paid') ORDER BY period_start DESC", [$this->tid, $_SESSION['user_id']]);
        $this->financeView('my_payroll', ['pageTitle' => 'My Payroll', 'profile' => $profile, 'records' => $records]);
    }
}
