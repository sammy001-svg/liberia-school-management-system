<?php
require_once ROOT_DIR . '/core/Controller.php';

class ClassController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['classes.view','classes.manage']);
        $classes = $this->db->fetchAll(
            "SELECT c.*, u.name AS teacher_name,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) AS student_count,
                    (SELECT COUNT(*) FROM course_classes cc WHERE cc.class_id=c.id) AS subject_count
             FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id
             WHERE c.tenant_id=? ORDER BY c.grade_level, c.section", [$this->tid]
        );
        $teachers = $this->db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=? ORDER BY u.name", [$this->tid]);
        $academicYears = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total, COALESCE(SUM(capacity),0) totalCapacity,
                    (SELECT COUNT(*) FROM students s WHERE s.tenant_id=? AND s.class_id IN (SELECT id FROM classes WHERE tenant_id=?)) enrolled,
                    SUM(CASE WHEN class_teacher_id IS NULL THEN 1 ELSE 0 END) unassigned
             FROM classes WHERE tenant_id=?", [$this->tid, $this->tid, $this->tid]
        );
        $this->view('school/highschool/classes/index', ['pageTitle'=>'Classes','panelType'=>'school','classes'=>$classes,'teachers'=>$teachers,'academicYears'=>$academicYears,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requirePermission(['classes.manage']);
        $this->redirect('/school/classes');
    }

    private function ensureClassColumns(): void {
        try {
            $cols = $this->db->fetchAll("SHOW COLUMNS FROM classes");
            $existing = array_column($cols, 'Field');
            if (!in_array('room_number', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN room_number VARCHAR(50) DEFAULT NULL");
            }
            if (!in_array('description', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN description TEXT DEFAULT NULL");
            }
            if (!in_array('academic_year_id', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN academic_year_id INT UNSIGNED DEFAULT NULL");
            }
            if (!in_array('class_teacher_id', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN class_teacher_id INT UNSIGNED DEFAULT NULL");
            }
            if (!in_array('section', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN section VARCHAR(20) DEFAULT NULL");
            }
            if (!in_array('capacity', $existing, true)) {
                $this->db->execute("ALTER TABLE classes ADD COLUMN capacity INT DEFAULT 40");
            }
        } catch (\Throwable $e) {
            // Ignore if schema check fails
        }
    }

    public function store(): void {
        $this->requirePermission(['classes.manage']);
        $this->ensureClassColumns();
        $errors = $this->validate($_POST, [
            'name'        => 'required|max:80',
            'grade_level' => 'required|max:30',
            'capacity'    => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/classes'); }

        $academicYearId = !empty($_POST['academic_year_id']) ? (int)$_POST['academic_year_id'] : null;
        if ($academicYearId !== null) {
            $yearExists = $this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$academicYearId, $this->tid]);
            if (!$yearExists) { $academicYearId = null; }
        }

        $classId = $this->db->insert(
            "INSERT INTO classes (tenant_id,academic_year_id,name,grade_level,section,capacity,room_number,description) VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $academicYearId, trim($_POST['name']), trim($_POST['grade_level']), trim($_POST['section'] ?? ''),
                (int)($_POST['capacity'] ?? 40), trim($_POST['room_number'] ?? '') ?: null, trim($_POST['description'] ?? '') ?: null,
            ]
        );
        if (!empty($_POST['teacher_id'])) {
            $teacherId = (int)$_POST['teacher_id'];
            $teacherExists = $this->db->fetchOne("SELECT id FROM teachers WHERE id=? AND tenant_id=?", [$teacherId, $this->tid]);
            if ($teacherExists) {
                $this->assignHomeroom($this->tid, $teacherId, (int)$classId);
            }
        }
        $this->flash('success','Class created successfully.'); $this->redirect('/school/classes');
    }

    public function edit(string $id): void {
        $this->requirePermission(['classes.manage']);
        $class    = $this->db->fetchOne("SELECT * FROM classes WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        $teachers = $this->db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=?", [$this->tid]);
        $this->view('school/highschool/classes/form', ['pageTitle'=>'Edit Class','panelType'=>'school','class'=>$class,'teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requirePermission(['classes.manage']);
        $this->ensureClassColumns();
        $errors = $this->validate($_POST, ['name' => 'required|max:80', 'grade_level' => 'required|max:30', 'capacity' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/classes/'.$id.'/edit'); }
        $this->db->execute(
            "UPDATE classes SET name=?,grade_level=?,section=?,capacity=?,room_number=?,description=? WHERE id=? AND tenant_id=?",
            [trim($_POST['name']),trim($_POST['grade_level']),trim($_POST['section']??''),(int)$_POST['capacity'],trim($_POST['room_number']??'')?:null,trim($_POST['description']??'')?:null,$id,$this->tid]
        );
        if (!empty($_POST['teacher_id'])) {
            $teacherId = (int)$_POST['teacher_id'];
            $teacherExists = $this->db->fetchOne("SELECT id FROM teachers WHERE id=? AND tenant_id=?", [$teacherId, $this->tid]);
            if ($teacherExists) {
                $this->assignHomeroom($this->tid, $teacherId, (int)$id);
            }
        } else {
            $this->db->execute("UPDATE classes SET class_teacher_id=NULL WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("UPDATE teachers SET class_id=NULL WHERE class_id=? AND tenant_id=?", [$id, $this->tid]);
        }
        $this->flash('success','Class updated.'); $this->redirect('/school/classes');
    }

    // Blocked while students are still assigned (protects live enrollment data).
    // On delete, FKs cascade the class-link/homework/online-class/online-exam rows
    // and SET NULL students/timetable; the remaining class_id columns have no FK
    // (teachers, attendance, exams, fee_structures, announcements,
    // learning_materials) so they're cleared manually to avoid dangling pointers.
    public function delete(string $id): void {
        $this->requirePermission(['classes.manage']);
        $class = $this->db->fetchOne("SELECT id, name FROM classes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$class) { $this->redirect('/school/classes'); }
        $students = $this->db->fetchOne("SELECT COUNT(*) c FROM students WHERE class_id=? AND tenant_id=?", [$id, $this->tid])['c'] ?? 0;
        if ($students > 0) {
            $this->flash('danger', "Cannot delete {$class['name']} — {$students} student(s) are still assigned to it. Move them to another class first.");
            $this->redirect('/school/classes');
        }
        foreach (['teachers','attendance','exams','fee_structures','announcements','learning_materials'] as $table) {
            $this->db->execute("UPDATE {$table} SET class_id=NULL WHERE class_id=? AND tenant_id=?", [$id, $this->tid]);
        }
        $this->db->execute("DELETE FROM classes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', "Class {$class['name']} deleted.");
        $this->redirect('/school/classes');
    }

    public function show(string $id): void {
        $this->requirePermission(['classes.view','classes.manage']);
        $class = $this->db->fetchOne(
            "SELECT c.*, u.name AS teacher_name, ay.name AS academic_year_name
             FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id
             LEFT JOIN academic_years ay ON c.academic_year_id=ay.id
             WHERE c.id=? AND c.tenant_id=?", [$id, $this->tid]
        );
        if (!$class) { $this->redirect('/school/classes'); }
        $roster = $this->db->fetchAll(
            "SELECT s.id, s.admission_no, s.status, u.name, u.gender
             FROM students s JOIN users u ON s.user_id=u.id
             WHERE s.class_id=? AND s.tenant_id=? ORDER BY u.name", [$id, $this->tid]
        );
        $gradeStats = $this->db->fetchOne(
            "SELECT AVG(g.marks_obtained/g.total_marks*100) avg_pct
             FROM grades g JOIN students s ON g.student_id=s.id
             WHERE s.class_id=? AND g.tenant_id=? AND g.total_marks>0", [$id, $this->tid]
        );
        $avgGrade = $gradeStats['avg_pct'] !== null ? round($gradeStats['avg_pct']) : null;
        $courses = $this->db->fetchAll(
            "SELECT c.id,c.name,c.code FROM courses c JOIN course_classes cc ON cc.course_id=c.id
             WHERE cc.class_id=? AND c.tenant_id=? ORDER BY c.name", [$id, $this->tid]
        );

        $this->view('school/highschool/classes/show', [
            'pageTitle'=>$class['name'],'panelType'=>'school','class'=>$class,'roster'=>$roster,
            'avgGrade'=>$avgGrade,'courses'=>$courses,'flash'=>$this->getFlash(),
        ]);
    }
}
