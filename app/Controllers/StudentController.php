<?php
require_once ROOT_DIR . '/core/Controller.php';

class StudentController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        $this->requirePermission(['students.view','students.edit','students.manage']);
        $search = $_GET['q'] ?? '';
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $where = "s.tenant_id=?";
        if ($search)  { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM students s JOIN users u ON s.user_id=u.id WHERE $where", $params)['c'];
        $p = $this->paginate($totalCount);

        $students = $this->db->fetchAll(
            "SELECT s.*, u.name, u.email, u.phone, u.gender, u.avatar, c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE $where ORDER BY u.name ASC LIMIT {$p['perPage']} OFFSET {$p['offset']}", $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN s.status='active' THEN 1 ELSE 0 END) active,
                    SUM(CASE WHEN u.gender='male' THEN 1 ELSE 0 END) male,
                    SUM(CASE WHEN u.gender='female' THEN 1 ELSE 0 END) female
             FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=?", [$this->tid]
        );
        $this->view('school/highschool/students/index', [
            'pageTitle'=>'Students','panelType'=>'school','students'=>$students,'classes'=>$classes,'search'=>$search,'classId'=>$classId,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],'stats'=>$stats,
            'flash'=>$this->getFlash(), 'importErrors'=>$this->getImportErrors(),
            'bulkCredentialsUrl' => !empty($_SESSION['bulk_credentials']) ? '/school/students/bulk-credentials' : null,
        ]);
    }

    public function create(): void {
        $this->requirePermission(['students.manage']);
        $this->redirect('/school/students');
    }

    public function bulkTemplate(): void {
        $this->requirePermission(['students.manage']);
        $this->downloadCsvTemplate('students_template.csv',
            ['TSM ID','First Name','Middle Name','Last Name','Class','Gender','Date of Birth','Residential Address','County','Country','Religion','Contact Number 1','Contact Number 2','Email','Previous School Name','Previous School Address','Previous Class','Admission Date','Transfer Date','Reason for Leaving','Student Type'],
            ['CAS0001','Jane','K.','Doe','Grade 7A','Female','14/05/2010','Ben Town','Margibi County','Liberia','Christianity','0771234567','0779876543','jane.doe@example.com','','','','15/01/2026','','','New Student']
        );
    }

    /** Parses a date in DD/MM/YYYY (as exported by TSM-style systems) or falls back to any format PHP recognizes. */
    private function parseFlexibleDate(?string $value): ?string {
        $value = trim((string)$value);
        if ($value === '') { return null; }
        $d = \DateTime::createFromFormat('d/m/Y', $value);
        if ($d && $d->format('d/m/Y') === $value) { return $d->format('Y-m-d'); }
        $ts = strtotime($value);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    public function bulkUpload(): void {
        $this->requirePermission(['students.manage']);
        $rows = $this->parseCsvUpload('csv_file');
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $classByName = [];
        foreach ($classes as $c) { $classByName[strtolower($c['name'])] = $c['id']; }

        $success = 0;
        $rowErrors = [];
        $generatedPins = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2; // +1 for header row, +1 for 1-indexing
            try {
                $tsmId     = trim($row['tsm id'] ?? '');
                $firstName = trim($row['first name'] ?? '');
                $middleName = trim($row['middle name'] ?? '');
                $lastName  = trim($row['last name'] ?? '');
                if ($tsmId === '' || $firstName === '' || $lastName === '') {
                    $rowErrors[] = "Row {$line}: TSM ID, First Name and Last Name are required.";
                    continue;
                }
                $name = trim(preg_replace('/\s+/', ' ', "$firstName $middleName $lastName"));

                $email = trim($row['email'] ?? '');
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Row {$line}: '{$email}' is not a valid email address.";
                    continue;
                }
                $email = $email !== '' ? $email : null;

                $genderRaw = strtolower(trim($row['gender'] ?? ''));
                $gender = in_array($genderRaw, ['male','female','other'], true) ? $genderRaw : null;

                $dob = $this->parseFlexibleDate($row['date of birth'] ?? null);
                $admissionDate = $this->parseFlexibleDate($row['admission date'] ?? null)
                    ?? $this->parseFlexibleDate($row['transfer date'] ?? null)
                    ?? date('Y-m-d');

                $className = trim($row['class'] ?? '');
                $classId = null;
                if ($className !== '' && strtolower($className) !== 'n/a') {
                    $key = strtolower($className);
                    if (!isset($classByName[$key])) {
                        // Auto-create so a fresh school's class list doesn't block the whole import
                        $classByName[$key] = $this->db->insert(
                            "INSERT INTO classes (tenant_id,name,grade_level) VALUES (?,?,?)",
                            [$this->tid, $className, $className]
                        );
                    }
                    $classId = $classByName[$key];
                }

                $studentTypeRaw = strtolower(trim($row['student type'] ?? ''));
                $admissionType = str_contains($studentTypeRaw, 'old') ? 'old' : 'new';

                $userId = $this->db->insert(
                    "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,address,status) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$this->tid, $roleId, $name, $email, $row['contact number 1'] ?: '', $gender, $dob, $row['residential address'] ?: '', 'active']
                );
                $pin = $this->generateUniquePin();
                $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($pin, PASSWORD_BCRYPT), $userId]);
                $generatedPins[$tsmId] = $pin;

                $this->db->insert(
                    "INSERT INTO students (
                        tenant_id,user_id,admission_no,class_id,admission_date,status,
                        guardian_phone,emergency_contact_phone,
                        first_name,middle_name,last_name,county,country,religion,
                        previous_school,previous_school_address,previous_class,reason_for_leaving,admission_type
                     ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $this->tid, $userId, $tsmId, $classId, $admissionDate, 'active',
                        $row['contact number 1'] ?: null, $row['contact number 2'] ?: null,
                        $firstName, $middleName ?: null, $lastName, $row['county'] ?: null, $row['country'] ?: null, $row['religion'] ?: null,
                        $row['previous school name'] ?: null, $row['previous school address'] ?: null, $row['previous class'] ?: null, $row['reason for leaving'] ?: null,
                        $admissionType,
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $reason = str_contains($e->getMessage(), 'unique_admission')
                        ? "TSM ID '{$tsmId}' is already in use."
                        : 'that email is already registered.';
                } else {
                    // Surface the real reason (this is an admin-only screen) instead of a
                    // vague message, so a bad row is self-diagnosing instead of a guessing game.
                    $msg = $e->getMessage();
                    if (preg_match('/SQLSTATE\[\w+\]:?\s*(?:[^:]*:\s*\d+\s*)?(.+)/s', $msg, $m)) { $msg = trim($m[1]); }
                    $reason = mb_strlen($msg) > 160 ? mb_substr($msg, 0, 160) . '…' : $msg;
                }
                error_log("Student bulk import row {$line} failed: " . $e->getMessage());
                $rowErrors[] = "Row {$line}: {$reason}";
            }
        }
        if ($generatedPins) { $_SESSION['bulk_credentials'] = $generatedPins; }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/students');
    }

    public function store(): void {
        $this->requirePermission(['students.manage']);
        $errors = $this->validate($_POST, [
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'email'      => 'email|max:150',
            'phone'      => 'max:30',
            'dob'        => 'date',
            'admission_date' => 'date',
        ]);
        if (!empty($_POST['admission_no'])) {
            $taken = $this->db->fetchOne("SELECT id FROM students WHERE admission_no=? AND tenant_id=?", [$_POST['admission_no'], $this->tid]);
            if ($taken) { $errors['admission_no'] = 'That admission/TSM ID is already in use.'; }
        }
        $avatarUrl = $this->handleImageUpload('photo', 'students', $errors);
        if ($errors) { $this->failValidation($errors, '/school/students'); }

        $name = trim(preg_replace('/\s+/', ' ', $_POST['first_name'].' '.($_POST['middle_name']??'').' '.$_POST['last_name']));
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $pin = $this->generateUniquePin();
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,address,avatar,status) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $name, $_POST['email']?:null, $_POST['phone']??'', $_POST['gender']??null, $_POST['dob']??null, $_POST['address']??'', $avatarUrl, 'active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($pin, PASSWORD_BCRYPT), $userId]);
        $admNo = $_POST['admission_no'] ?: ('ADM-'.date('Y').'-'.str_pad($userId, 4, '0', STR_PAD_LEFT));
        $this->db->insert(
            "INSERT INTO students (
                tenant_id,user_id,admission_no,class_id,admission_date,status,blood_group,previous_school,
                guardian_name,guardian_phone,guardian_relationship,emergency_contact_phone,
                first_name,middle_name,last_name,county,country,religion,
                previous_school_address,previous_class,reason_for_leaving,admission_type
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $userId, $admNo, $_POST['class_id']?:null, $_POST['admission_date']??date('Y-m-d'), 'active',
                $_POST['blood_group']?:null, $_POST['previous_school']??null,
                $_POST['guardian_name']??null, $_POST['guardian_phone']??null, $_POST['guardian_relationship']??null,
                $_POST['emergency_contact_phone']??null,
                $_POST['first_name'], $_POST['middle_name']?:null, $_POST['last_name'],
                $_POST['county']?:null, $_POST['country']?:null, $_POST['religion']?:null,
                $_POST['previous_school_address']?:null, $_POST['previous_class']?:null, $_POST['reason_for_leaving']?:null,
                $_POST['admission_type']??'new',
            ]
        );
        $this->flash('success', "Student admitted successfully. Admission No: {$admNo} — Login PIN: {$pin} (write this down, it will not be shown again).");
        $this->redirect('/school/students');
    }

    public function show(string $id): void {
        $this->requirePermission(['students.view','students.edit','students.manage']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.avatar,
                    c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id LEFT JOIN classes c ON s.class_id=c.id
             WHERE s.id=? AND s.tenant_id=?", [$id, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }
        $grades   = $this->db->fetchAll("SELECT g.*, c.name AS course_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY g.created_at DESC LIMIT 10",[$id,$this->tid]);
        $attendance = $this->db->fetchAll("SELECT * FROM attendance WHERE student_id=? AND tenant_id=? ORDER BY date DESC LIMIT 10",[$id,$this->tid]);
        $invoices = $this->db->fetchAll("SELECT * FROM invoices WHERE student_id=? AND tenant_id=? ORDER BY created_at DESC",[$id,$this->tid]);
        $rankings = $this->db->fetchAll("SELECT * FROM student_rankings WHERE student_id=? AND tenant_id=? ORDER BY created_at",[$id,$this->tid]);

        $homework = $student['class_id'] ? $this->db->fetchAll(
            "SELECT h.id, h.title, h.due_date, h.max_score, co.name AS course_name,
                    hs.submitted_at, hs.score, hs.feedback
             FROM homework h
             LEFT JOIN courses co ON h.course_id=co.id
             LEFT JOIN homework_submissions hs ON hs.homework_id=h.id AND hs.student_id=?
             WHERE h.tenant_id=? AND h.class_id=?
             ORDER BY h.due_date DESC LIMIT 10",
            [$id, $this->tid, $student['class_id']]
        ) : [];

        $onlineExams = $student['class_id'] ? $this->db->fetchAll(
            "SELECT e.id, e.title, e.status AS exam_status, co.name AS course_name,
                    a.status AS attempt_status, a.score, a.total_marks, a.submitted_at
             FROM online_exams e
             LEFT JOIN courses co ON e.course_id=co.id
             LEFT JOIN online_exam_attempts a ON a.exam_id=e.id AND a.student_id=?
             WHERE e.tenant_id=? AND e.class_id=?
             ORDER BY e.starts_at DESC LIMIT 10",
            [$id, $this->tid, $student['class_id']]
        ) : [];

        $attendanceStats = $this->db->fetchOne(
            "SELECT COUNT(*) total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) present FROM attendance WHERE student_id=? AND tenant_id=?",
            [$id,$this->tid]
        );
        $attendanceRate = $attendanceStats['total'] > 0 ? round($attendanceStats['present'] / $attendanceStats['total'] * 100) : null;

        $gradeStats = $this->db->fetchOne(
            "SELECT AVG(marks_obtained/total_marks*100) avg_pct FROM grades WHERE student_id=? AND tenant_id=? AND total_marks>0",
            [$id,$this->tid]
        );
        $avgGrade = $gradeStats['avg_pct'] !== null ? round($gradeStats['avg_pct']) : null;

        $feesStats = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount_due-amount_paid),0) outstanding FROM invoices WHERE student_id=? AND tenant_id=? AND status NOT IN ('paid','waived')",
            [$id,$this->tid]
        );

        $this->view('school/highschool/students/show',[
            'pageTitle'=>$student['name'],'panelType'=>'school','student'=>$student,'grades'=>$grades,'attendance'=>$attendance,'invoices'=>$invoices,
            'rankings'=>$rankings,'homework'=>$homework,'onlineExams'=>$onlineExams,
            'attendanceRate'=>$attendanceRate,'avgGrade'=>$avgGrade,'outstandingFees'=>$feesStats['outstanding'],
            'flash'=>$this->getFlash(),
        ]);
    }

    public function idCard(string $id): void {
        $this->requirePermission(['students.view','students.edit','students.manage']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth, u.avatar
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
            'photoUrl' => $student['avatar'] ?? null,
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
        $this->requirePermission(['students.manage']);
        $student = $this->db->fetchOne("SELECT s.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.avatar FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$id,$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?",[$this->tid]);
        $this->view('school/highschool/students/form',['pageTitle'=>'Edit Student','panelType'=>'school','student'=>$student,'classes'=>$classes,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requirePermission(['students.manage']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if (!$student) { $this->redirect('/school/students'); }
        $errors = $this->validate($_POST, ['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'email' => 'email|max:150']);
        $avatarUrl = $this->handleImageUpload('photo', 'students', $errors);
        if ($errors) { $this->failValidation($errors, '/school/students/'.$id.'/edit'); }
        $name = trim(preg_replace('/\s+/', ' ', $_POST['first_name'].' '.($_POST['middle_name']??'').' '.$_POST['last_name']));
        if ($avatarUrl !== null) {
            $this->db->execute("UPDATE users SET avatar=? WHERE id=?", [$avatarUrl, $student['user_id']]);
        }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,date_of_birth=?,address=? WHERE id=?",
            [$name,$_POST['email']?:null,$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'',$student['user_id']]);
        $this->db->execute(
            "UPDATE students SET class_id=?,status=?,first_name=?,middle_name=?,last_name=?,county=?,country=?,religion=?,
                guardian_name=?,guardian_phone=?,guardian_relationship=?,emergency_contact_phone=?,
                previous_school=?,previous_school_address=?,previous_class=?,reason_for_leaving=?,admission_type=?
             WHERE id=? AND tenant_id=?",
            [
                $_POST['class_id']?:null, $_POST['status']??'active', $_POST['first_name'], $_POST['middle_name']?:null, $_POST['last_name'],
                $_POST['county']?:null, $_POST['country']?:null, $_POST['religion']?:null,
                $_POST['guardian_name']?:null, $_POST['guardian_phone']?:null, $_POST['guardian_relationship']?:null, $_POST['emergency_contact_phone']?:null,
                $_POST['previous_school']?:null, $_POST['previous_school_address']?:null, $_POST['previous_class']?:null, $_POST['reason_for_leaving']?:null,
                $_POST['admission_type']??'new',
                $id, $this->tid,
            ]
        );
        $this->flash('success','Student updated.');
        $this->redirect('/school/students/'.$id);
    }

    public function delete(string $id): void {
        $this->requirePermission(['students.manage']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
        if ($student) {
            $this->db->execute("DELETE FROM students WHERE id=? AND tenant_id=?",[$id,$this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?",[$student['user_id']]);
        }
        $this->flash('success','Student removed.');
        $this->redirect('/school/students');
    }

    public function resetPin(string $id): void {
        $this->requirePermission(['students.manage']);
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$student) { $this->redirect('/school/students'); }
        $pin = $this->generateUniquePin();
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($pin, PASSWORD_BCRYPT), $student['user_id']]);
        $this->flash('success', "New login PIN: {$pin} (write this down, it will not be shown again).");
        $this->redirect('/school/students/'.$id);
    }

    /** One-time download of the PINs generated by the most recent bulk import (session-scoped, cleared after reading). */
    public function downloadCredentials(): never {
        $this->requirePermission(['students.manage']);
        $pins = $_SESSION['bulk_credentials'] ?? [];
        unset($_SESSION['bulk_credentials']);
        $rows = [];
        foreach ($pins as $admissionNo => $pin) { $rows[] = [$admissionNo, $pin]; }
        $this->downloadCsv('student_login_pins.csv', ['Admission No', 'PIN'], $rows);
    }
}
