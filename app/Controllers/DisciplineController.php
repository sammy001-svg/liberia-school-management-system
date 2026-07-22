<?php
require_once ROOT_DIR . '/core/Controller.php';

class DisciplineController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['discipline.manage']);
        $classId  = $_GET['class_id'] ?? '';
        $severity = $_GET['severity'] ?? '';
        $search   = trim($_GET['q'] ?? '');

        $params = [$this->tid];
        $where = "d.tenant_id=?";
        if ($classId)  { $where .= " AND s.class_id=?"; $params[] = $classId; }
        if ($severity) { $where .= " AND d.severity=?"; $params[] = $severity; }
        if ($search)   { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $records = $this->db->fetchAll(
            "SELECT d.*, u.name AS student_name, s.admission_no, c.name AS class_name, ru.name AS reported_by_name
             FROM disciplinary_records d
             JOIN students s ON d.student_id=s.id
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             LEFT JOIN users ru ON d.reported_by=ru.id
             WHERE $where ORDER BY d.incident_date DESC, d.id DESC", $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $students = $this->db->fetchAll("SELECT s.id, u.name, s.admission_no FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]);
        $stats = [
            'total'   => count($records),
            'severe'  => count(array_filter($records, fn($r) => $r['severity'] === 'severe')),
            'moderate' => count(array_filter($records, fn($r) => $r['severity'] === 'moderate')),
            'minor'   => count(array_filter($records, fn($r) => $r['severity'] === 'minor')),
            'commendation' => count(array_filter($records, fn($r) => $r['severity'] === 'commendation')),
        ];

        $this->view('school/highschool/discipline/index', [
            'pageTitle' => 'Discipline', 'panelType' => 'school',
            'records' => $records, 'classes' => $classes, 'students' => $students, 'stats' => $stats,
            'classId' => $classId, 'severity' => $severity, 'search' => $search,
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requirePermission(['discipline.manage']);
        $backTo = $_POST['back_to'] ?: '/school/discipline';
        $errors = $this->validate($_POST, [
            'student_id'    => 'required',
            'incident_date' => 'required|date',
            'category'      => 'required|max:100',
            'severity'      => 'required',
        ]);
        if (!$errors) {
            $student = $this->db->fetchOne("SELECT id FROM students WHERE id=? AND tenant_id=?", [$_POST['student_id'], $this->tid]);
            if (!$student) { $errors['student_id'] = 'Select a valid student.'; }
        }
        if ($errors) { $this->failValidation($errors, $backTo); }

        $this->db->insert(
            "INSERT INTO disciplinary_records (tenant_id,student_id,incident_date,category,severity,description,action_taken,reported_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['student_id'], $_POST['incident_date'], $_POST['category'], $_POST['severity'],
                $_POST['description'] ?: null, $_POST['action_taken'] ?: null, $_SESSION['user_id'],
            ]
        );
        $this->flash('success', 'Disciplinary record added.');
        $this->redirect($backTo);
    }

    public function delete(string $id): void {
        $this->requirePermission(['discipline.manage']);
        $record = $this->db->fetchOne("SELECT student_id FROM disciplinary_records WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($record) {
            $this->db->execute("DELETE FROM disciplinary_records WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        }
        $this->flash('success', 'Disciplinary record removed.');
        $this->redirect(!empty($_POST['back_to']) ? $_POST['back_to'] : '/school/discipline');
    }
}
