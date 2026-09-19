<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Financial Records: the finance overview, Profit & Loss, class/student statements,
 * the daily receipts summary and the payment reconciliation (audit) view.
 */
class FinancialRecordsController extends FinanceBaseController {

    // ── Overview ───────────────────────────────────────────────────

    public function overview(): void {
        $this->guard(['finance.manage']);
        $year = $this->selectedYear();
        [$from, $to, $label, $yearId] = Finance::yearRange($this->db, $this->tid, $year['id'] ?? null);
        $pl = Finance::profitAndLoss($this->db, $this->tid, $from, $to);
        $def = $this->settings['default_currency'];

        // Remaining arrears: anything unpaid from years before this one.
        $remaining = $year ? array_column($this->db->fetchAll(
            "SELECT COALESCE(i.currency, ?) cur, SUM(i.amount_due - i.discount - i.amount_paid) t FROM invoices i JOIN academic_years ay ON ay.id=i.academic_year_id
             WHERE i.tenant_id=? AND i.status IN ('unpaid','partial','overdue') AND ay.start_date < ? GROUP BY cur", [$def, $this->tid, $from]), 't', 'cur') : [];
        // Still owed on this year's bills.
        $owedThisYear = $year ? array_column($this->db->fetchAll(
            "SELECT COALESCE(currency, ?) cur, SUM(amount_due - discount - amount_paid) t FROM invoices
             WHERE tenant_id=? AND academic_year_id=? AND status IN ('unpaid','partial','overdue') GROUP BY cur", [$def, $this->tid, $yearId]), 't', 'cur') : [];

        // Month-by-month income vs expenses for the year (default currency).
        $months = [];
        for ($t = strtotime(date('Y-m-01', strtotime($from))); $t <= strtotime($to); $t = strtotime('+1 month', $t)) { $months[date('Y-m', $t)] = ['in' => 0.0, 'out' => 0.0]; }
        foreach ($this->db->fetchAll("SELECT DATE_FORMAT(COALESCE(payment_date, DATE(paid_at)),'%Y-%m') ym, SUM(amount) t FROM payments WHERE tenant_id=? AND status='active' AND COALESCE(currency, ?)=? AND COALESCE(payment_date, DATE(paid_at)) BETWEEN ? AND ? GROUP BY ym", [$this->tid, $def, $def, $from, $to]) as $r) { if (isset($months[$r['ym']])) { $months[$r['ym']]['in'] += (float)$r['t']; } }
        foreach ($this->db->fetchAll("SELECT DATE_FORMAT(income_date,'%Y-%m') ym, SUM(amount) t FROM incomes WHERE tenant_id=? AND status='active' AND COALESCE(currency, ?)=? AND income_date BETWEEN ? AND ? GROUP BY ym", [$this->tid, $def, $def, $from, $to]) as $r) { if (isset($months[$r['ym']])) { $months[$r['ym']]['in'] += (float)$r['t']; } }
        foreach ($this->db->fetchAll("SELECT DATE_FORMAT(expense_date,'%Y-%m') ym, SUM(amount) t FROM expenses WHERE tenant_id=? AND status='active' AND COALESCE(currency, ?)=? AND expense_date BETWEEN ? AND ? GROUP BY ym", [$this->tid, $def, $def, $from, $to]) as $r) { if (isset($months[$r['ym']])) { $months[$r['ym']]['out'] += (float)$r['t']; } }

        // Year over year (default currency), last five academic years.
        $yoy = [];
        foreach (array_slice(array_reverse($this->years()), -5) as $y) {
            $p = Finance::profitAndLoss($this->db, $this->tid, $y['start_date'], $y['end_date'])[$def] ?? ['income' => 0, 'expense' => 0];
            $yoy[] = ['label' => $y['name'], 'in' => $p['income'], 'out' => $p['expense']];
        }

        $pending = [
            'payments' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payments WHERE tenant_id=? AND status='pending'", [$this->tid])['c'] ?? 0),
            'expenses' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM expenses WHERE tenant_id=? AND status='pending'", [$this->tid])['c'] ?? 0),
            'overrides' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM arrears_override_requests WHERE tenant_id=? AND status='pending'", [$this->tid])['c'] ?? 0),
        ];
        $this->financeView('overview', [
            'pageTitle' => 'Financial Records', 'year' => $year, 'years' => $this->years(), 'label' => $label,
            'pl' => $pl, 'remaining' => $remaining, 'owedThisYear' => $owedThisYear, 'months' => $months, 'yoy' => $yoy, 'pending' => $pending,
        ]);
    }

    // ── Profit & Loss ──────────────────────────────────────────────

    public function profitLoss(): void {
        $this->guard(['finance.manage']);
        $type = in_array($_GET['type'] ?? '', ['year', 'range', 'compare', 'daily', 'daily_class'], true) ? $_GET['type'] : 'year';
        $years = $this->years();
        $columns = [];   // [heading => P&L data]
        $classId = null;
        $title = '';
        switch ($type) {
            case 'range':
                $from = $_GET['from'] ?? date('Y-m-01'); $to = $_GET['to'] ?? date('Y-m-d');
                $title = date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
                $columns[$title] = Finance::profitAndLoss($this->db, $this->tid, $from, $to);
                break;
            case 'compare':
                foreach ([1, 2] as $n) {
                    $yid = (int)($_GET["year{$n}"] ?? 0);
                    if ($yid) {
                        [$f, $t, $lbl] = Finance::yearRange($this->db, $this->tid, $yid);
                    } else {
                        $f = $_GET["from{$n}"] ?? ''; $t = $_GET["to{$n}"] ?? '';
                        if (!$f || !$t) { continue; }
                        $lbl = date('M j, Y', strtotime($f)) . ' – ' . date('M j, Y', strtotime($t));
                    }
                    $columns[$lbl . (isset($columns[$lbl]) ? " ({$n})" : '')] = Finance::profitAndLoss($this->db, $this->tid, $f, $t);
                }
                $title = implode(' vs ', array_keys($columns));
                break;
            case 'daily':
            case 'daily_class':
                $date = $_GET['date'] ?? date('Y-m-d');
                if ($type === 'daily_class') { $classId = (int)($_GET['class'] ?? 0) ?: null; }
                $title = date('l, F j, Y', strtotime($date));
                $columns[$title] = Finance::profitAndLoss($this->db, $this->tid, $date, $date, $classId);
                break;
            default:
                [$f, $t, $lbl] = Finance::yearRange($this->db, $this->tid, (int)($_GET['year'] ?? 0) ?: null);
                $title = $lbl;
                $columns[$lbl] = Finance::profitAndLoss($this->db, $this->tid, $f, $t);
        }
        // One row list per section, merged across columns so compare reports line up.
        $currencies = [];
        foreach ($columns as $data) { $currencies = array_unique(array_merge($currencies, array_keys($data))); }
        $this->financeView('profit_loss', [
            'pageTitle' => 'Profit & Loss Statement', 'type' => $type, 'years' => $years, 'classes' => $this->classes(),
            'columns' => $columns, 'plCurrencies' => $currencies, 'reportTitle' => $title, 'classId' => $classId,
        ]);
    }

    // ── Statements ─────────────────────────────────────────────────

    public function statements(): void {
        $this->guard();
        $mode = ($_GET['mode'] ?? 'student') === 'class' ? 'class' : 'student';
        $year = $this->selectedYear();
        $data = ['pageTitle' => 'Financial Statements', 'mode' => $mode, 'year' => $year, 'years' => $this->years(),
                 'classes' => $this->classes(), 'types' => $this->studentTypes(false), 'report' => null];
        $data['students'] = $this->db->fetchAll("SELECT s.id, s.admission_no, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.tenant_id=? ORDER BY u.name", [$this->tid]);
        if (!$year) { $this->financeView('statements', $data); return; }

        if ($mode === 'student' && ($sid = (int)($_GET['student'] ?? 0))) {
            $student = $this->db->fetchOne("SELECT s.id, s.admission_no, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=? AND s.tenant_id=?", [$sid, $this->tid]);
            if ($student) {
                $data['report'] = [
                    'student' => $student,
                    'enrollment' => $this->db->fetchOne("SELECT e.*, c.name AS class_name, st.name AS type_name FROM enrollments e LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN student_types st ON st.id=e.student_type_id WHERE e.student_id=? AND e.academic_year_id=?", [$sid, $year['id']]),
                    'bills' => $this->db->fetchAll(
                        "SELECT i.*, COALESCE(i.description, i.notes, i.invoice_no) AS label FROM invoices i LEFT JOIN fee_bills fb ON fb.id=i.fee_bill_id
                         WHERE i.tenant_id=? AND i.student_id=? AND (i.academic_year_id=? OR (i.academic_year_id IS NULL AND DATE(i.created_at) BETWEEN ? AND ?))
                         ORDER BY i.fee_bill_id IS NULL, fb.sort_order, i.due_date, i.id", [$this->tid, $sid, $year['id'], $year['start_date'], $year['end_date']]),
                    'payments' => $this->db->fetchAll(
                        "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label FROM payments p JOIN invoices i ON i.id=p.invoice_id
                         WHERE p.tenant_id=? AND p.status='active' AND i.student_id=? AND (i.academic_year_id=? OR (i.academic_year_id IS NULL AND DATE(i.created_at) BETWEEN ? AND ?))
                         ORDER BY COALESCE(p.payment_date, DATE(p.paid_at)) DESC, p.id DESC", [$this->tid, $sid, $year['id'], $year['start_date'], $year['end_date']]),
                    'arrears' => Finance::priorArrears($this->db, $this->tid, $sid, (int)$year['id']),
                ];
            }
        }

        if ($mode === 'class' && ($cid = (int)($_GET['class'] ?? 0))) {
            $rtype = in_array($_GET['report'] ?? '', ['overview', 'installments', 'outstanding', 'history'], true) ? $_GET['report'] : 'overview';
            $f = ['category' => $_GET['category'] ?? '', 'pay' => $_GET['pay'] ?? '', 'type' => (int)($_GET['type'] ?? 0),
                  'min' => $_GET['min'] ?? '', 'max' => $_GET['max'] ?? ''];
            $params = [$this->tid, $year['id'], $cid];
            $w = "e.tenant_id=? AND e.academic_year_id=? AND e.class_id=? AND e.status='active'";
            if (in_array($f['category'], ['new', 'old'], true)) { $w .= " AND e.category=?"; $params[] = $f['category']; }
            if ($f['type']) { $w .= " AND e.student_type_id=?"; $params[] = $f['type']; }
            $students = $this->db->fetchAll(
                "SELECT e.id AS enrollment_id, e.student_id, e.category, s.admission_no, u.name, st.name AS type_name
                 FROM enrollments e JOIN students s ON s.id=e.student_id JOIN users u ON u.id=s.user_id LEFT JOIN student_types st ON st.id=e.student_type_id
                 WHERE $w ORDER BY u.name", $params);
            $bills = [];
            if ($students) {
                $ids = implode(',', array_map('intval', array_column($students, 'enrollment_id')));
                foreach ($this->db->fetchAll("SELECT i.*, COALESCE(i.description, i.invoice_no) AS label FROM invoices i WHERE i.enrollment_id IN ($ids) ORDER BY i.due_date, i.id") as $b) {
                    $bills[(int)$b['enrollment_id']][] = $b;
                }
            }
            $descriptions = [];
            $rows = [];
            $totalAll = count($students);
            foreach ($students as $s) {
                $billed = $paid = $waived = 0.0; $per = []; $cur = null;
                foreach ($bills[(int)$s['enrollment_id']] ?? [] as $b) {
                    $due = (float)$b['amount_due'] - (float)$b['discount'];
                    $billed += $due; $paid += (float)$b['amount_paid'];
                    if ($b['status'] === 'waived') { $waived += $due - (float)$b['amount_paid']; }
                    $per[$b['label']] = ['paid' => (float)$b['amount_paid'], 'owed' => $b['status'] === 'waived' ? 0 : max(0, $due - (float)$b['amount_paid'])];
                    $descriptions[$b['label']] = true;
                    $cur = $cur ?? ($b['currency'] ?: $this->settings['default_currency']);
                }
                $balance = max(0, $billed - $paid - $waived);
                $status = $billed <= 0 ? 'nobill' : ($balance <= 0.005 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'));
                if ($f['pay'] === 'paid' && $status !== 'paid') { continue; }
                if ($f['pay'] === 'partial' && $status !== 'partial') { continue; }
                if ($f['pay'] === 'unpaid' && $status !== 'unpaid') { continue; }
                if ($f['pay'] === 'overdue') {
                    $overdue = array_filter($bills[(int)$s['enrollment_id']] ?? [], fn($b) => $b['due_date'] && $b['due_date'] < date('Y-m-d') && in_array($b['status'], ['unpaid', 'partial', 'overdue'], true));
                    if (!$overdue) { continue; }
                }
                if ($rtype === 'outstanding' && $balance <= 0.005) { continue; }
                if ($f['min'] !== '' && $balance < (float)$f['min']) { continue; }
                if ($f['max'] !== '' && $balance > (float)$f['max']) { continue; }
                $rows[] = $s + ['billed' => $billed, 'paid' => $paid, 'balance' => $balance, 'status' => $status, 'per' => $per, 'cur' => $cur ?? $this->settings['default_currency']];
            }
            $history = [];
            if ($rtype === 'history' && $rows) {
                $ids = implode(',', array_map('intval', array_column($rows, 'enrollment_id')));
                $history = $this->db->fetchAll(
                    "SELECT p.*, COALESCE(i.description, i.invoice_no) AS label, u.name AS student_name, s.admission_no FROM payments p JOIN invoices i ON i.id=p.invoice_id
                     JOIN students s ON s.id=i.student_id JOIN users u ON u.id=s.user_id WHERE i.enrollment_id IN ($ids) AND p.status='active'
                     ORDER BY COALESCE(p.payment_date, DATE(p.paid_at)) DESC, p.id DESC");
            }
            $class = $this->db->fetchOne("SELECT name FROM classes WHERE id=? AND tenant_id=?", [$cid, $this->tid]);
            $data['report'] = ['class' => $class['name'] ?? '', 'type' => $rtype, 'filters' => $f, 'rows' => $rows, 'total' => $totalAll,
                               'descriptions' => array_keys($descriptions), 'history' => $history];
        }
        $this->financeView('statements', $data);
    }

    // ── Daily receipts & reconciliation ────────────────────────────

    public function dailyReceipts(): void {
        $this->guard();
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!strtotime($date)) { $date = date('Y-m-d'); }
        $fees = $this->db->fetchAll(
            "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label, s.admission_no, u.name AS student_name, c.name AS class_name, r.name AS received_by_name
             FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN students s ON s.id=i.student_id JOIN users u ON u.id=s.user_id
             LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN users r ON r.id=p.received_by
             WHERE p.tenant_id=? AND p.status='active' AND COALESCE(p.payment_date, DATE(p.paid_at))=? ORDER BY p.id", [$this->tid, $date]);
        $collections = $this->db->fetchAll(
            "SELECT i.*, r.name AS received_by_name FROM incomes i LEFT JOIN users r ON r.id=i.recorded_by
             WHERE i.tenant_id=? AND i.status='active' AND i.income_date=? ORDER BY i.id", [$this->tid, $date]);
        $this->financeView('daily_receipts', ['pageTitle' => 'Daily Receipts Summary', 'date' => $date, 'fees' => $fees, 'collections' => $collections]);
    }

    /** Payment records audit: every fee payment in a period, with who took it and how, incl. cancelled ones. */
    public function audit(): void {
        $this->guard(['finance.manage']);
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $method = $_GET['method'] ?? '';
        $user = (int)($_GET['user'] ?? 0);
        $params = [$this->tid, $from, $to];
        $w = "p.tenant_id=? AND COALESCE(p.payment_date, DATE(p.paid_at)) BETWEEN ? AND ?";
        if ($method !== '') { $w .= " AND p.method=?"; $params[] = $method; }
        if ($user) { $w .= " AND p.received_by=?"; $params[] = $user; }
        $rows = $this->db->fetchAll(
            "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label, s.admission_no, u.name AS student_name,
                    r.name AS received_by_name, cb.name AS cancelled_by_name, ab.name AS approved_by_name
             FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN students s ON s.id=i.student_id JOIN users u ON u.id=s.user_id
             LEFT JOIN users r ON r.id=p.received_by LEFT JOIN users cb ON cb.id=p.cancelled_by LEFT JOIN users ab ON ab.id=p.approved_by
             WHERE $w ORDER BY COALESCE(p.payment_date, DATE(p.paid_at)), p.id", $params);
        $def = $this->settings['default_currency'];
        $byMethod = []; $byUser = [];
        foreach ($rows as $r) {
            if ($r['status'] !== 'active') { continue; }
            $c = $r['currency'] ?: $def;
            $byMethod[$c][$r['method']] = ($byMethod[$c][$r['method']] ?? 0) + (float)$r['amount'];
            $byUser[$c][$r['received_by_name'] ?? '—'] = ($byUser[$c][$r['received_by_name'] ?? '—'] ?? 0) + (float)$r['amount'];
        }
        $users = $this->db->fetchAll("SELECT DISTINCT u.id, u.name FROM payments p JOIN users u ON u.id=p.received_by WHERE p.tenant_id=? ORDER BY u.name", [$this->tid]);
        $this->financeView('audit', ['pageTitle' => 'Payment Records Audit', 'rows' => $rows, 'byMethod' => $byMethod, 'byUser' => $byUser,
            'users' => $users, 'filters' => ['from' => $from, 'to' => $to, 'method' => $method, 'user' => $user]]);
    }

    /** Old finance-module URLs → the page that does that job now (the target checks access). */
    public function legacy(string $id = ''): void {
        $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $map = [
            '/school/hr/payroll' => '/school/payroll',
            '/school/finance/invoices' => '/school/finance/fees-payment',
            '/school/finance/payments' => '/school/finance/fees-payment',
            '/school/finance/fees' => '/school/finance/billing',
            '/school/finance/collection' => '/school/finance/arrears-collection',
            '/school/finance/arrears' => '/school/finance/arrears-collection',
            '/school/finance/reports' => '/school/finance/profit-loss',
            '/school/finance/accounts' => '/school/finance/statements',
            '/school/finance/scholarships' => '/school/finance/billing',
        ];
        foreach ($map as $old => $new) {
            if (str_contains($path, $old)) {
                if ($id !== '' && $old === '/school/finance/accounts') { $new .= '?student=' . (int)$id; }
                $this->redirect($new);
            }
        }
        $this->redirect('/school/finance');
    }
}
