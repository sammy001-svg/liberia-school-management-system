<?php
require_once ROOT_DIR . '/core/Controller.php';

class StudentController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Staff','Super Admin']);
        $search = $_GET['q'] ?? '';
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $where = "s.tenant_id=?";
        if ($search)  { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }

        $students = $this->db->fetchAll(
            "SELECT s.*, u.name, u.email, u.phone, u.gender, c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE $where ORDER BY u.name ASC", $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $this->view('school/highschool/students/index', ['pageTitle'=>'Students','panelType'=>'school','students'=>$students,'classes'=>$classes,'search'=>$search,'classId'=>$classId,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $roles   = $this->db->fetchAll("SELECT id,name FROM roles WHERE name='Student'");
        $this->view('school/highschool/students/form', ['pageTitle'=>'Add Student','panelType'=>'school','student'=>null,'classes'=>$classes,'roles'=>$roles,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $pw = password_hash($_POST['password'] ?? 'Student@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,status) VALUES (?,?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $_POST['name'], $_POST['email'], $_POST['phone']??'', $_POST['gender']??null, $_POST['dob']??null, 'active']
        );
        // Update password
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);
        $admNo = 'ADM-'.date('Y').'-'.str_pad($userId, 4, '0', STR_PAD_LEFT);
        $this->db->insert(
            "INSERT INTO students (tenant_id,user_id,admission_no,class_id,admission_date,status) VALUES (?,?,?,?,?,?)",
            [$this->tid, $userId, $admNo, $_POST['class_id']??null, $_POST['admission_date']??date('Y-m-d'), 'active']
        );
        $this->flash('success', 'Student admitted successfully. Admission No: '.$admNo);
        $this->redirect('/school/students');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.avatar,
                    c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN classes c ON s.class_id=c.id
             WHERE s.id=? AND s.tenant_id=?", [$id, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }
        $grades   = $this->db->fetchAll("SELECT g.*, c.name AS course_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY g.created_at DESC LIMIT 10",[$id,$this->tid]);
        $attendance = $this->db->fetchAll("SELECT * FROM attendance WHERE student_id=? AND tenant_id=? ORDER BY date DESC LIMIT 30",[$id,$this->tid]);
        $invoices = $this->db->fetchAll("SELECT * FROM invoices WHERE student_id=? AND tenant_id=? ORDER BY created_at DESC",[$id,$this->tid]);
        $this->view('school/highschool/students/show',['pageTitle'=>$student['name'],'panelType'=>'school','student'=>$student,'grades'=>$grades,'attendance'=>$attendance,'invoices'=>$invoices,'flash'=>$this->getFlash()]);
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $student = $this->db->fetchOne("SELECT s.*, u.name, u.email, u.phone, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?",[$this->tid]);
        $this->view('school/highschool/students/form',['pageTitle'=>'Edit Student','panelType'=>'school','student'=>$student,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if (!$student) { $this->redirect('/school/students'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,date_of_birth=? WHERE id=?",[$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$student['user_id']]);
        $this->db->execute("UPDATE students SET class_id=?,status=? WHERE id=? AND tenant_id=?",[$_POST['class_id']??null,$_POST['status']??'active',$id,$this->tid]);
        $this->flash('success','Student updated.');
        $this->redirect('/school/students/'.$id);
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin','Super Admin']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if ($student) {
            $this->db->execute("DELETE FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?",[$student['user_id']]);
        }
        $this->flash('success','Student removed.');
        $this->redirect('/school/students');
    }
}
