<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Services/Finance.php';

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

    // "Arrears" = an unpaid/partial bill past its due date, on ANY linked child. Account-wide,
    // not per-child: one overdue child restricts detail pages for all of this parent's children.
    // Overdue per currency (LRD and USD are never added together); empty when nothing is overdue.
    private function overdueTotal(): array {
        $rows = $this->db->fetchAll(
            "SELECT COALESCE(i.currency, ?) AS cur, SUM(i.amount_due - i.amount_paid - i.discount) AS total
             FROM invoices i JOIN parent_students ps ON ps.student_id = i.student_id
             WHERE ps.parent_id = ? AND i.tenant_id = ? AND i.status NOT IN ('paid','waived') AND i.due_date IS NOT NULL AND i.due_date < CURDATE()
             GROUP BY cur HAVING total > 0.005",
            [Finance::settings($this->db, $this->tid)['default_currency'], $this->pid, $this->tid]
        );
        return array_map('floatval', array_column($rows, 'total', 'cur'));
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
        $overdueTotal = $this->restrictionEnabled ? $this->overdueTotal() : [];

        $this->view('school/portals/parent/dashboard', [
            'pageTitle' => 'Parent Dashboard',
            'panelType' => 'parent',
            'children' => $children,
            'hasArrears' => (bool)$overdueTotal,
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
            if ($overdueTotal) {
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

        $acct = Finance::studentAccount($this->db, $this->tid, $sid);

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
            'acct' => $acct,
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
            if ($overdueTotal) {
                $this->view('school/portals/parent/restricted', [
                    'pageTitle' => 'Access Restricted',
                    'panelType' => 'parent',
                    'student' => $student,
                    'overdueTotal' => $overdueTotal,
                ]);
                return;
            }
        }

        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);

        // publishedOnly: a parent only ever sees marks the school has released.
        $this->view('school/report_card_celdi', array_merge(
            ['pageTitle' => 'Report Card', 'tenant' => $tenant, 'student' => $student],
            $this->buildCeldiReportCard($studentId, $student, true)
        ));
    }

    private function children(): array {
        return $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, c.name AS class_name
             FROM parent_students ps JOIN students s ON ps.student_id = s.id JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE ps.parent_id = ? AND s.tenant_id = ? ORDER BY u.name",
            [$this->pid, $this->tid]
        );
    }

    private function ownsStudent(int $sid): bool {
        return (bool)$this->db->fetchOne("SELECT 1 FROM parent_students WHERE parent_id=? AND student_id=?", [$this->pid, $sid]);
    }

    /** Fees for each child: bills and installments, payments and receipts, per school year. */
    public function finance(): void {
        $children = $this->children();
        $childId = (int)($_GET['child'] ?? 0);
        $ids = array_map('intval', array_column($children, 'id'));
        if (!in_array($childId, $ids, true)) { $childId = $ids[0] ?? 0; }

        // A one-line balance for every child, so the tabs show who owes what this year.
        foreach ($children as &$c) {
            $c['totals'] = Finance::studentAccount($this->db, $this->tid, (int)$c['id'])['totals'];
        }
        unset($c);

        $this->view('school/portals/parent/finance', [
            'pageTitle' => 'School Fees',
            'panelType' => 'parent',
            'children' => $children,
            'childId' => $childId,
            'acct' => $childId ? Finance::studentAccount($this->db, $this->tid, $childId, (int)($_GET['year'] ?? 0) ?: null) : null,
        ]);
    }

    /** One receipt, only for a payment on one of this parent's children. */
    public function receipt(string $id): void {
        $rows = Finance::receiptRows($this->db, $this->tid, 'p.id=? AND EXISTS (SELECT 1 FROM parent_students ps WHERE ps.parent_id=? AND ps.student_id=i.student_id)', [(int)$id, $this->pid]);
        if (!$rows) { $this->flash('error', 'That receipt is not available.'); $this->redirect('/parent/finance'); }
        $this->printReceipts($rows, 'Receipt #' . (int)$id);
    }

    /** Every receipt for one child in one year. */
    public function receipts(): void {
        $sid = (int)($_GET['child'] ?? 0);
        $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [(int)($_GET['year'] ?? 0), $this->tid]);
        $rows = $year && $this->ownsStudent($sid) ? Finance::receiptRows($this->db, $this->tid,
            'i.student_id=? AND (i.academic_year_id=? OR (i.academic_year_id IS NULL AND DATE(i.created_at) BETWEEN ? AND ?))',
            [$sid, $year['id'], $year['start_date'], $year['end_date']]) : [];
        if (!$rows) { $this->flash('error', 'No receipts to print for that year.'); $this->redirect('/parent/finance?child=' . $sid); }
        $this->printReceipts($rows, 'Receipts — ' . $rows[0]['student_name']);
    }

    private function printReceipts(array $rows, string $title): void {
        $this->view('school/highschool/finance/receipt_print', [
            'pageTitle' => $title, 'rows' => $rows, 'copies' => ['Receipt'],
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'finSettings' => Finance::settings($this->db, $this->tid),
        ]);
    }
}
