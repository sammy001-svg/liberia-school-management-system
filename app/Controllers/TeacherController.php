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
            'flash'=>$this->getFlash(), 'importErrors'=>$this->getImportErrors(),
        ]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/teachers');
    }

    public function bulkTemplate(): void {
        $this->requireAuth(['School Admin']);
        $this->downloadCsvTemplate('teachers_template.csv',
            ['name','email','phone','gender','dob','qualification','specialization','department_name','class_name','employment_type','joined_at'],
            ['John Smith','john.smith@example.com','0771234567','male','1985-03-20','B.Ed','Mathematics','Sciences','Grade 7A','full_time','2026-01-15']
        );
    }

    public function bulkUpload(): void {
        $this->requireAuth(['School Admin']);
        $rows = $this->parseCsvUpload('csv_file');
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Teacher' LIMIT 1")['id'] ?? 5;
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $classByName = [];
        foreach ($classes as $c) { $classByName[strtolower($c['name'])] = $c['id']; }
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE tenant_id=?", [$this->tid]);
        $deptByName = [];
        foreach ($departments as $d) { $deptByName[strtolower($d['name'])] = $d['id']; }

        $success = 0;
        $rowErrors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            try {
                $name = $row['name'] ?? '';
                $email = $row['email'] ?? '';
                if ($name === '' || $email === '') {
                    $rowErrors[] = "Row {$line}: name and email are required.";
                    continue;
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Row {$line}: '{$email}' is not a valid email address.";
                    continue;
                }
                $classId = null;
                if (!empty($row['class_name'])) {
                    $classId = $classByName[strtolower($row['class_name'])] ?? null;
                    if ($classId === null) { $rowErrors[] = "Row {$line}: class '{$row['class_name']}' not found — teacher added without a class."; }
                }
                $deptId = null;
                if (!empty($row['department_name'])) {
                    $deptId = $deptByName[strtolower($row['department_name'])] ?? null;
                    if ($deptId === null) { $rowErrors[] = "Row {$line}: department '{$row['department_name']}' not found — teacher added without a department."; }
                }
                $userId = $this->db->insert(
                    "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,status) VALUES (?,?,?,?,?,?,?,?)",
                    [$this->tid, $roleId, $name, $email, $row['phone'] ?? '', $row['gender'] ?: null, $row['dob'] ?: null, 'active']
                );
                $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash('Teacher@123', PASSWORD_BCRYPT), $userId]);
                $empNo = 'EMP-'.date('Y').'-'.str_pad($userId, 4, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO teachers (tenant_id,user_id,employee_no,department_id,class_id,qualification,specialization,employment_type,joined_at)
                     VALUES (?,?,?,?,?,?,?,?,?)",
                    [
                        $this->tid, $userId, $empNo, $deptId, $classId,
                        $row['qualification'] ?? '', $row['specialization'] ?? '',
                        in_array($row['employment_type'] ?? '', ['full_time','part_time','contract'], true) ? $row['employment_type'] : 'full_time',
                        $row['joined_at'] ?: date('Y-m-d'),
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                $reason = str_contains($e->getMessage(), 'Duplicate entry') ? 'that email is already registered.' : 'could not be imported.';
                $rowErrors[] = "Row {$line}: {$reason}";
            }
        }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/teachers');
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
