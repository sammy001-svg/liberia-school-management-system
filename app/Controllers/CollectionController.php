<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Extra Collections: money received outside school fees — uniforms, cafeteria, textbooks,
 * entrance tests, donations. Stored in `incomes`, so budgets and the P&L pick it up.
 * A payer can be a student, parent, staff member or anyone typed in; a balance can be
 * recorded for things paid in part (e.g. a uniform).
 */
class CollectionController extends FinanceBaseController {

    private function filters(): array {
        return [
            'q' => trim($_GET['q'] ?? ''), 'category' => $_GET['category'] ?? '', 'status' => $_GET['status'] ?? '',
            'from' => $_GET['from'] ?? '', 'to' => $_GET['to'] ?? '', 'method' => $_GET['method'] ?? '',
            'class' => (int)($_GET['class'] ?? 0), 'gender' => $_GET['gender'] ?? '', 'balance' => $_GET['balance'] ?? '',
        ];
    }

    private function query(array $f): array {
        $w = "i.tenant_id=?"; $p = [$this->tid];
        if ($f['q'] !== '') { $w .= " AND (i.source LIKE ? OR i.description LIKE ? OR i.reference LIKE ? OR s.admission_no LIKE ?)"; array_push($p, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%"); }
        if ($f['category'] !== '') { $w .= " AND i.category=?"; $p[] = $f['category']; }
        if (in_array($f['status'], ['active', 'cancelled'], true)) { $w .= " AND i.status=?"; $p[] = $f['status']; }
        if ($f['from'] !== '') { $w .= " AND i.income_date >= ?"; $p[] = $f['from']; }
        if ($f['to'] !== '') { $w .= " AND i.income_date <= ?"; $p[] = $f['to']; }
        if ($f['method'] !== '') { $w .= " AND i.method=?"; $p[] = $f['method']; }
        if ($f['class']) { $w .= " AND s.class_id=?"; $p[] = $f['class']; }
        if (in_array($f['gender'], ['male', 'female'], true)) { $w .= " AND su.gender=?"; $p[] = $f['gender']; }
        if ($f['balance'] === 'outstanding') { $w .= " AND i.balance_owed > 0"; }
        if ($f['balance'] === 'settled') { $w .= " AND (i.balance_owed IS NULL OR i.balance_owed <= 0)"; }
        $from = "FROM incomes i LEFT JOIN students s ON i.payer_type='student' AND s.id=i.payer_id LEFT JOIN users su ON su.id=s.user_id";
        return [$from, $w, $p];
    }

    public function index(): void {
        $this->guard(['finance.manage']);
        $f = $this->filters();
        [$from, $where, $params] = $this->query($f);
        $total = (int)($this->db->fetchOne("SELECT COUNT(*) c $from WHERE $where", $params)['c'] ?? 0);
        $pg = $this->paginate($total, 25);
        $rows = $this->db->fetchAll(
            "SELECT i.*, s.admission_no, u.name AS recorded_by_name $from LEFT JOIN users u ON u.id=i.recorded_by
             WHERE $where ORDER BY i.income_date DESC, i.id DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}", $params);
        $sums = $this->db->fetchAll("SELECT COALESCE(i.currency, ?) cur, SUM(i.amount) t, COUNT(*) n, SUM(COALESCE(i.balance_owed,0)) owed $from WHERE $where AND i.status='active' GROUP BY cur",
            array_merge([$this->settings['default_currency']], $params));
        $payers = $this->db->fetchAll(
            "SELECT 'student' t, s.id, CONCAT(u.name, ' (', s.admission_no, ')') label FROM students s JOIN users u ON u.id=s.user_id WHERE s.tenant_id=? AND s.status='active'
             UNION ALL SELECT 'parent', p.id, CONCAT(u.name, ' (Parent)') FROM parents p JOIN users u ON u.id=p.user_id WHERE p.tenant_id=?
             UNION ALL SELECT 'staff', u.id, CONCAT(u.name, ' (Staff)') FROM users u JOIN roles r ON r.id=u.role_id WHERE u.tenant_id=? AND r.name NOT IN ('Student','Parent') AND u.status='active'
             ORDER BY label", [$this->tid, $this->tid, $this->tid]);
        $this->financeView('collections', [
            'pageTitle' => 'Extra Collections', 'rows' => $rows, 'filters' => $f, 'sums' => $sums, 'payers' => $payers,
            'categories' => $this->categories('collection'), 'classes' => $this->classes(), 'years' => $this->years(),
            'page' => $pg['page'], 'totalPages' => $pg['totalPages'], 'total' => $pg['total'], 'perPage' => $pg['perPage'],
        ]);
    }

    public function store(): void {
        $this->guard(['finance.manage']);
        $errors = $this->validate($_POST, ['payer' => 'required|max:150', 'category' => 'required|max:120', 'amount' => 'required|numeric', 'income_date' => 'required|date', 'description' => 'max:255', 'reference' => 'max:60']);
        if ((float)($_POST['amount'] ?? 0) <= 0) { $errors['amount'] = 'Enter an amount greater than zero.'; }
        if (($_POST['income_date'] ?? '') > date('Y-m-d')) { $errors['income_date'] = "The payment date can't be in the future."; }
        $file = $this->storeProof('receipt_file', $errors);
        if ($errors) { $this->failValidation($errors, '/school/finance/collections'); }

        // "student:12" / "parent:4" / "staff:9" from the payer list; anything else is a typed name.
        $type = 'other'; $payerId = null;
        if (preg_match('/^(student|parent|staff):(\d+)$/', (string)($_POST['payer_ref'] ?? ''), $m)) {
            $table = ['student' => 'students', 'parent' => 'parents', 'staff' => 'users'][$m[1]];
            if ($this->db->fetchOne("SELECT id FROM {$table} WHERE id=? AND tenant_id=?", [(int)$m[2], $this->tid])) { $type = $m[1]; $payerId = (int)$m[2]; }
        }
        $cat = trim((string)$_POST['category']);
        $this->db->execute("INSERT IGNORE INTO finance_categories (tenant_id,kind,name) VALUES (?,'collection',?)", [$this->tid, mb_substr($cat, 0, 120)]);
        $yearId = (int)($this->db->fetchOne("SELECT id FROM academic_years WHERE tenant_id=? AND ? BETWEEN start_date AND end_date LIMIT 1", [$this->tid, $_POST['income_date']])['id'] ?? 0);
        $balance = trim((string)($_POST['balance_owed'] ?? '')) === '' ? null : max(0, round((float)$_POST['balance_owed'], 2));
        $id = $this->db->insert(
            "INSERT INTO incomes (tenant_id,category,description,amount,currency,income_date,source,payer_type,payer_id,balance_owed,method,reference,academic_year_id,receipt_file,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $cat, trim((string)($_POST['description'] ?? '')) ?: null, round((float)$_POST['amount'], 2), $this->currencyOrDefault($_POST['currency'] ?? null),
             $_POST['income_date'], trim((string)$_POST['payer']), $type, $payerId, $balance,
             array_key_exists($_POST['method'] ?? '', Finance::PAYMENT_METHODS) ? $_POST['method'] : 'cash',
             trim((string)($_POST['reference'] ?? '')) ?: null, $yearId ?: null, $file, $_SESSION['user_id'] ?? null]);
        $this->flash('success', 'Collection recorded.');
        $this->redirect('/school/finance/collections?receipt=' . $id);
    }

    public function cancel(string $id): void {
        $this->guard(['finance.manage']);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') { $this->flash('danger', 'Give a reason for cancelling.'); $this->redirect('/school/finance/collections'); }
        $n = $this->db->execute("UPDATE incomes SET status='cancelled', cancelled_at=NOW(), cancelled_by=?, cancel_reason=? WHERE id=? AND tenant_id=? AND status='active'",
            [$_SESSION['user_id'] ?? null, $reason, $id, $this->tid]);
        $this->flash($n ? 'success' : 'warning', $n ? 'Collection cancelled. It stays on record but no longer counts.' : 'Already cancelled.');
        $this->redirect('/school/finance/collections');
    }

    /** Settles (or reduces) the balance still owed on a part-paid collection. */
    public function settle(string $id): void {
        $this->guard(['finance.manage']);
        $row = $this->db->fetchOne("SELECT * FROM incomes WHERE id=? AND tenant_id=? AND status='active'", [$id, $this->tid]);
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        if (!$row || $amount <= 0 || $amount > (float)$row['balance_owed'] + 0.005) {
            $this->flash('danger', 'Enter an amount no more than the balance owed.');
            $this->redirect('/school/finance/collections');
        }
        // The balance payment is its own collection (so it lands on the day it was received).
        $newId = $this->db->insert(
            "INSERT INTO incomes (tenant_id,category,description,amount,currency,income_date,source,payer_type,payer_id,balance_owed,method,reference,academic_year_id,recorded_by)
             VALUES (?,?,?,?,?,CURDATE(),?,?,?,?,?,?,?,?)",
            [$this->tid, $row['category'], 'Balance payment for #' . $row['id'] . ($row['description'] ? ' — ' . $row['description'] : ''), $amount, $row['currency'],
             $row['source'], $row['payer_type'], $row['payer_id'], null, array_key_exists($_POST['method'] ?? '', Finance::PAYMENT_METHODS) ? $_POST['method'] : 'cash',
             trim((string)($_POST['reference'] ?? '')) ?: null, $row['academic_year_id'], $_SESSION['user_id'] ?? null]);
        $this->db->execute("UPDATE incomes SET balance_owed=? WHERE id=?", [max(0, round((float)$row['balance_owed'] - $amount, 2)), $id]);
        $this->flash('success', 'Balance payment recorded.');
        $this->redirect('/school/finance/collections?receipt=' . $newId);
    }

    public function receipt(string $id): void {
        $this->guard(['finance.manage']);
        $row = $this->db->fetchOne(
            "SELECT i.*, s.admission_no, c.name AS class_name, u.name AS recorded_by_name FROM incomes i
             LEFT JOIN students s ON i.payer_type='student' AND s.id=i.payer_id LEFT JOIN classes c ON c.id=s.class_id
             LEFT JOIN users u ON u.id=i.recorded_by WHERE i.id=? AND i.tenant_id=? AND i.status='active'", [$id, $this->tid]);
        if (!$row) { $this->flash('danger', 'That receipt is not available.'); $this->redirect('/school/finance/collections'); }
        $this->view('school/highschool/finance/collection_receipt', [
            'pageTitle' => 'Receipt #' . (int)$id, 'r' => $row, 'finSettings' => $this->settings,
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
        ]);
    }
}
