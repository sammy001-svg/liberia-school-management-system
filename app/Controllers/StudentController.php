<?php
require_once ROOT_DIR . '/core/Controller.php';

class StudentController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Staff']);
        $search = $_GET['q'] ?? '';
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $where = "s.tenant_id=?";
        if ($search)  { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM students s JOIN users u ON s.user_id=u.id WHERE $where", $params)['c'];
        $p = $this->paginate($totalCount);

        $students = $this->db->fetchAll(
            "SELECT s.*, u.name, u.email, u.phone, u.gender, c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE $where ORDER BY u.name ASC LIMIT {$p['perPage']} OFFSET {$p['offset']}", $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $this->view('school/highschool/students/index', [
            'pageTitle'=>'Students','panelType'=>'school','students'=>$students,'classes'=>$classes,'search'=>$search,'classId'=>$classId,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],
            'flash'=>$this->getFlash(), 'importErrors'=>$this->getImportErrors(),
        ]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/students');
    }

    public function bulkTemplate(): void {
        $this->requireAuth(['School Admin']);
        $this->downloadCsvTemplate('students_template.csv',
            ['name','email','phone','gender','dob','class_name','admission_date','guardian_name','guardian_phone','guardian_relationship','blood_group'],
            ['Jane Doe','jane.doe@example.com','0771234567','female','2010-05-14','Grade 7A','2026-01-15','John Doe','0779876543','Father','O+']
        );
    }

    public function bulkUpload(): void {
        $this->requireAuth(['School Admin']);
        $rows = $this->parseCsvUpload('csv_file');
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $classByName = [];
        foreach ($classes as $c) { $classByName[strtolower($c['name'])] = $c['id']; }

        $success = 0;
        $rowErrors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2; // +1 for header row, +1 for 1-indexing
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
                    if ($classId === null) {
                        $rowErrors[] = "Row {$line}: class '{$row['class_name']}' not found — student added without a class.";
                    }
                }
                $userId = $this->db->insert(
                    "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,status) VALUES (?,?,?,?,?,?,?,?)",
                    [$this->tid, $roleId, $name, $email, $row['phone'] ?? '', $row['gender'] ?: null, $row['dob'] ?: null, 'active']
                );
                $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash('Student@123', PASSWORD_BCRYPT), $userId]);
                $admNo = 'ADM-'.date('Y').'-'.str_pad($userId, 4, '0', STR_PAD_LEFT);
                $this->db->insert(
                    "INSERT INTO students (tenant_id,user_id,admission_no,class_id,admission_date,status,blood_group,guardian_name,guardian_phone,guardian_relationship)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    [
                        $this->tid, $userId, $admNo, $classId, $row['admission_date'] ?: date('Y-m-d'), 'active',
                        $row['blood_group'] ?: null, $row['guardian_name'] ?: null, $row['guardian_phone'] ?: null, $row['guardian_relationship'] ?: null,
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                $reason = str_contains($e->getMessage(), 'Duplicate entry') ? 'that email is already registered.' : 'could not be imported.';
                $rowErrors[] = "Row {$line}: {$reason}";
            }
        }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/students');
    }

    public function store(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, [
            'name'  => 'required|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'max:30',
            'dob'   => 'date',
            'admission_date' => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/students'); }
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $pw = password_hash($_POST['password'] ?? 'Student@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,address,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $_POST['name'], $_POST['email'], $_POST['phone']??'', $_POST['gender']??null, $_POST['dob']??null, $_POST['address']??'', 'active']
        );
        // Update password
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);
        $admNo = 'ADM-'.date('Y').'-'.str_pad($userId, 4, '0', STR_PAD_LEFT);
        $this->db->insert(
            "INSERT INTO students (tenant_id,user_id,admission_no,class_id,admission_date,status,blood_group,previous_school,guardian_name,guardian_phone,guardian_relationship,emergency_contact_phone)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $userId, $admNo, $_POST['class_id']?:null, $_POST['admission_date']??date('Y-m-d'), 'active',
                $_POST['blood_group']?:null, $_POST['previous_school']??null,
                $_POST['guardian_name']??null, $_POST['guardian_phone']??null, $_POST['guardian_relationship']??null,
                $_POST['emergency_contact_phone']??null,
            ]
        );
        $this->flash('success', 'Student admitted successfully. Admission No: '.$admNo);
        $this->redirect('/school/students');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
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

    public function idCard(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth
             FROM students s JOIN users u ON s.user_id=u.id
             WHERE s.id=? AND s.tenant_id=?", [$id, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }
        $class = $student['class_id'] ? $this->db->fetchOne("SELECT name FROM classes WHERE id=?", [$student['class_id']]) : null;
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $currentYear = $this->db->fetchOne("SELECT * FROM academic_years WHERE tenant_id=? AND is_current=1 LIMIT 1", [$this->tid]);
        $validThru = $currentYear ? date('M Y', strtotime($currentYear['end_date'])) : date('M Y', strtotime('+1 year'));

        $this->view('school/id_card', [
            'pageTitle' => 'ID Card', 'tenant' => $tenant,
            'roleLabel' => 'Student',
            'personName' => $student['name'],
            'idLabel' => 'Admission No', 'idValue' => $student['admission_no'],
            'fields' => [
                'Class'     => $class['name'] ?? 'Not Assigned',
                'Gender'    => $student['gender'] ? ucfirst($student['gender']) : null,
                'DOB'       => $student['date_of_birth'] ? date('d M Y', strtotime($student['date_of_birth'])) : null,
                'Blood Grp' => $student['blood_group'] ?? null,
            ],
            'validThru' => $validThru,
            'backNote' => "This card is the property of " . ($tenant['name'] ?? 'the school') . " and must be carried at all times on school premises.\n\nGuardian Contact: " . ($student['guardian_phone'] ?: 'N/A'),
        ]);
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin']);
        $student = $this->db->fetchOne("SELECT s.*, u.name, u.email, u.phone, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?",[$this->tid]);
        $this->view('school/highschool/students/form',['pageTitle'=>'Edit Student','panelType'=>'school','student'=>$student,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if (!$student) { $this->redirect('/school/students'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'email' => 'required|email|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/students/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,date_of_birth=? WHERE id=?",[$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$student['user_id']]);
        $this->db->execute("UPDATE students SET class_id=?,status=? WHERE id=? AND tenant_id=?",[$_POST['class_id']?:null,$_POST['status']??'active',$id,$this->tid]);
        $this->flash('success','Student updated.');
        $this->redirect('/school/students/'.$id);
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if ($student) {
            $this->db->execute("DELETE FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?",[$student['user_id']]);
        }
        $this->flash('success','Student removed.');
        $this->redirect('/school/students');
    }
}
