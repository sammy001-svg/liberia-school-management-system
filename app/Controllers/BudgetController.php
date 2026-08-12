<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Budget module: income and expenditure planning with live budget-vs-actual variance.
 * Actuals are always computed from real transactions, never stored, so a budget cannot
 * drift out of step with the books.
 */
class BudgetController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function guard(): void { $this->requirePermission(['budget.manage','finance.manage']); }

    // ── BUDGETS ──────────────────────────────────────────────────────
    public function index(): void {
        $this->guard();
        $budgets = $this->db->fetchAll(
            "SELECT b.*, ay.name AS year_name,
                    COALESCE((SELECT SUM(bl.budgeted_amount) FROM budget_lines bl WHERE bl.budget_id=b.id AND bl.line_type='income'),0)  AS budget_income,
                    COALESCE((SELECT SUM(bl.budgeted_amount) FROM budget_lines bl WHERE bl.budget_id=b.id AND bl.line_type='expense'),0) AS budget_expense
             FROM budgets b LEFT JOIN academic_years ay ON b.academic_year_id=ay.id
             WHERE b.tenant_id=? ORDER BY b.period_start DESC", [$this->tid]
        );
        $this->view('school/highschool/finance/budgets/index', [
            'pageTitle' => 'Budgets', 'panelType' => 'school', 'budgets' => $budgets,
            'years' => $this->db->fetchAll("SELECT id,name,start_date,end_date FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->guard();
        $errors = $this->validate($_POST, [
            'name'         => 'required|max:150',
            'period_start' => 'required|date',
            'period_end'   => 'required|date',
        ]);
        if (!$errors && strtotime($_POST['period_end']) < strtotime($_POST['period_start'])) {
            $errors['period_end'] = 'The end date cannot be before the start date.';
        }
        if ($errors) { $this->failValidation($errors, '/school/finance/budgets'); }

        $id = $this->db->insert(
            "INSERT INTO budgets (tenant_id,name,academic_year_id,period_start,period_end,status,notes,created_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['name'], $_POST['academic_year_id'] ?: null,
                $_POST['period_start'], $_POST['period_end'], $_POST['status'] ?: 'draft',
                $_POST['notes'] ?: null, $_SESSION['user_id'],
            ]
        );
        $this->flash('success', 'Budget created — now add its income and expense lines.');
        $this->redirect('/school/finance/budgets/' . $id);
    }

    public function delete(string $id): void {
        $this->guard();
        $this->db->execute("DELETE FROM budgets WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Budget deleted.');
        $this->redirect('/school/finance/budgets');
    }

    public function updateStatus(string $id): void {
        $this->guard();
        $status = in_array($_POST['status'] ?? '', ['draft','active','closed'], true) ? $_POST['status'] : 'draft';
        $this->db->execute("UPDATE budgets SET status=? WHERE id=? AND tenant_id=?", [$status, $id, $this->tid]);
        $this->flash('success', 'Budget marked as ' . $status . '.');
        $this->redirect('/school/finance/budgets/' . $id);
    }

    // ── BUDGET DETAIL: budgeted vs actual vs variance ────────────────
    public function show(string $id): void {
        $this->guard();
        $budget = $this->db->fetchOne(
            "SELECT b.*, ay.name AS year_name FROM budgets b
             LEFT JOIN academic_years ay ON b.academic_year_id=ay.id
             WHERE b.id=? AND b.tenant_id=?", [$id, $this->tid]
        );
        if (!$budget) { $this->redirect('/school/finance/budgets'); }

        $lines = $this->db->fetchAll(
            "SELECT * FROM budget_lines WHERE budget_id=? AND tenant_id=? ORDER BY line_type DESC, sort_order, id",
            [$id, $this->tid]
        );

        $from = $budget['period_start'];
        $to   = $budget['period_end'];

        // Actuals, computed live for the budget's own period.
        $feeCollected = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) t FROM payments WHERE tenant_id=? AND DATE(paid_at) BETWEEN ? AND ?",
            [$this->tid, $from, $to]
        )['t'] ?? 0);

        $expenseByCat = [];
        foreach ($this->db->fetchAll(
            "SELECT LOWER(TRIM(category)) k, COALESCE(SUM(amount),0) t FROM expenses
             WHERE tenant_id=? AND expense_date BETWEEN ? AND ? GROUP BY k", [$this->tid, $from, $to]
        ) as $r) { $expenseByCat[$r['k']] = (float)$r['t']; }

        $incomeByCat = [];
        foreach ($this->db->fetchAll(
            "SELECT LOWER(TRIM(category)) k, COALESCE(SUM(amount),0) t FROM incomes
             WHERE tenant_id=? AND income_date BETWEEN ? AND ? GROUP BY k", [$this->tid, $from, $to]
        ) as $r) { $incomeByCat[$r['k']] = (float)$r['t']; }

        // One combined list rather than separate income/expense tables — income first,
        // then expenditure, then other, so the whole budget reads top to bottom.
        $rows = [];
        $totals = [
            'budget_income'=>0.0, 'actual_income'=>0.0,
            'budget_expense'=>0.0,'actual_expense'=>0.0,
            'budget_other'=>0.0,  'actual_other'=>0.0,
        ];
        $order = ['income'=>0, 'expense'=>1, 'other'=>2];

        foreach ($lines as $l) {
            $key  = strtolower(trim($l['category']));
            $type = $l['line_type'];

            if ($type === 'income') {
                $actual = $l['source'] === 'fees' ? $feeCollected : ($incomeByCat[$key] ?? 0.0);
                // For income, beating the budget is favourable.
                $l['variance'] = $actual - (float)$l['budgeted_amount'];
                $totals['budget_income'] += (float)$l['budgeted_amount'];
                $totals['actual_income'] += $actual;
            } else {
                // Expense and other both draw actuals from recorded expenditure.
                $actual = $expenseByCat[$key] ?? 0.0;
                // For outgoings, spending less than budget is favourable.
                $l['variance'] = (float)$l['budgeted_amount'] - $actual;
                $bucket = $type === 'other' ? 'other' : 'expense';
                $totals['budget_' . $bucket] += (float)$l['budgeted_amount'];
                $totals['actual_' . $bucket] += $actual;
            }
            $l['actual'] = $actual;
            $l['sort_group'] = $order[$type] ?? 3;
            $rows[] = $l;
        }

        usort($rows, fn($a, $b) => [$a['sort_group'], $a['sort_order'], $a['category']]
                               <=> [$b['sort_group'], $b['sort_order'], $b['category']]);

        // "Other" counts as outgoing, so the net is income less everything spent.
        $totals['budget_outgoing'] = $totals['budget_expense'] + $totals['budget_other'];
        $totals['actual_outgoing'] = $totals['actual_expense'] + $totals['actual_other'];
        $totals['budget_net'] = $totals['budget_income'] - $totals['budget_outgoing'];
        $totals['actual_net'] = $totals['actual_income'] - $totals['actual_outgoing'];

        // Expense categories already in use, offered as suggestions when adding a line.
        $knownExpenseCats = array_column($this->db->fetchAll(
            "SELECT DISTINCT category FROM expenses WHERE tenant_id=? AND category IS NOT NULL ORDER BY category", [$this->tid]
        ), 'category');
        $knownIncomeCats = array_column($this->db->fetchAll(
            "SELECT DISTINCT category FROM incomes WHERE tenant_id=? AND category IS NOT NULL ORDER BY category", [$this->tid]
        ), 'category');

        $this->view('school/highschool/finance/budgets/show', [
            'pageTitle' => $budget['name'], 'panelType' => 'school',
            'budget' => $budget, 'rows' => $rows, 'totals' => $totals,
            'knownExpenseCats' => $knownExpenseCats, 'knownIncomeCats' => $knownIncomeCats,
            'flash' => $this->getFlash(),
        ]);
    }

    public function storeLine(string $id): void {
        $this->guard();
        $budget = $this->db->fetchOne("SELECT id FROM budgets WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$budget) { $this->redirect('/school/finance/budgets'); }

        $errors = $this->validate($_POST, [
            'line_type'       => 'required',
            'category'        => 'required|max:120',
            'budgeted_amount' => 'required|numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/budgets/' . $id); }

        $this->db->insert(
            "INSERT INTO budget_lines (tenant_id,budget_id,line_type,category,description,budgeted_amount,source,sort_order)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $id,
                in_array($_POST['line_type'], ['income','expense','other'], true) ? $_POST['line_type'] : 'expense',
                trim($_POST['category']), $_POST['description'] ?: null,
                (float)$_POST['budgeted_amount'],
                ($_POST['source'] ?? 'other') === 'fees' ? 'fees' : 'other',
                (int)($_POST['sort_order'] ?? 0),
            ]
        );
        $this->flash('success', 'Budget line added.');
        $this->redirect('/school/finance/budgets/' . $id);
    }

    public function deleteLine(string $id): void {
        $this->guard();
        $line = $this->db->fetchOne("SELECT budget_id FROM budget_lines WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$line) { $this->redirect('/school/finance/budgets'); }
        $this->db->execute("DELETE FROM budget_lines WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Budget line removed.');
        $this->redirect('/school/finance/budgets/' . $line['budget_id']);
    }

    // ── OTHER INCOME (non-fee) ───────────────────────────────────────
    public function incomes(): void {
        $this->guard();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $rows = $this->db->fetchAll(
            "SELECT i.*, u.name AS recorded_by_name FROM incomes i
             LEFT JOIN users u ON i.recorded_by=u.id
             WHERE i.tenant_id=? AND i.income_date BETWEEN ? AND ?
             ORDER BY i.income_date DESC, i.id DESC", [$this->tid, $from, $to]
        );
        $byCategory = $this->db->fetchAll(
            "SELECT category, COALESCE(SUM(amount),0) total FROM incomes
             WHERE tenant_id=? AND income_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC",
            [$this->tid, $from, $to]
        );
        $this->view('school/highschool/finance/incomes', [
            'pageTitle' => 'Other Income', 'panelType' => 'school',
            'rows' => $rows, 'byCategory' => $byCategory, 'from' => $from, 'to' => $to,
            'total' => array_sum(array_column($rows, 'amount')),
            'flash' => $this->getFlash(),
        ]);
    }

    public function storeIncome(): void {
        $this->guard();
        $errors = $this->validate($_POST, [
            'category'    => 'required|max:120',
            'amount'      => 'required|numeric',
            'income_date' => 'required|date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/incomes'); }

        $this->db->insert(
            "INSERT INTO incomes (tenant_id,category,description,amount,income_date,source,method,reference,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, trim($_POST['category']), $_POST['description'] ?: null,
                (float)$_POST['amount'], $_POST['income_date'], $_POST['source'] ?: null,
                $_POST['method'] ?: null, $_POST['reference'] ?: null, $_SESSION['user_id'],
            ]
        );
        $this->flash('success', 'Income recorded.');
        $this->redirect('/school/finance/incomes');
    }

    public function deleteIncome(string $id): void {
        $this->guard();
        $this->db->execute("DELETE FROM incomes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Income entry removed.');
        $this->redirect('/school/finance/incomes');
    }
}
