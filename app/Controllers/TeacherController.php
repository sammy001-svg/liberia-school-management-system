<?php
require_once ROOT_DIR . '/core/Controller.php';

class TeacherController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin']);
        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM teachers t WHERE t.tenant_id=?", [$this->tid])['c'];
        $p = $this->paginate($totalCount);
        $teachers = $this->db->fetchAll(
            "SELECT t.*, u.name, u.email, u.phone, u.gender, c.name AS class_name FROM teachers t JOIN users u ON t.user_id=u.id LEFT JOIN classes c ON t.class_id=c.id WHERE t.tenant_id=? ORDER BY u.name LIMIT {$p['perPage']} OFFSET {$p['offset']}",
            [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $this->view('school/highschool/teachers/index', [
            'pageTitle'=>'Teachers','panelType'=>'school','teachers'=>$teachers,'classes'=>$classes,'departments'=>$departments,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],
            'flash'=>$this->getFlash(),
        ]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/teachers');
    }

    public function store(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, [
            'name'  => 'required|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'max:30',
            'dob'   => 'date',
            'joined_at' => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/teachers'); }
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Teacher' LIMIT 1")['id'] ?? 5;
        $pw = password_hash($_POST['password'] ?: 'Teacher@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,address,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid,$roleId,$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'','active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);
        $empNo = 'EMP-'.date('Y').'-'.str_pad($userId,4,'0',STR_PAD_LEFT);
        $this->db->insert(
            "INSERT INTO teachers (tenant_id,user_id,employee_no,department_id,class_id,qualification,specialization,national_id,employment_type,joined_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid,$userId,$empNo,$_POST['department_id']?:null,$_POST['class_id']?:null,
                $_POST['qualification']??'',$_POST['specialization']??'',$_POST['national_id']??null,
                $_POST['employment_type']??'full_time',$_POST['joined_at']??date('Y-m-d'),
            ]
        );
        $this->flash('success','Teacher added. Employee No: '.$empNo);
        $this->redirect('/school/teachers');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $assignedCourses = $this->db->fetchAll(
            "SELECT c.* FROM teacher_courses tc JOIN courses c ON tc.course_id=c.id WHERE tc.teacher_id=? AND c.tenant_id=? ORDER BY c.name",
            [$id, $this->tid]
        );
        $availableCourses = $this->db->fetchAll(
            "SELECT * FROM courses WHERE tenant_id=? AND id NOT IN (SELECT course_id FROM teacher_courses WHERE teacher_id=?) ORDER BY name",
            [$this->tid, $id]
        );
        $this->view('school/highschool/teachers/show', [
            'pageTitle'=>$teacher['name'],'panelType'=>'school','teacher'=>$teacher,
            'assignedCourses'=>$assignedCourses,'availableCourses'=>$availableCourses,
            'flash'=>$this->getFlash(),
        ]);
    }

    public function assignCourse(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['course_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/teachers/'.$id); }
        $this->db->insert("INSERT INTO teacher_courses (teacher_id, course_id) VALUES (?, ?)", [$id, $_POST['course_id']]);
        $this->flash('success', 'Course assigned.');
        $this->redirect('/school/teachers/'.$id);
    }

    public function removeCourse(string $id, string $courseId): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM teacher_courses WHERE teacher_id=? AND course_id=?", [$id, $courseId]);
        $this->flash('success', 'Course unassigned.');
        $this->redirect('/school/teachers/'.$id);
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $this->view('school/highschool/teachers/form', ['pageTitle'=>'Edit Teacher','panelType'=>'school','teacher'=>$teacher,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin']);
        $teacher = $this->db->fetchOne("SELECT user_id FROM teachers WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'email' => 'required|email|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/teachers/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=? WHERE id=?", [$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$teacher['user_id']]);
        $this->db->execute("UPDATE teachers SET class_id=?,qualification=?,specialization=? WHERE id=? AND tenant_id=?", [$_POST['class_id']?:null,$_POST['qualification']??'',$_POST['specialization']??'',$id,$this->tid]);
        $this->flash('success','Teacher updated.'); $this->redirect('/school/teachers');
    }
}
