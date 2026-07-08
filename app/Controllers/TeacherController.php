<?php
require_once ROOT_DIR . '/core/Controller.php';

class TeacherController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $teachers = $this->db->fetchAll("SELECT t.*, u.name, u.email, u.phone, u.gender, c.name AS class_name FROM teachers t JOIN users u ON t.user_id=u.id LEFT JOIN classes c ON t.class_id=c.id WHERE t.tenant_id=? ORDER BY u.name", [$this->tid]);
        $this->view('school/highschool/teachers/index', ['pageTitle'=>'Teachers','panelType'=>'school','teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $this->view('school/highschool/teachers/form', ['pageTitle'=>'Add Teacher','panelType'=>'school','teacher'=>null,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Teacher' LIMIT 1")['id'] ?? 5;
        $pw = password_hash($_POST['password'] ?? 'Teacher@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert("INSERT INTO users (tenant_id,role_id,name,email,phone,gender,status) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$roleId,$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,'active']);
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);
        $empNo = 'EMP-'.date('Y').'-'.str_pad($userId,4,'0',STR_PAD_LEFT);
        $this->db->insert("INSERT INTO teachers (tenant_id,user_id,employee_no,class_id,qualification,specialization,joined_at) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$userId,$empNo,$_POST['class_id']??null,$_POST['qualification']??'',$_POST['specialization']??'',$_POST['joined_at']??date('Y-m-d')]);
        $this->flash('success','Teacher added. Employee No: '.$empNo);
        $this->redirect('/school/teachers');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $this->view('school/highschool/teachers/show', ['pageTitle'=>$teacher['name'],'panelType'=>'school','teacher'=>$teacher,'flash'=>$this->getFlash()]);
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $this->view('school/highschool/teachers/form', ['pageTitle'=>'Edit Teacher','panelType'=>'school','teacher'=>$teacher,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $teacher = $this->db->fetchOne("SELECT user_id FROM teachers WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=? WHERE id=?", [$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$teacher['user_id']]);
        $this->db->execute("UPDATE teachers SET class_id=?,qualification=?,specialization=? WHERE id=? AND tenant_id=?", [$_POST['class_id']??null,$_POST['qualification']??'',$_POST['specialization']??'',$id,$this->tid]);
        $this->flash('success','Teacher updated.'); $this->redirect('/school/teachers');
    }
}
