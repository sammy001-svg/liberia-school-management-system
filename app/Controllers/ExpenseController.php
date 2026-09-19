<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Expenses: money the school pays out. Entries are cancelled rather than deleted so the
 * books keep an audit trail, and with expense approval switched on, entries from
 * non-approvers wait in Expense Approvals before they count.
 */
class ExpenseController extends FinanceBaseController {

    private function filters(): array {
        return [
            'q' => trim($_GET['q'] ?? ''), 'category' => $_GET['category'] ?? '', 'method' => $_GET['method'] ?? '',
            'status' => $_GET['status'] ?? '', 'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '',
        ];
    }

    private function where(array $f): array {
        $w = "e.tenant_id=?"; $p = [$this->tid];
        if ($f['q'] !== '') { $w .= " AND (e.payee LIKE ? OR e.description LIKE ? OR e.reference LIKE ?)"; array_push($p, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
        if ($f['category'] !== '') { $w .= " AND e.category=?"; $p[] = $f['category']; }
        if ($f['method'] !== '') { $w .= " AND e.method=?"; $p[] = $f['method']; }
        if (in_array($f['status'], ['active', 'pending', 'cancelled', 'rejected'], true)) { $w .= " AND e.status=?"; $p[] = $f['status']; }
        if ($f['from'] !== '') { $w .= " AND e.expense_date >= ?"; $p[] = $f['from']; }
        if ($f['to'] !== '') { $w .= " AND e.expense_date <= ?"; $p[] = $f['to']; }
        return [$w, $p];
    }

    public function index(): void {
        $this->guard(['finance.manage']);
        $f = $this->filters();
        [$where, $params] = $this->where($f);
        $total = (int)($this->db->fetchOne("SELECT COUNT(*) c FROM expenses e WHERE $where", $params)['c'] ?? 0);
        $pg = $this->paginate($total, 25);
        $rows = $this->db->fetchAll(
            "SELECT e.*, u.name AS recorded_by_name, ay.name AS year_name FROM expenses e LEFT JOIN users u ON u.id=e.recorded_by
             LEFT JOIN academic_years ay ON ay.id=e.academic_year_id
             WHERE $where ORDER BY e.expense_date DESC, e.id DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}", $params);
        $sums = $this->db->fetchAll("SELECT COALESCE(e.currency, ?) cur, SUM(e.amount) t, COUNT(*) n FROM expenses e WHERE $where AND e.status='active' GROUP BY cur",
            array_merge([$this->settings['default_currency']], $params));
        $staff = $this->db->fetchAll(
            "SELECT u.id, u.name, COALESCE(u.employee_no, '') code FROM users u JOIN roles r ON r.id=u.role_id
             WHERE u.tenant_id=? AND r.name NOT IN ('Student','Parent') AND u.status='active' ORDER BY u.name", [$this->tid]);
        $pastPayees = array_column($this->db->fetchAll("SELECT DISTINCT payee FROM expenses WHERE tenant_id=? AND payee<>'' AND payee_user_id IS NULL ORDER BY payee LIMIT 300", [$this->tid]), 'payee');
        $this->financeView('expenses_manage', [
            'pageTitle' => 'Expenses', 'rows' => $rows, 'filters' => $f, 'sums' => $sums, 'staff' => $staff, 'pastPayees' => $pastPayees,
            'categories' => $this->categories('expense'), 'years' => $this->years(), 'currentYear' => Finance::currentYear($this->db, $this->tid),
            'pendingCount' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM expenses WHERE tenant_id=? AND status='pending'", [$this->tid])['c'] ?? 0),
            'page' => $pg['page'], 'totalPages' => $pg['totalPages'], 'total' => $pg['total'], 'perPage' => $pg['perPage'],
        ]);
    }

    private function data(array &$errors): array {
        $errors = $this->validate($_POST, [
            'payee' => 'required|max:150', 'category' => 'required|max:80', 'amount' => 'required|numeric',
            'expense_date' => 'required|date', 'description' => 'required|max:255', 'reference' => 'max:150',
        ]);
        if ((float)($_POST['amount'] ?? 0) <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
        if (($_POST['expense_date'] ?? '') > date('Y-m-d')) { $errors['expense_date'] = "The payment date can't be in the future."; }
        $payeeId = (int)($_POST['payee_user_id'] ?? 0);
        if ($payeeId && !$this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$payeeId, $this->tid])) { $payeeId = 0; }
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        if (!$yearId || !$this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$yearId, $this->tid])) {
            // Default to the academic year the payment date falls in.
            $yearId = (int)($this->db->fetchOne("SELECT id FROM academic_years WHERE tenant_id=? AND ? BETWEEN start_date AND end_date LIMIT 1",
                [$this->tid, $_POST['expense_date'] ?? date('Y-m-d')])['id'] ?? 0);
        }
        // New categories typed in are added to the school's list.
        $cat = trim((string)($_POST['category'] ?? ''));
        if ($cat !== '') { $this->db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,'expense',?)", [$this->tid, mb_substr($cat, 0, 80)]); }
        return [
            'payee' => trim((string)($_POST['payee'] ?? '')), 'payee_user_id' => $payeeId ?: null, 'category' => $cat,
            'amount' => round((float)($_POST['amount'] ?? 0), 2), 'currency' => $this->currencyOrDefault($_POST['currency'] ?? null),
            'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'), 'method' => array_key_exists($_POST['method'] ?? '', Finance::PAYMENT_METHODS) ? $_POST['method'] : 'cash',
            'reference' => trim((string)($_POST['reference'] ?? '')) ?: null, 'description' => trim((string)($_POST['description'] ?? '')),
            'academic_year_id' => $yearId ?: null,
        ];
    }

    public function store(): void {
        $this->guard(['finance.manage']);
        $errors = [];
        $d = $this->data($errors);
        if ($errors) { $this->failValidation($errors, '/school/finance/expenses'); }
        $pending = $this->settings['expense_approval'] === '1' && !Finance::canApprove();
        $this->db->insert(
            "INSERT INTO expenses (tenant_id,payee,payee_user_id,category,amount,currency,expense_date,method,reference,description,academic_year_id,status,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $d['payee'], $d['payee_user_id'], $d['category'], $d['amount'], $d['currency'], $d['expense_date'], $d['method'],
             $d['reference'], $d['description'], $d['academic_year_id'], $pending ? 'pending' : 'active', $_SESSION['user_id'] ?? null]);
        $this->flash('success', $pending ? 'Expense recorded and sent for approval.' : 'Expense of ' . Finance::money($d['amount'], $d['currency']) . ' recorded.');
        $this->redirect('/school/finance/expenses');
    }

    public function update(string $id): void {
        $this->guard(['finance.manage']);
        $e = $this->db->fetchOne("SELECT * FROM expenses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$e || $e['status'] === 'cancelled') { $this->flash('danger', 'That expense can no longer be edited.'); $this->redirect('/school/finance/expenses'); }
        $errors = [];
        $d = $this->data($errors);
        if ($errors) { $this->failValidation($errors, '/school/finance/expenses'); }
        $this->db->execute(
            "UPDATE expenses SET payee=?, payee_user_id=?, category=?, amount=?, currency=?, expense_date=?, method=?, reference=?, description=?, academic_year_id=? WHERE id=? AND tenant_id=?",
            [$d['payee'], $d['payee_user_id'], $d['category'], $d['amount'], $d['currency'], $d['expense_date'], $d['method'], $d['reference'], $d['description'], $d['academic_year_id'], $id, $this->tid]);
        $this->flash('success', 'Expense updated.');
        $this->redirect('/school/finance/expenses');
    }

    public function cancel(string $id): void {
        $this->guard(['finance.manage']);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') { $this->flash('danger', 'Give a reason for cancelling the expense.'); $this->redirect('/school/finance/expenses'); }
        $n = $this->db->execute("UPDATE expenses SET status='cancelled', cancelled_at=NOW(), cancelled_by=?, cancel_reason=? WHERE id=? AND tenant_id=? AND status IN ('active','pending')",
            [$_SESSION['user_id'] ?? null, $reason, $id, $this->tid]);
        $this->flash($n ? 'success' : 'warning', $n ? 'Expense cancelled. It stays on record but no longer counts.' : 'That expense was already cancelled.');
        $this->redirect('/school/finance/expenses');
    }

    public function exportCsv(): void {
        $this->guard(['finance.manage']);
        [$where, $params] = $this->where($this->filters());
        $rows = $this->db->fetchAll("SELECT e.* FROM expenses e WHERE $where ORDER BY e.expense_date DESC, e.id DESC", $params);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expenses-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Date', 'Payee', 'Category', 'Description', 'Method', 'Reference', 'Currency', 'Amount', 'Status']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['expense_date'], $r['payee'], $r['category'], $r['description'], $r['method'], $r['reference'],
                $r['currency'] ?: $this->settings['default_currency'], $r['amount'], $r['status']]);
        }
        fclose($out);
        exit;
    }

    // ── Approvals ──────────────────────────────────────────────────

    public function approvals(): void {
        $this->guard(['finance.manage']);
        $rows = $this->db->fetchAll(
            "SELECT e.*, u.name AS recorded_by_name FROM expenses e LEFT JOIN users u ON u.id=e.recorded_by
             WHERE e.tenant_id=? AND e.status='pending' ORDER BY e.id", [$this->tid]);
        $this->financeView('expense_approvals', ['pageTitle' => 'Expense Approvals', 'rows' => $rows]);
    }

    public function decide(string $id): void {
        $this->guard(['finance.manage']);
        if (!Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $approve = ($_POST['decision'] ?? '') === 'approve';
        $n = $approve
            ? $this->db->execute("UPDATE expenses SET status='active', approved_by=?, approved_at=NOW() WHERE id=? AND tenant_id=? AND status='pending'", [$_SESSION['user_id'] ?? null, $id, $this->tid])
            : $this->db->execute("UPDATE expenses SET status='rejected', cancelled_by=?, cancelled_at=NOW(), cancel_reason=? WHERE id=? AND tenant_id=? AND status='pending'",
                [$_SESSION['user_id'] ?? null, trim((string)($_POST['reason'] ?? '')) ?: 'Rejected', $id, $this->tid]);
        $this->flash($n ? 'success' : 'warning', $n ? ($approve ? 'Expense approved.' : 'Expense rejected.') : 'That expense was already handled.');
        $this->redirect('/school/finance/expense-approvals');
    }
}
