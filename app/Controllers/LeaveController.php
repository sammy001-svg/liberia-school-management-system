<?php
require_once ROOT_DIR . '/core/Controller.php';

// Self-service leave requests for any school-panel staff member (Teacher, Staff,
// Accountant, School Admin, etc.) — separate from HRController, which is the
// admin-only review/approve screen gated behind the hr.manage permission.
class LeaveController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        if (!isset($_SESSION['user_id'])) { $this->redirect('/login'); }
        if (!empty($_SESSION['student_id']) || !empty($_SESSION['parent_id'])) { $this->redirect('/login'); }
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        $userId = $_SESSION['user_id'];
        $leaves = $this->db->fetchAll(
            "SELECT * FROM leave_applications WHERE tenant_id=? AND user_id=? ORDER BY created_at DESC",
            [$this->tid, $userId]
        );
        $stats = [
            'pending'  => count(array_filter($leaves, fn($l) => $l['status'] === 'pending')),
            'approved' => count(array_filter($leaves, fn($l) => $l['status'] === 'approved')),
            'rejected' => count(array_filter($leaves, fn($l) => $l['status'] === 'rejected')),
        ];
        $this->view('school/hr/leaves/my', [
            'pageTitle' => 'My Leave',
            'panelType' => 'school',
            'leaves' => $leaves,
            'stats' => $stats,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $errors = $this->validate($_POST, [
            'leave_type' => 'required|in:sick,annual,maternity,paternity,unpaid,other',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);
        if (!$errors && strtotime($_POST['end_date']) < strtotime($_POST['start_date'])) {
            $errors['end_date'] = 'End date cannot be before the start date.';
        }
        if ($errors) { $this->failValidation($errors, '/school/my-leave'); }

        $this->db->insert(
            "INSERT INTO leave_applications (tenant_id, user_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')",
            [$this->tid, $_SESSION['user_id'], $_POST['leave_type'], $_POST['start_date'], $_POST['end_date'], $_POST['reason'] ?? '']
        );
        $this->flash('success', 'Leave request submitted.');
        $this->redirect('/school/my-leave');
    }
}
