<?php
require_once ROOT_DIR . '/core/Controller.php';

class TeacherController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['teachers.manage']);
        $search = $_GET['q'] ?? '';
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $where = "t.tenant_id=?";
        if ($search) { $where .= " AND (u.name LIKE ? OR t.employee_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($classId) {
            // "Classes taught" = homeroom class OR any class whose subjects this teacher is assigned to
            // (via the teacher_courses multi-assignment, since a subject already belongs to one class).
            $where .= " AND (t.class_id=? OR EXISTS (SELECT 1 FROM teacher_courses tc JOIN courses co ON tc.course_id=co.id WHERE tc.teacher_id=t.id AND co.class_id=?))";
            $params[] = $classId; $params[] = $classId;
        }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM teachers t JOIN users u ON t.user_id=u.id WHERE $where", $params)['c'];
        $p = $this->paginate($totalCount);
        $teachers = $this->db->fetchAll(
            "SELECT t.*, u.name, u.email, u.phone, u.gender, c.name AS class_name, d.name AS department_name
             FROM teachers t JOIN users u ON t.user_id=u.id LEFT JOIN classes c ON t.class_id=c.id LEFT JOIN departments d ON t.department_id=d.id
             WHERE $where ORDER BY u.name LIMIT {$p['perPage']} OFFSET {$p['offset']}",
            $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $departments = $this->db->fetchAll("SELECT id,name FROM departments WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN t.employment_type='full_time' THEN 1 ELSE 0 END) full_time,
                    SUM(CASE WHEN t.employment_type='part_time' THEN 1 ELSE 0 END) part_time,
                    SUM(CASE WHEN t.employment_type='contract' THEN 1 ELSE 0 END) contract,
                    COUNT(DISTINCT t.department_id) departments
             FROM teachers t WHERE t.tenant_id=?", [$this->tid]
        );
        $this->view('school/highschool/teachers/index', [
            'pageTitle'=>'Teachers','panelType'=>'school','teachers'=>$teachers,'classes'=>$classes,'departments'=>$departments,
            'search'=>$search,'classId'=>$classId,'stats'=>$stats,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],
            'flash'=>$this->getFlash(), 'importErrors'=>$this->getImportErrors(),
        ]);
    }

    public function create(): void {
        $this->requirePermission(['teachers.manage']);
        $this->redirect('/school/teachers');
    }

    public function bulkTemplate(): void {
        $this->requirePermission(['teachers.manage']);
        $this->downloadCsvTemplate('teachers_template.csv',
            ['name','email','phone','gender','dob','qualification','specialization','department_name','class_name','employment_type','joined_at'],
            ['John Smith','john.smith@example.com','0771234567','male','1985-03-20','B.Ed','Mathematics','Sciences','Grade 7A','full_time','2026-01-15']
        );
    }

    public function bulkUpload(): void {
        $this->requirePermission(['teachers.manage']);
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
        $this->requirePermission(['teachers.manage']);
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
        $teacherId = $this->db->insert(
            "INSERT INTO teachers (tenant_id,user_id,employee_no,department_id,qualification,specialization,national_id,employment_type,joined_at)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid,$userId,$empNo,$_POST['department_id']?:null,
                $_POST['qualification']??'',$_POST['specialization']??'',$_POST['national_id']??null,
                $_POST['employment_type']??'full_time',$_POST['joined_at']??date('Y-m-d'),
            ]
        );
        $this->assignHomeroom($this->tid, (int)$teacherId, $_POST['class_id'] ?: null);
        $this->flash('success','Teacher added. Employee No: '.$empNo);
        $this->redirect('/school/teachers');
    }

    public function show(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, c.name AS class_name, d.name AS department_name FROM teachers t JOIN users u ON t.user_id=u.id LEFT JOIN classes c ON t.class_id=c.id LEFT JOIN departments d ON t.department_id=d.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $assignedCourses = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT GROUP_CONCAT(cl.name ORDER BY cl.name SEPARATOR ', ') FROM course_classes cc JOIN classes cl ON cc.class_id=cl.id WHERE cc.course_id=c.id) AS class_names
             FROM teacher_courses tc JOIN courses c ON tc.course_id=c.id
             WHERE tc.teacher_id=? AND c.tenant_id=? ORDER BY c.name",
            [$id, $this->tid]
        );
        $availableCourses = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT GROUP_CONCAT(cl.name ORDER BY cl.name SEPARATOR ', ') FROM course_classes cc JOIN classes cl ON cc.class_id=cl.id WHERE cc.course_id=c.id) AS class_names
             FROM courses c
             WHERE c.tenant_id=? AND c.id NOT IN (SELECT course_id FROM teacher_courses WHERE teacher_id=?) ORDER BY c.name",
            [$this->tid, $id]
        );
        $homeroomCount = $teacher['class_id']
            ? ($this->db->fetchOne("SELECT COUNT(*) c FROM students WHERE class_id=? AND tenant_id=?", [$teacher['class_id'], $this->tid])['c'] ?? 0)
            : 0;
        $yearsOfService = $teacher['joined_at'] ? floor((time() - strtotime($teacher['joined_at'])) / 31536000) : null;
        $otherTeachers = $this->db->fetchAll(
            "SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=? AND t.id!=? ORDER BY u.name",
            [$this->tid, $id]
        );

        $this->view('school/highschool/teachers/show', [
            'pageTitle'=>$teacher['name'],'panelType'=>'school','teacher'=>$teacher,
            'assignedCourses'=>$assignedCourses,'availableCourses'=>$availableCourses,
            'otherTeachers'=>$otherTeachers,
            'homeroomCount'=>$homeroomCount,'yearsOfService'=>$yearsOfService,
            'flash'=>$this->getFlash(),
        ]);
    }

    public function idCard(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.gender, u.avatar FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id, $this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $department = $teacher['department_id'] ? $this->db->fetchOne("SELECT name FROM departments WHERE id=?", [$teacher['department_id']]) : null;
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $currentYear = $this->db->fetchOne("SELECT * FROM academic_years WHERE tenant_id=? AND is_current=1 LIMIT 1", [$this->tid]);
        $validThru = $currentYear ? date('M Y', strtotime($currentYear['end_date'])) : date('M Y', strtotime('+1 year'));

        $this->view('school/id_card', [
            'pageTitle' => 'ID Card', 'tenant' => $tenant,
            'roleLabel' => 'Staff',
            'personName' => $teacher['name'],
            'idLabel' => 'Employee No', 'idValue' => $teacher['employee_no'],
            'photoUrl' => $teacher['avatar'] ?? null,
            'fields' => [
                'Department' => $department['name'] ?? null,
                'Subject'    => $teacher['specialization'] ?: null,
                'Gender'     => $teacher['gender'] ? ucfirst($teacher['gender']) : null,
                'Qualif.'    => $teacher['qualification'] ?: null,
            ],
            'validThru' => $validThru,
            'backNote' => "This card is the property of " . ($tenant['name'] ?? 'the school') . " and must be carried at all times on school premises.\n\nIf found, please contact the school office.",
        ]);
    }

    public function assignCourse(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $courseIds = array_filter(array_map('intval', $_POST['course_ids'] ?? []));
        if (empty($courseIds)) {
            $this->failValidation(['course_ids' => 'Select at least one class/subject to assign.'], '/school/teachers/'.$id);
        }
        $assigned = 0;
        foreach (array_unique($courseIds) as $courseId) {
            $course = $this->db->fetchOne("SELECT id FROM courses WHERE id=? AND tenant_id=?", [$courseId, $this->tid]);
            if (!$course) { continue; }
            $this->db->execute("INSERT IGNORE INTO teacher_courses (teacher_id, course_id) VALUES (?, ?)", [$id, $courseId]);
            $assigned++;
        }
        $this->flash('success', $assigned === 1 ? '1 subject assigned.' : "{$assigned} subjects assigned.");
        $this->redirect('/school/teachers/'.$id);
    }

    public function removeCourse(string $id, string $courseId): void {
        $this->requirePermission(['teachers.manage']);
        $this->db->execute("DELETE FROM teacher_courses WHERE teacher_id=? AND course_id=?", [$id, $courseId]);
        $this->flash('success', 'Course unassigned.');
        $this->redirect('/school/teachers/'.$id);
    }

    public function reassignCourse(string $id, string $courseId): void {
        $this->requirePermission(['teachers.manage']);
        $newTeacherId = (int)($_POST['new_teacher_id'] ?? 0);
        if (!$newTeacherId) {
            $this->failValidation(['new_teacher_id' => 'Select a teacher to reassign to.'], '/school/teachers/'.$id);
        }
        $newTeacher = $this->db->fetchOne("SELECT id FROM teachers WHERE id=? AND tenant_id=?", [$newTeacherId, $this->tid]);
        $link = $this->db->fetchOne("SELECT 1 FROM teacher_courses WHERE teacher_id=? AND course_id=?", [$id, $courseId]);
        if (!$newTeacher || !$link) { $this->redirect('/school/teachers/'.$id); }
        $alreadyAssigned = $this->db->fetchOne("SELECT 1 FROM teacher_courses WHERE teacher_id=? AND course_id=?", [$newTeacherId, $courseId]);
        if ($alreadyAssigned) {
            $this->db->execute("DELETE FROM teacher_courses WHERE teacher_id=? AND course_id=?", [$id, $courseId]);
        } else {
            $this->db->execute("UPDATE teacher_courses SET teacher_id=? WHERE teacher_id=? AND course_id=?", [$newTeacherId, $id, $courseId]);
        }
        $this->flash('success', 'Subject reassigned.');
        $this->redirect('/school/teachers/'.$id);
    }

    public function edit(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT t.*, u.name, u.email, u.phone, u.gender FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.tenant_id=?", [$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $this->view('school/highschool/teachers/form', ['pageTitle'=>'Edit Teacher','panelType'=>'school','teacher'=>$teacher,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT user_id FROM teachers WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'email' => 'required|email|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/teachers/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=? WHERE id=?", [$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$teacher['user_id']]);
        $this->db->execute("UPDATE teachers SET qualification=?,specialization=? WHERE id=? AND tenant_id=?", [$_POST['qualification']??'',$_POST['specialization']??'',$id,$this->tid]);
        $this->assignHomeroom($this->tid, (int)$id, $_POST['class_id'] ?: null);
        $this->flash('success','Teacher updated.'); $this->redirect('/school/teachers');
    }

    public function resetPassword(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT user_id FROM teachers WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$teacher) { $this->redirect('/school/teachers'); }
        $password = $this->generateStrongPassword();
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($password, PASSWORD_BCRYPT), $teacher['user_id']]);
        $this->flash('success', "New password: {$password} (write this down, it will not be shown again).");
        $this->redirect('/school/teachers/'.$id);
    }

    // Safe to delete outright: teacher_courses/learning_materials rows cascade away,
    // timetable/homework/online class/online exam rows fall back to SET NULL (they
    // survive, just lose the teacher attribution); classes.class_teacher_id has no FK
    // so it's cleared manually to avoid a dangling homeroom reference.
    public function delete(string $id): void {
        $this->requirePermission(['teachers.manage']);
        $teacher = $this->db->fetchOne("SELECT user_id FROM teachers WHERE id=? AND tenant_id=?", [$id,$this->tid]);
        if ($teacher) {
            $this->db->execute("UPDATE classes SET class_teacher_id=NULL WHERE class_teacher_id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM teachers WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?", [$teacher['user_id']]);
        }
        $this->flash('success', 'Teacher removed.');
        $this->redirect('/school/teachers');
    }
}
