<?php
require_once ROOT_DIR . '/core/Controller.php';

class HRController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requirePermission(['hr.manage']);
        $this->tid = $this->tenantId() ?? 0;
    }

    // Payroll lives in PayrollController (/school/payroll).

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
