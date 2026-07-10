<?php
require_once ROOT_DIR . '/core/Controller.php';

class CertificateController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['certificates.manage']);
        $academicYears = $this->db->fetchAll("SELECT * FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $academicYearId = $_GET['academic_year_id'] ?? ($academicYears[0]['id'] ?? '');
        $classId = $_GET['class_id'] ?? '';
        $type = $_GET['type'] ?: 'completion';

        $selectedYear = null;
        foreach ($academicYears as $y) { if ((string)$y['id'] === (string)$academicYearId) { $selectedYear = $y; break; } }

        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);

        $whereParams = [$this->tid];
        $where = "s.tenant_id=? AND s.status IN ('active','graduated')";
        if ($classId) { $where .= " AND s.class_id=?"; $whereParams[] = $classId; }

        $students = $academicYearId ? $this->db->fetchAll(
            "SELECT s.id AS student_id, u.name AS student_name, s.admission_no, c.name AS class_name,
                    cert.id AS certificate_id, cert.certificate_no, cert.issued_date
             FROM students s
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             LEFT JOIN certificates cert ON cert.student_id=s.id AND cert.academic_year_id=? AND cert.type=?
             WHERE $where ORDER BY u.name",
            array_merge([$academicYearId, $type], $whereParams)
        ) : [];

        $stats = [
            'total'   => count($students),
            'issued'  => count(array_filter($students, fn($s) => $s['certificate_id'])),
            'pending' => count(array_filter($students, fn($s) => !$s['certificate_id'])),
        ];

        $this->view('school/highschool/certificates/index', [
            'pageTitle'=>'Certificates','panelType'=>'school',
            'academicYears'=>$academicYears,'selectedYear'=>$selectedYear,'academicYearId'=>$academicYearId,
            'classes'=>$classes,'classId'=>$classId,'type'=>$type,
            'students'=>$students,'stats'=>$stats,
            'flash'=>$this->getFlash(),
        ]);
    }

    public function generate(): void {
        $this->requirePermission(['certificates.manage']);
        $errors = $this->validate($_POST, ['student_id' => 'required', 'academic_year_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates'); }
        $studentId = $_POST['student_id'];
        $academicYearId = $_POST['academic_year_id'];
        $type = $_POST['type'] ?: 'completion';

        $exists = $this->db->fetchOne(
            "SELECT id FROM certificates WHERE tenant_id=? AND student_id=? AND academic_year_id=? AND type=?",
            [$this->tid, $studentId, $academicYearId, $type]
        );
        if ($exists) {
            $this->flash('warning', 'A certificate already exists for this student for this year.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId);
        }

        $certNo = 'CERT-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
        $id = $this->db->insert(
            "INSERT INTO certificates (tenant_id,student_id,academic_year_id,certificate_no,type,title,remarks,issued_date,issued_by) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid, $studentId, $academicYearId, $certNo, $type, $_POST['title'] ?: null, $_POST['remarks'] ?: null, date('Y-m-d'), $_SESSION['user_id']]
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
        $errors = $this->validate($_POST, ['academic_year_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/certificates'); }
        $academicYearId = $_POST['academic_year_id'];
        $classId = $_POST['class_id'] ?? '';
        $type = $_POST['type'] ?: 'completion';

        $params = [$this->tid];
        $where = "tenant_id=? AND status IN ('active','graduated')";
        if ($classId) { $where .= " AND class_id=?"; $params[] = $classId; }
        $students = $this->db->fetchAll("SELECT id FROM students WHERE $where", $params);

        $alreadyIssued = array_flip(array_column(
            $this->db->fetchAll("SELECT student_id FROM certificates WHERE tenant_id=? AND academic_year_id=? AND type=?", [$this->tid, $academicYearId, $type]),
            'student_id'
        ));

        set_time_limit(120);
        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($students as $s) {
                if (isset($alreadyIssued[$s['id']])) { $skipped++; continue; }
                $certNo = 'CERT-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));
                $this->db->insert(
                    "INSERT INTO certificates (tenant_id,student_id,academic_year_id,certificate_no,type,issued_date,issued_by) VALUES (?,?,?,?,?,?,?)",
                    [$this->tid, $s['id'], $academicYearId, $certNo, $type, date('Y-m-d'), $_SESSION['user_id']]
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("Certificate bulkGenerate failed: " . $e->getMessage());
            $this->flash('danger', 'Could not generate certificates — no changes were made. Please try again.');
            $this->redirect('/school/certificates?academic_year_id='.$academicYearId);
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Issued {$created} certificate(s)." . ($skipped > 0 ? " {$skipped} student(s) already had one." : ''));
        $this->redirect('/school/certificates?academic_year_id='.$academicYearId);
    }

    public function printCertificate(string $id): void {
        $this->requirePermission(['certificates.manage']);
        $cert = $this->db->fetchOne(
            "SELECT cert.*, u.name AS student_name, s.admission_no, c.name AS class_name, ay.name AS year_name
             FROM certificates cert
             JOIN students s ON cert.student_id=s.id JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
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
}
