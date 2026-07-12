<?php
require_once ROOT_DIR . '/core/Controller.php';

class ClassController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['classes.view','classes.manage']);
        $classes = $this->db->fetchAll("SELECT c.*, u.name AS teacher_name, (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) AS student_count FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id WHERE c.tenant_id=? ORDER BY c.grade_level, c.section", [$this->tid]);
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

    public function store(): void {
        $this->requirePermission(['classes.manage']);
        $errors = $this->validate($_POST, [
            'name'        => 'required|max:80',
            'grade_level' => 'required|max:30',
            'capacity'    => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/classes'); }
        $classId = $this->db->insert(
            "INSERT INTO classes (tenant_id,academic_year_id,name,grade_level,section,capacity,room_number,description) VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid,$_POST['academic_year_id']?:null,$_POST['name'],$_POST['grade_level'],$_POST['section']??'',
                (int)($_POST['capacity']??40),$_POST['room_number']??null,$_POST['description']??null,
            ]
        );
        if (!empty($_POST['teacher_id'])) {
            $this->assignHomeroom($this->tid, (int)$_POST['teacher_id'], (int)$classId);
        }
        $this->flash('success','Class created.'); $this->redirect('/school/classes');
    }

    public function edit(string $id): void {
        $this->requirePermission(['classes.manage']);
        $class    = $this->db->fetchOne("SELECT * FROM classes WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        $teachers = $this->db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=?", [$this->tid]);
        $this->view('school/highschool/classes/form', ['pageTitle'=>'Edit Class','panelType'=>'school','class'=>$class,'teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requirePermission(['classes.manage']);
        $errors = $this->validate($_POST, ['name' => 'required|max:80', 'grade_level' => 'required|max:30', 'capacity' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/classes/'.$id.'/edit'); }
        $this->db->execute(
            "UPDATE classes SET name=?,grade_level=?,section=?,capacity=?,room_number=?,description=? WHERE id=? AND tenant_id=?",
            [$_POST['name'],$_POST['grade_level'],$_POST['section']??'',(int)$_POST['capacity'],$_POST['room_number']??null,$_POST['description']??null,$id,$this->tid]
        );
        if (!empty($_POST['teacher_id'])) {
            $this->assignHomeroom($this->tid, (int)$_POST['teacher_id'], (int)$id);
        } else {
            $this->db->execute("UPDATE classes SET class_teacher_id=NULL WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("UPDATE teachers SET class_id=NULL WHERE class_id=? AND tenant_id=?", [$id, $this->tid]);
        }
        $this->flash('success','Class updated.'); $this->redirect('/school/classes');
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
