<?php
require_once ROOT_DIR . '/core/Controller.php';

class CertificateController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function certNo(): string {
        return 'CERT-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
    }

    public function index(): void {
        $this->requirePermission(['certificates.manage']);
        $tab = ($_GET['tab'] ?? 'student') === 'staff' ? 'staff' : 'student';
        $academicYears = $this->db->fetchAll("SELECT * FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $academicYearId = $_GET['academic_year_id'] ?? ($academicYears[0]['id'] ?? '');
        $classId = $_GET['class_id'] ?? '';

        $selectedYear = null;
        foreach ($academicYears as $y) { if ((string)$y['id'] === (string)$academicYearId) { $selectedYear = $y; break; } }

        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);

        // Each tab only offers the certificate types relevant to that recipient category,
        // so "which types can be issued" is driven by the type's own recipient_category.
        $certificateTypes = $this->db->fetchAll(
            "SELECT * FROM certificate_types WHERE tenant_id=? AND recipient_category IN (?, 'any') ORDER BY name",
            [$this->tid, $tab]
        );
        $typeId = $_GET['type_id'] ?? ($certificateTypes[0]['id'] ?? '');

        $students = []; $staff = []; $stats = ['total' => 0, 'issued' => 0, 'pending' => 0];

        if ($tab === 'student') {
            $whereParams = [$this->tid];
            $where = "s.tenant_id=? AND s.status IN ('active','graduated')";
            if ($classId) { $where .= " AND s.class_id=?"; $whereParams[] = $classId; }

            $students = ($academicYearId && $typeId) ? $this->db->fetchAll(
                "SELECT s.id AS student_id, u.id AS user_id, u.name AS student_name, s.admission_no, c.name AS class_name,
                        cert.id AS certificate_id, cert.certificate_no, cert.issued_date
                 FROM students s
                 JOIN users u ON s.user_id=u.id
                 LEFT JOIN classes c ON s.class_id=c.id
                 LEFT JOIN certificates cert ON cert.user_id=u.id AND cert.academic_year_id=? AND cert.certificate_type_id=?
                 WHERE $where ORDER BY u.name",
                array_merge([$academicYearId, $typeId], $whereParams)
            ) : [];
            $stats = [
                'total'   => count($students),
                'issued'  => count(array_filter($students, fn($s) => $s['certificate_id'])),
                'pending' => count(array_filter($students, fn($s) => !$s['certificate_id'])),
            ];
        } else {
            // Staff certificates aren't tied to an academic year, so matching is by
            // (user, type) only — academic_year_id is always NULL on this path.
            $staff = $typeId ? $this->db->fetchAll(
                "SELECT u.id AS user_id, u.name AS staff_name, r.name AS role_name, u.employee_no,
                        cert.id AS certificate_id, cert.certificate_no, cert.issued_date
                 FROM users u
                 JOIN roles r ON u.role_id=r.id
                 LEFT JOIN certificates cert ON cert.user_id=u.id AND cert.certificate_type_id=? AND cert.academic_year_id IS NULL
                 WHERE u.tenant_id=? AND r.name IN ('School Admin','Teacher','Staff','Accountant') AND u.status='active'
                 ORDER BY u.name",
                [$typeId, $this->tid]
            ) : [];
            $stats = [
                'total'   => count($staff),
                'issued'  => count(array_filter($staff, fn($s) => $s['certificate_id'])),
                'pending' => count(array_filter($staff, fn($s) => !$s['certificate_id'])),
            ];
        }

        $this->view('school/highschool/certificates/index', [
            'pageTitle' => 'Certificates', 'panelType' => 'school', 'tab' => $tab,
            'academicYears' => $academicYears, 'selectedYear' => $selectedYear, 'academicYearId' => $academicYearId,
            'classes' => $classes, 'classId' => $classId, 'certificateTypes' => $certificateTypes, 'typeId' => $typeId,
            'students' => $students, 'staff' => $staff, 'stats' => $stats,
            'flash' => $this->getFlash(),
        ]);
    }

    public function generate(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validate($_POST, ['student_id' => 'required', 'academic_year_id' => 'required', 'certificate_type_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates'); }
        $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?", [$_POST['student_id'], $this->tid]);
        if (!$student) { $this->redirect('/school/certificates'); }
        $academicYearId = $_POST['academic_year_id'];
        $typeId = $_POST['certificate_type_id'];

        $exists = $this->db->fetchOne(
            "SELECT id FROM certificates WHERE tenant_id=? AND user_id=? AND academic_year_id=? AND certificate_type_id=?",
            [$this->tid, $student['user_id'], $academicYearId, $typeId]
        );
        if ($exists) {
            $this->flash('warning', 'A certificate already exists for this student for this year.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
        }

        $id = $this->db->insert(
            "INSERT INTO certificates (tenant_id,user_id,certificate_type_id,academic_year_id,certificate_no,title,remarks,placement,issued_date,issued_by) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $student['user_id'], $typeId, $academicYearId, $this->certNo(), $_POST['title'] ?: null, $_POST['remarks'] ?: null, $_POST['placement'] ?: null, date('Y-m-d'), $_SESSION['user_id']]
        );
        $this->flash('success', 'Certificate issued.');
        $this->redirect('/school/certificates/'.$id.'/print');
    }

    // Idempotent bulk issue: skips students who already have a certificate of this
    // type for this academic year, so re-running after adding a few late students
    // only creates the new ones. Wrapped in a single transaction with a batched
    // existence check (see the Finance bulk-invoicing fix) so it can't silently
    // truncate partway through a large class list.
    public function bulkGenerate(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validate($_POST, ['academic_year_id' => 'required', 'certificate_type_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates'); }
        $academicYearId = $_POST['academic_year_id'];
        $classId = $_POST['class_id'] ?? '';
        $typeId = $_POST['certificate_type_id'];

        $params = [$this->tid];
        $where = "tenant_id=? AND status IN ('active','graduated')";
        if ($classId) { $where .= " AND class_id=?"; $params[] = $classId; }
        $students = $this->db->fetchAll("SELECT id, user_id FROM students WHERE $where", $params);

        $alreadyIssued = array_flip(array_column(
            $this->db->fetchAll("SELECT user_id FROM certificates WHERE tenant_id=? AND academic_year_id=? AND certificate_type_id=?", [$this->tid, $academicYearId, $typeId]),
            'user_id'
        ));

        set_time_limit(120);
        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($students as $s) {
                if (isset($alreadyIssued[$s['user_id']])) { $skipped++; continue; }
                $this->db->insert(
                    "INSERT INTO certificates (tenant_id,user_id,certificate_type_id,academic_year_id,certificate_no,issued_date,issued_by) VALUES (?,?,?,?,?,?,?)",
                    [$this->tid, $s['user_id'], $typeId, $academicYearId, $this->certNo(), date('Y-m-d'), $_SESSION['user_id']]
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("Certificate bulkGenerate failed: " . $e->getMessage());
            $this->flash('danger', 'Could not generate certificates — no changes were made. Please try again.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Issued {$created} certificate(s)." . ($skipped > 0 ? " {$skipped} student(s) already had one." : ''));
        $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
    }

    // Same idempotent-skip + transaction pattern as bulkGenerate(), but scoped to an explicit
    // set of student IDs (ticked via checkboxes) instead of "everyone matching a class filter."
    public function issueSelected(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validate($_POST, ['academic_year_id' => 'required', 'certificate_type_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates'); }
        $academicYearId = $_POST['academic_year_id'];
        $typeId = $_POST['certificate_type_id'];
        $studentIds = array_unique(array_filter(array_map('intval', $_POST['student_ids'] ?? [])));
        if (empty($studentIds)) {
            $this->flash('danger', 'Select at least one student to issue a certificate to.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
        }

        $alreadyIssued = array_flip(array_column(
            $this->db->fetchAll("SELECT user_id FROM certificates WHERE tenant_id=? AND academic_year_id=? AND certificate_type_id=?", [$this->tid, $academicYearId, $typeId]),
            'user_id'
        ));

        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($studentIds as $sid) {
                $student = $this->db->fetchOne("SELECT user_id FROM students WHERE id=? AND tenant_id=?", [$sid, $this->tid]);
                if (!$student || isset($alreadyIssued[$student['user_id']])) { $skipped++; continue; }
                $this->db->insert(
                    "INSERT INTO certificates (tenant_id,user_id,certificate_type_id,academic_year_id,certificate_no,issued_date,issued_by) VALUES (?,?,?,?,?,?,?)",
                    [$this->tid, $student['user_id'], $typeId, $academicYearId, $this->certNo(), date('Y-m-d'), $_SESSION['user_id']]
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("Certificate issueSelected failed: " . $e->getMessage());
            $this->flash('danger', 'Could not issue certificates — no changes were made. Please try again.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Issued {$created} certificate(s)." . ($skipped > 0 ? " {$skipped} student(s) were skipped (already issued)." : ''));
        $this->redirect('/school/certificates?academic_year_id='.$academicYearId.'&type_id='.$typeId);
    }

    // Same idempotent-skip pattern as issueSelected(), but for Teacher/Staff/Accountant/School
    // Admin recipients — no academic year involved, so matching is by (user, type) only.
    public function issueToStaffSelected(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validate($_POST, ['certificate_type_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates?tab=staff'); }
        $typeId = $_POST['certificate_type_id'];
        $userIds = array_unique(array_filter(array_map('intval', $_POST['user_ids'] ?? [])));
        if (empty($userIds)) {
            $this->flash('danger', 'Select at least one person to issue a certificate to.');
            $this->redirect('/school/certificates?tab=staff&type_id='.$typeId);
        }

        $alreadyIssued = array_flip(array_column(
            $this->db->fetchAll("SELECT user_id FROM certificates WHERE tenant_id=? AND certificate_type_id=? AND academic_year_id IS NULL", [$this->tid, $typeId]),
            'user_id'
        ));

        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($userIds as $uid) {
                $user = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$uid, $this->tid]);
                if (!$user || isset($alreadyIssued[$uid])) { $skipped++; continue; }
                $this->db->insert(
                    "INSERT INTO certificates (tenant_id,user_id,certificate_type_id,certificate_no,issued_date,issued_by) VALUES (?,?,?,?,?,?)",
                    [$this->tid, $uid, $typeId, $this->certNo(), date('Y-m-d'), $_SESSION['user_id']]
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("Certificate issueToStaffSelected failed: " . $e->getMessage());
            $this->flash('danger', 'Could not issue certificates — no changes were made. Please try again.');
            $this->redirect('/school/certificates?tab=staff&type_id='.$typeId);
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Issued {$created} certificate(s)." . ($skipped > 0 ? " {$skipped} were skipped (already issued)." : ''));
        $this->redirect('/school/certificates?tab=staff&type_id='.$typeId);
    }

    public function printCertificate(string $id): void {
        $this->requirePermission(['certificates.manage']);
        $cert = $this->db->fetchOne(
            "SELECT cert.*, u.name AS recipient_name, ct.name AS type_name,
                    s.admission_no, cl.name AS class_name, ay.name AS year_name
             FROM certificates cert
             JOIN users u ON cert.user_id=u.id
             JOIN certificate_types ct ON cert.certificate_type_id=ct.id
             LEFT JOIN students s ON s.user_id=u.id
             LEFT JOIN classes cl ON s.class_id=cl.id
             LEFT JOIN academic_years ay ON cert.academic_year_id=ay.id
             WHERE cert.id=? AND cert.tenant_id=?", [$id, $this->tid]
        );
        if (!$cert) { $this->redirect('/school/certificates'); }
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/certificate_print', ['pageTitle'=>'Certificate', 'tenant'=>$tenant, 'cert'=>$cert]);
    }

    public function delete(string $id): void {
        $this->requirePermission(['certificates.manage']);
        $this->db->execute("DELETE FROM certificates WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Certificate revoked.');
        $this->redirect('/school/certificates');
    }

    // --- CERTIFICATE TYPES ---

    public function types(): void {
        $this->requirePermission(['certificates.manage']);
        $types = $this->db->fetchAll(
            "SELECT ct.*, (SELECT COUNT(*) FROM certificates c WHERE c.certificate_type_id=ct.id) AS usage_count
             FROM certificate_types ct WHERE ct.tenant_id=? ORDER BY ct.name", [$this->tid]
        );
        $this->view('school/highschool/certificates/types', [
            'pageTitle' => 'Certificate Types', 'panelType' => 'school', 'types' => $types, 'flash' => $this->getFlash(),
        ]);
    }

    private function validateTypeInput(array $errors): array {
        if (!in_array($_POST['recipient_category'] ?? '', ['student', 'staff', 'any'], true)) {
            $errors['recipient_category'] = 'Choose a valid recipient category.';
        }
        return $errors;
    }

    public function storeType(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validateTypeInput($this->validate($_POST, ['name' => 'required|max:100']));
        if ($errors) { $this->failValidation($errors, '/school/certificates/types'); }
        $this->db->insert(
            "INSERT INTO certificate_types (tenant_id,name,recipient_category,description) VALUES (?,?,?,?)",
            [$this->tid, $_POST['name'], $_POST['recipient_category'], $_POST['description'] ?: null]
        );
        $this->flash('success', 'Certificate type added.');
        $this->redirect('/school/certificates/types');
    }

    public function updateType(string $id): void {
        $this->requirePermission(['certificates.manage']);
        $type = $this->db->fetchOne("SELECT id FROM certificate_types WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$type) { $this->redirect('/school/certificates/types'); }
        $errors = $this->validateTypeInput($this->validate($_POST, ['name' => 'required|max:100']));
        if ($errors) { $this->failValidation($errors, '/school/certificates/types'); }
        $this->db->execute(
            "UPDATE certificate_types SET name=?,recipient_category=?,description=? WHERE id=? AND tenant_id=?",
            [$_POST['name'], $_POST['recipient_category'], $_POST['description'] ?: null, $id, $this->tid]
        );
        $this->flash('success', 'Certificate type updated.');
        $this->redirect('/school/certificates/types');
    }

    public function deleteType(string $id): void {
        $this->requirePermission(['certificates.manage']);
        $inUse = $this->db->fetchOne("SELECT COUNT(*) c FROM certificates WHERE certificate_type_id=?", [$id])['c'];
        if ($inUse > 0) {
            $this->flash('danger', "This type has {$inUse} certificate(s) issued against it and can't be deleted.");
            $this->redirect('/school/certificates/types');
        }
        $this->db->execute("DELETE FROM certificate_types WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Certificate type removed.');
        $this->redirect('/school/certificates/types');
    }
}
