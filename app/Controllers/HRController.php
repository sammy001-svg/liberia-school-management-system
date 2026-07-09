<?php
require_once ROOT_DIR . '/core/Controller.php';

class HRController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['School Admin', 'Accountant']);
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

        $this->view('school/hr/payroll/index', [
            'pageTitle' => 'Staff Payroll',
            'panelType' => 'school',
            'records' => $records,
            'month' => $month,
            'year' => $year,
            'stats' => $stats,
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

        $this->flash('success', 'Payroll draft generated for ' . date("F", mktime(0, 0, 0, $month, 10)) . ' ' . $year);
        $this->redirect('/school/hr/payroll');
    }

    public function markPayrollPaid(string $id): void {
        $record = $this->db->fetchOne("SELECT month,year FROM payroll WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($record) {
            $this->db->execute("UPDATE payroll SET status='paid', paid_at=NOW() WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->flash('success', 'Marked as paid.');
            $this->redirect('/school/hr/payroll?month='.$record['month'].'&year='.$record['year']);
        }
        $this->redirect('/school/hr/payroll');
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
