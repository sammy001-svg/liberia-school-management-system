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

        $this->view('school/portals/parent/student_detail', [
            'pageTitle' => 'Child Profile: ' . $student['name'],
            'panelType' => 'parent',
            'student' => $student,
            'attendance' => $attendance,
            'grades' => $grades,
            'invoices' => $invoices
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

        $examOptions = $this->db->fetchAll(
            "SELECT DISTINCT e.id, e.name, e.exam_date FROM grades g JOIN exams e ON g.exam_id=e.id WHERE g.student_id=? AND g.tenant_id=? AND e.status='published' ORDER BY e.exam_date DESC",
            [$studentId, $this->tid]
        );
        $examId = $_GET['exam_id'] ?? ($examOptions[0]['id'] ?? null);
        $exam   = $examId ? $this->db->fetchOne("SELECT * FROM exams WHERE id=? AND tenant_id=? AND status='published'", [$examId, $this->tid]) : null;

        $grades = [];
        $totalObtained = 0; $totalPossible = 0;
        if ($exam) {
            $grades = $this->db->fetchAll(
                "SELECT g.*, c.name AS course_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id WHERE g.student_id=? AND g.exam_id=? AND g.tenant_id=? ORDER BY c.name",
                [$studentId, $examId, $this->tid]
            );
            foreach ($grades as $g) { $totalObtained += $g['marks_obtained']; $totalPossible += $g['total_marks']; }
        }
        $overallPct = $totalPossible > 0 ? round($totalObtained / $totalPossible * 100, 1) : 0;
        $overallGrade = $overallPct>=90?'A+':($overallPct>=80?'A':($overallPct>=70?'B':($overallPct>=60?'C':($overallPct>=50?'D':'F'))));

        $rank = null; $rankOf = null;
        if ($exam && $student['class_id']) {
            $classTotals = $this->db->fetchAll(
                "SELECT g.student_id, SUM(g.marks_obtained) obtained
                 FROM grades g JOIN students s2 ON g.student_id=s2.id
                 WHERE g.exam_id=? AND g.tenant_id=? AND s2.class_id=?
                 GROUP BY g.student_id ORDER BY obtained DESC",
                [$examId, $this->tid, $student['class_id']]
            );
            $rankOf = count($classTotals);
            foreach ($classTotals as $i => $row) {
                if ((int)$row['student_id'] === (int)$studentId) { $rank = $i + 1; break; }
            }
        }

        $attendance = null;
        if ($exam && $exam['term_id']) {
            $term = $this->db->fetchOne("SELECT * FROM terms WHERE id=?", [$exam['term_id']]);
            if ($term) {
                $total = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND date BETWEEN ? AND ?", [$studentId, $this->tid, $term['start_date'], $term['end_date']])['c'];
                $present = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND status='present' AND date BETWEEN ? AND ?", [$studentId, $this->tid, $term['start_date'], $term['end_date']])['c'];
                $attendance = ['total'=>$total, 'present'=>$present, 'pct'=>$total>0 ? round($present/$total*100,1) : null];
            }
        }

        $this->view('school/report_card', [
            'pageTitle' => 'Report Card', 'tenant' => $tenant,
            'student' => $student, 'class' => $class, 'exam' => $exam,
            'examOptions' => $examOptions, 'selectedExamId' => $examId,
            'grades' => $grades, 'totalObtained' => $totalObtained, 'totalPossible' => $totalPossible,
            'overallPct' => $overallPct, 'overallGrade' => $overallGrade,
            'rank' => $rank, 'rankOf' => $rankOf, 'attendance' => $attendance,
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
