<?php
require_once ROOT_DIR . '/core/Controller.php';

class HRController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requirePermission(['hr.manage']);
        $this->tid = $this->tenantId() ?? 0;
    }

    // --- PAYROLL ---
    public function payroll(): void {
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        $records = $this->db->fetchAll(
            "SELECT p.*, u.name as staff_name
             FROM payroll p
             JOIN users u ON p.user_id = u.id
             WHERE p.tenant_id = ? AND p.month = ? AND p.year = ? ORDER BY u.name",
            [$this->tid, $month, $year]
        );
        $stats = [
            'total' => count($records),
            'netTotal' => array_sum(array_column($records, 'net_salary')),
            'draft' => count(array_filter($records, fn($r) => $r['status'] === 'draft')),
            'paid' => count(array_filter($records, fn($r) => $r['status'] === 'paid')),
        ];

        // Payroll is only ever generated for people who already have a staff_salaries row —
        // but that row is only created from the Staff page's own create/edit form. Teachers in
        // particular are created through a separate flow that never touches staff_salaries, so
        // they silently never appear here unless someone remembers to set their salary
        // afterward. Surface exactly who's missing so "payroll isn't showing everyone" is
        // self-explanatory instead of a mystery.
        $missingSalary = $this->db->fetchAll(
            "SELECT u.id, u.name, r.name AS role_name
             FROM users u JOIN roles r ON u.role_id=r.id
             WHERE u.tenant_id=? AND u.status='active' AND r.name IN ('Staff','Accountant','Teacher')
               AND NOT EXISTS (SELECT 1 FROM staff_salaries s WHERE s.user_id=u.id)
             ORDER BY u.name", [$this->tid]
        );

        $this->view('school/hr/payroll/index', [
            'pageTitle' => 'Staff Payroll',
            'panelType' => 'school',
            'records' => $records,
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
            'missingSalary' => $missingSalary,
            'flash' => $this->getFlash()
        ]);
    }

    public function generatePayroll(): void {
        $month = $_POST['month'] ?? date('n');
        $year  = $_POST['year']  ?? date('Y');

        // Check if already generated
        $exists = $this->db->fetchOne("SELECT id FROM payroll WHERE tenant_id = ? AND month = ? AND year = ?", [$this->tid, $month, $year]);
        if ($exists) {
            $this->flash('warning', 'Payroll for this period already exists.');
            $this->redirect('/school/hr/payroll');
        }

        // Get all active staff with set salaries
        $salaries = $this->db->fetchAll(
            "SELECT s.* FROM staff_salaries s
             JOIN users u ON s.user_id = u.id
             WHERE s.tenant_id = ? AND u.status = 'active'",
            [$this->tid]
        );

        foreach ($salaries as $s) {
            $net = $s['basic_salary'] + $s['allowances'] - $s['deductions'];
            $this->db->insert(
                "INSERT INTO payroll (tenant_id, user_id, month, year, basic_salary, allowances, deductions, net_salary, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')",
                [$this->tid, $s['user_id'], $month, $year, $s['basic_salary'], $s['allowances'], $s['deductions'], $net]
            );
        }

        $skipped = $this->db->fetchOne(
            "SELECT COUNT(*) c FROM users u JOIN roles r ON u.role_id=r.id
             WHERE u.tenant_id=? AND u.status='active' AND r.name IN ('Staff','Accountant','Teacher')
               AND NOT EXISTS (SELECT 1 FROM staff_salaries s WHERE s.user_id=u.id)", [$this->tid]
        )['c'] ?? 0;

        $message = 'Payroll draft generated for ' . count($salaries) . ' staff (' . date("F", mktime(0, 0, 0, $month, 10)) . ' ' . $year . ').';
        if ($skipped > 0) {
            $message .= " {$skipped} active staff/teacher(s) were skipped because they have no salary set up yet — see below.";
        }
        $this->flash($skipped > 0 ? 'warning' : 'success', $message);
        $this->redirect('/school/hr/payroll');
    }

    // Marking a payslip paid is the moment the salary actually leaves the school's
    // account, so it also records a matching Expense (category 'Salaries', matching
    // the preset already offered on the manual Expense form) — this is what makes
    // Net Income and the Financial Reports reflect real payroll cost.
    // Guarded by checking current status first so re-submitting an already-paid
    // record (e.g. a double click) never creates a duplicate expense.
    public function markPayrollPaid(string $id): void {
        $record = $this->db->fetchOne(
            "SELECT p.*, u.name AS staff_name FROM payroll p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.tenant_id=?",
            [$id, $this->tid]
        );
        if (!$record) { $this->redirect('/school/hr/payroll'); }
        if ($record['status'] === 'paid') {
            $this->redirect('/school/hr/payroll?month='.$record['month'].'&year='.$record['year']);
        }

        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            $this->db->execute("UPDATE payroll SET status='paid', paid_at=NOW() WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $monthLabel = date('F', mktime(0, 0, 0, (int)$record['month'], 10)) . ' ' . $record['year'];
            $this->db->insert(
                "INSERT INTO expenses (tenant_id,category,description,amount,expense_date,payee,method,reference,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)",
                [$this->tid, 'Salaries', "Salary - {$record['staff_name']} - {$monthLabel}", $record['net_salary'], date('Y-m-d'), $record['staff_name'], 'bank', 'payroll:'.$id, $_SESSION['user_id']]
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("markPayrollPaid failed: " . $e->getMessage());
            $this->flash('danger', 'Could not mark payroll as paid — no changes were made. Please try again.');
            $this->redirect('/school/hr/payroll?month='.$record['month'].'&year='.$record['year']);
        }
        $this->flash('success', 'Marked as paid and recorded as a Payroll expense.');
        $this->redirect('/school/hr/payroll?month='.$record['month'].'&year='.$record['year']);
    }

    public function payslip(string $id): void {
        $record = $this->db->fetchOne(
            "SELECT p.*, u.name, u.email, r.name AS role_name
             FROM payroll p JOIN users u ON p.user_id=u.id JOIN roles r ON u.role_id=r.id
             WHERE p.id=? AND p.tenant_id=?", [$id, $this->tid]
        );
        if (!$record) { $this->redirect('/school/hr/payroll'); }
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/hr/payroll/payslip', ['pageTitle'=>'Payslip','tenant'=>$tenant,'record'=>$record]);
    }

    // --- LEAVES ---
    public function leaves(): void {
        $leaves = $this->db->fetchAll(
            "SELECT l.*, u.name as staff_name
             FROM leave_applications l
             JOIN users u ON l.user_id = u.id
             WHERE l.tenant_id = ? ORDER BY l.created_at DESC",
            [$this->tid]
        );
        $stats = [
            'pending'  => count(array_filter($leaves, fn($l) => $l['status'] === 'pending')),
            'approved' => count(array_filter($leaves, fn($l) => $l['status'] === 'approved')),
            'rejected' => count(array_filter($leaves, fn($l) => $l['status'] === 'rejected')),
        ];

        $this->view('school/hr/leaves/index', [
            'pageTitle' => 'Leave Management',
            'panelType' => 'school',
            'leaves' => $leaves,
            'stats' => $stats,
            'flash' => $this->getFlash()
        ]);
    }

    public function approveLeave(): void {
        $id = $_POST['id'];
        $status = $_POST['status']; // approved / rejected
        $this->db->execute(
            "UPDATE leave_applications SET status = ?, approved_by = ? WHERE id = ? AND tenant_id = ?",
            [$status, $_SESSION['user_id'], $id, $this->tid]
        );
        $this->flash('success', 'Leave application ' . $status);
        $this->redirect('/school/hr/leaves');
    }
}
