<?php
require_once ROOT_DIR . '/core/Controller.php';

class ClassController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $classes = $this->db->fetchAll("SELECT c.*, u.name AS teacher_name, (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id) AS student_count FROM classes c LEFT JOIN teachers t ON c.class_teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id WHERE c.tenant_id=? ORDER BY c.grade_level, c.section", [$this->tid]);
        $teachers = $this->db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=? ORDER BY u.name", [$this->tid]);
        $academicYears = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $this->view('school/highschool/classes/index', ['pageTitle'=>'Classes','panelType'=>'school','classes'=>$classes,'teachers'=>$teachers,'academicYears'=>$academicYears,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/classes');
    }

    public function store(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, [
            'name'        => 'required|max:80',
            'grade_level' => 'required|max:30',
            'capacity'    => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/classes'); }
        $this->db->insert(
            "INSERT INTO classes (tenant_id,academic_year_id,name,grade_level,section,capacity,class_teacher_id,room_number,description) VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid,$_POST['academic_year_id']?:null,$_POST['name'],$_POST['grade_level'],$_POST['section']??'',
                (int)($_POST['capacity']??40),$_POST['teacher_id']?:null,$_POST['room_number']??null,$_POST['description']??null,
            ]
        );
        $this->flash('success','Class created.'); $this->redirect('/school/classes');
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin']);
        $class    = $this->db->fetchOne("SELECT * FROM classes WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        $teachers = $this->db->fetchAll("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=?", [$this->tid]);
        $this->view('school/highschool/classes/form', ['pageTitle'=>'Edit Class','panelType'=>'school','class'=>$class,'teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['name' => 'required|max:80', 'grade_level' => 'required|max:30', 'capacity' => 'numeric']);
        if ($errors) { $this->failValidation($errors, '/school/classes/'.$id.'/edit'); }
        $this->db->execute(
            "UPDATE classes SET name=?,grade_level=?,section=?,capacity=?,class_teacher_id=?,room_number=?,description=? WHERE id=? AND tenant_id=?",
            [$_POST['name'],$_POST['grade_level'],$_POST['section']??'',(int)$_POST['capacity'],$_POST['teacher_id']?:null,$_POST['room_number']??null,$_POST['description']??null,$id,$this->tid]
        );
        $this->flash('success','Class updated.'); $this->redirect('/school/classes');
    }
}
