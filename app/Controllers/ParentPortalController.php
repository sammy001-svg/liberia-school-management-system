<?php
require_once ROOT_DIR . '/core/Controller.php';

class ParentPortalController extends Controller {
    private int $pid;
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['Parent']);
        $this->pid = $_SESSION['parent_id'] ?? 0;
        $this->tid = $this->tenantId() ?? 0;
    }

    public function dashboard(): void {
        $children = $this->db->fetchAll(
            "SELECT s.*, u.name, c.name as class_name 
             FROM parent_students ps 
             JOIN students s ON ps.student_id = s.id 
             JOIN users u ON s.user_id = u.id 
             LEFT JOIN classes c ON s.class_id = c.id 
             WHERE ps.parent_id = ?", 
            [$this->pid]
        );

        $this->view('school/portals/parent/dashboard', [
            'pageTitle' => 'Parent Dashboard',
            'panelType' => 'parent',
            'children' => $children
        ]);
    }

    public function viewChild(int $sid): void {
        // Security check: Is this student linked to this parent?
        $link = $this->db->fetchOne("SELECT * FROM parent_students WHERE parent_id = ? AND student_id = ?", [$this->pid, $sid]);
        if (!$link) {
            $this->flash('error', 'Unauthorized access to student record.');
            $this->redirect('/parent/dashboard');
        }

        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, c.name as class_name 
             FROM students s 
             JOIN users u ON s.user_id = u.id 
             LEFT JOIN classes c ON s.class_id = c.id 
             WHERE s.id = ?", 
            [$sid]
        );

        $attendance = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present
             FROM attendance 
             WHERE student_id = ?", 
            [$sid]
        );

        $grades = $this->db->fetchAll(
            "SELECT g.*, e.name as exam_name, co.name as course_name 
             FROM grades g 
             JOIN exams e ON g.exam_id = e.id 
             JOIN courses co ON g.course_id = co.id 
             WHERE g.student_id = ? ORDER BY e.date DESC", 
            [$sid]
        );

        $invoices = $this->db->fetchAll(
            "SELECT * FROM invoices WHERE student_id = ? ORDER BY created_at DESC", 
            [$sid]
        );

        $this->view('school/portals/parent/student_detail', [
            'pageTitle' => 'Child Profile: ' . $student['name'],
            'panelType' => 'parent',
            'student' => $student,
            'attendance' => $attendance,
            'grades' => $grades,
            'invoices' => $invoices
        ]);
    }

    public function finance(): void {
        $invoices = $this->db->fetchAll(
            "SELECT i.*, u.name as student_name 
             FROM invoices i 
             JOIN students s ON i.student_id = s.id 
             JOIN users u ON s.user_id = u.id 
             JOIN parent_students ps ON s.id = ps.student_id 
             WHERE ps.parent_id = ? ORDER BY i.created_at DESC", 
            [$this->pid]
        );

        $this->view('school/portals/parent/finance', [
            'pageTitle' => 'Financial Overview',
            'panelType' => 'parent',
            'invoices' => $invoices
        ]);
    }
}
