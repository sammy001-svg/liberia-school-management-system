<?php
require_once ROOT_DIR . '/core/Controller.php';

class ParentPortalController extends Controller {
    private int $pid;
    private int $tid;
    private bool $restrictionEnabled;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['Parent']);
        $this->pid = $_SESSION['parent_id'] ?? 0;
        $this->tid = $this->tenantId() ?? 0;
        $this->restrictionEnabled = (bool)($this->db->fetchOne("SELECT restrict_parent_arrears FROM tenants WHERE id=?", [$this->tid])['restrict_parent_arrears'] ?? false);
    }

    // "Arrears" = an unpaid/partial invoice past its due date, on ANY linked child — matches the
    // overdue calculation already used in FinanceController::collection(). Account-wide, not
    // per-child: one overdue child restricts detail pages for all of this parent's children.
    private function overdueTotal(): float {
        $total = $this->db->fetchOne(
            "SELECT COALESCE(SUM(i.amount_due - i.amount_paid - i.discount), 0) AS total
             FROM invoices i JOIN parent_students ps ON ps.student_id = i.student_id
             WHERE ps.parent_id = ? AND i.status NOT IN ('paid','waived') AND i.due_date IS NOT NULL AND i.due_date < CURDATE()",
            [$this->pid]
        );
        return (float)($total['total'] ?? 0);
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
        $overdueTotal = $this->restrictionEnabled ? $this->overdueTotal() : 0;

        $this->view('school/portals/parent/dashboard', [
            'pageTitle' => 'Parent Dashboard',
            'panelType' => 'parent',
            'children' => $children,
            'hasArrears' => $overdueTotal > 0,
            'overdueTotal' => $overdueTotal,
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

        if ($this->restrictionEnabled) {
            $overdueTotal = $this->overdueTotal();
            if ($overdueTotal > 0) {
                $this->view('school/portals/parent/restricted', [
                    'pageTitle' => 'Access Restricted',
                    'panelType' => 'parent',
                    'student' => $student,
                    'overdueTotal' => $overdueTotal,
                ]);
                return;
            }
        }

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
             WHERE g.student_id = ? AND e.status = 'published' ORDER BY e.exam_date DESC",
            [$sid]
        );

        $invoices = $this->db->fetchAll(
            "SELECT * FROM invoices WHERE student_id = ? ORDER BY created_at DESC",
            [$sid]
        );

        $busInfo = $this->db->fetchOne(
            "SELECT br.name AS route_name, br.stops, br.departure_time, br.return_time,
                    bs.pickup_stop,
                    b.bus_number, b.plate_number,
                    d.name AS driver_name, d.phone AS driver_phone
             FROM bus_students bs
             JOIN bus_routes br ON bs.route_id = br.id
             LEFT JOIN buses b ON br.bus_id = b.id
             LEFT JOIN bus_drivers d ON br.driver_id = d.id
             WHERE bs.student_id = ? AND bs.tenant_id = ? AND bs.status = 'active'",
            [$sid, $this->tid]
        );

        $this->view('school/portals/parent/student_detail', [
            'pageTitle' => 'Child Profile: ' . $student['name'],
            'panelType' => 'parent',
            'student' => $student,
            'attendance' => $attendance,
            'grades' => $grades,
            'invoices' => $invoices,
            'busInfo' => $busInfo ?: null,
        ]);
    }

    public function reportCard(string $studentId): void {
        $link = $this->db->fetchOne("SELECT 1 FROM parent_students WHERE parent_id=? AND student_id=?", [$this->pid, $studentId]);
        if (!$link) {
            $this->flash('error', 'Unauthorized access to student record.');
            $this->redirect('/parent/dashboard');
        }

        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",
            [$studentId, $this->tid]
        );
        if (!$student) { $this->redirect('/parent/dashboard'); }

        if ($this->restrictionEnabled) {
            $overdueTotal = $this->overdueTotal();
            if ($overdueTotal > 0) {
                $this->view('school/portals/parent/restricted', [
                    'pageTitle' => 'Access Restricted',
                    'panelType' => 'parent',
                    'student' => $student,
                    'overdueTotal' => $overdueTotal,
                ]);
                return;
            }
        }

        $class  = $student['class_id'] ? $this->db->fetchOne("SELECT * FROM classes WHERE id=?", [$student['class_id']]) : null;
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);

        $this->view('school/report_card', array_merge(
            ['pageTitle' => 'Report Card', 'tenant' => $tenant, 'student' => $student, 'class' => $class],
            $this->buildReportCardData($studentId, $student, true)
        ));
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
