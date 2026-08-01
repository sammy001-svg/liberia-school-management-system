<?php
require_once ROOT_DIR . '/core/Controller.php';

class AdmissionController extends Controller {

    /** Document slots offered on the public application form, in display order.
     *  Key = form field suffix, value = the document_type recorded against the upload. */
    /** The four attachments named on the school's paper admission form, in that order. */
    public const APPLICATION_DOCUMENT_SLOTS = [
        'report_card'       => 'Report Card',
        'transcript'        => 'Transcript',
        'recommendation'    => 'Letter of Recommendation',
        'birth_certificate' => 'Birth Certificate',
    ];

    /** Deliberately narrower than Controller::handleFileUpload()'s internal allowlist:
     *  /apply is unauthenticated, so anonymous visitors get documents only — no
     *  archives, office macros or video — and a tighter size cap. */
    private const PUBLIC_DOC_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    private const PUBLIC_DOC_MAX_BYTES  = 5242880; // 5MB

    private function uploadApplicationFile(string $field, array &$errors, bool $imageOnly = false): array {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        $allowed = $imageOnly
            ? array_values(array_diff(self::PUBLIC_DOC_EXTENSIONS, ['pdf']))
            : self::PUBLIC_DOC_EXTENSIONS;
        $ext = strtolower(pathinfo((string)($_FILES[$field]['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $errors[$field] = $imageOnly
                ? 'Photos must be a JPG, PNG or WEBP image.'
                : 'Only PDF, JPG, PNG or WEBP files can be attached.';
            return [null, null];
        }
        return $this->handleFileUpload($field, 'applications', $errors, self::PUBLIC_DOC_MAX_BYTES);
    }

    // Same resolution used by AuthController::loginPage() for the public login/apply
    // screens: a custom domain match, falling back to "the one active tenant" for
    // single-school deployments — there's no logged-in session to read tenant_id from.
    private function resolveTenant(): ?array {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE domain = ? AND status = 'active' LIMIT 1", [$host]);
        if (!$tenant) {
            $activeTenants = $this->db->fetchAll("SELECT * FROM tenants WHERE status = 'active'");
            if (count($activeTenants) === 1) {
                $tenant = $activeTenants[0];
            }
        }
        return $tenant ?: null;
    }

    // --- PUBLIC: no login required ---

    public function applyPage(): void {
        $tenant = $this->resolveTenant();
        $branding = [
            'name' => $tenant['name'] ?? 'Liberia School Management System',
            'primary_color' => $tenant['primary_color'] ?? '#10B981',
            'secondary_color' => $tenant['secondary_color'] ?? '#059669',
            'logo' => $tenant['logo'] ?? null,
        ];
        $classes = $tenant ? $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$tenant['id']]) : [];
        $this->view('auth/apply', [
            'pageTitle' => 'Online Application', 'branding' => $branding, 'classes' => $classes,
            'documentSlots' => self::APPLICATION_DOCUMENT_SLOTS,
            'flash' => $this->getFlash(),
        ]);
    }

    public function applySubmit(): void {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            $this->flash('danger', 'Unable to determine which school this application is for.');
            $this->redirect('/apply');
        }
        $errors = $this->validate($_POST, [
            'first_name'      => 'required|max:100',
            'last_name'       => 'required|max:100',
            'date_of_birth'   => 'date',
            'emergency_email' => 'email|max:150',
        ]);

        // The paper form lists mother and father separately and neither alone is mandatory,
        // but an application with no contactable parent is unusable — require at least one.
        $motherName = trim($_POST['mother_name'] ?? '');
        $fatherName = trim($_POST['father_name'] ?? '');
        $motherPhone = trim($_POST['mother_phone'] ?? '');
        $fatherPhone = trim($_POST['father_phone'] ?? '');
        if ($motherName === '' && $fatherName === '') {
            $errors['mother_name'] = "Enter at least one parent — the mother's or the father's name.";
        } elseif ($motherPhone === '' && $fatherPhone === '') {
            $errors['mother_phone'] = 'Enter a phone number for at least one parent.';
        }

        // Validate and stage every upload before creating the application, so a rejected
        // file never leaves a half-finished application behind.
        [$studentPhoto] = $this->uploadApplicationFile('student_photo', $errors, true);
        [$parentPhoto]  = $this->uploadApplicationFile('parent_photo', $errors, true);
        $uploads = [];
        foreach (self::APPLICATION_DOCUMENT_SLOTS as $slot => $label) {
            [$url, $origName] = $this->uploadApplicationFile('doc_' . $slot, $errors);
            if ($url) { $uploads[] = [$label, $url, $origName]; }
        }
        if ($errors) { $this->failValidation($errors, '/apply'); }

        // The single guardian pair the enrolment step needs is derived from whichever
        // parent was supplied, father first, falling back to the emergency contact.
        $guardianName  = $fatherName ?: $motherName ?: (trim($_POST['emergency_name'] ?? '') ?: null);
        $guardianPhone = $fatherPhone ?: $motherPhone ?: (trim($_POST['emergency_phone'] ?? '') ?: null);
        $guardianRel   = $fatherName ? 'Father' : ($motherName ? 'Mother' : ($_POST['emergency_relationship'] ?: null));

        $tid = $tenant['id'];
        $appId = $this->db->insert(
            "INSERT INTO admission_applications (
                tenant_id,reference_no,first_name,middle_name,last_name,date_of_birth,address,desired_class_id,
                student_photo,parent_photo,
                mother_name,mother_phone,father_name,father_phone,
                guardian_name,guardian_phone,guardian_relationship,guardian_email,
                previous_school,previous_school_address,principal_name,principal_phone,
                sponsor_name,sponsor_phone,last_class,previous_class,
                emergency_name,emergency_phone,emergency_relationship,emergency_address,emergency_email,
                medical_conditions,allergies,notes
             ) VALUES (?,?,?,?,?,?,?,?, ?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?,?,?,?, ?,?,?)",
            [
                $tid, '', $_POST['first_name'], $_POST['middle_name'] ?: null, $_POST['last_name'],
                $_POST['date_of_birth'] ?: null, $_POST['address'] ?: null, $_POST['desired_class_id'] ?: null,
                $studentPhoto, $parentPhoto,
                $motherName ?: null, $motherPhone ?: null, $fatherName ?: null, $fatherPhone ?: null,
                $guardianName, $guardianPhone, $guardianRel, $_POST['emergency_email'] ?: null,
                $_POST['previous_school'] ?: null, $_POST['previous_school_address'] ?: null,
                $_POST['principal_name'] ?: null, $_POST['principal_phone'] ?: null,
                $_POST['sponsor_name'] ?: null, $_POST['sponsor_phone'] ?: null,
                $_POST['last_class'] ?: null, $_POST['last_class'] ?: null,
                $_POST['emergency_name'] ?: null, $_POST['emergency_phone'] ?: null,
                $_POST['emergency_relationship'] ?: null, $_POST['emergency_address'] ?: null,
                $_POST['emergency_email'] ?: null,
                $_POST['medical_conditions'] ?: null, $_POST['allergies'] ?: null, $_POST['notes'] ?: null,
            ]
        );

        // Authorised pick-up persons — the form provides three lines; save the filled ones.
        foreach (($_POST['pickup_name'] ?? []) as $i => $pickupName) {
            $pickupName = trim((string)$pickupName);
            if ($pickupName === '') { continue; }
            $this->db->insert(
                "INSERT INTO application_pickup_persons (tenant_id,application_id,name,phone,address,sort_order) VALUES (?,?,?,?,?,?)",
                [
                    $tid, $appId, $pickupName,
                    trim((string)($_POST['pickup_phone'][$i] ?? '')) ?: null,
                    trim((string)($_POST['pickup_address'][$i] ?? '')) ?: null,
                    (int)$i + 1,
                ]
            );
        }
        $reference = 'APP-' . date('Y') . '-' . str_pad((string)$appId, 4, '0', STR_PAD_LEFT);
        $this->db->execute("UPDATE admission_applications SET reference_no=? WHERE id=?", [$reference, $appId]);

        foreach ($uploads as [$label, $url, $origName]) {
            $this->db->insert(
                "INSERT INTO application_documents (tenant_id,application_id,document_type,file_url,file_name) VALUES (?,?,?,?,?)",
                [$tid, $appId, $label, $url, $origName]
            );
        }

        $attached = $uploads ? ' ' . count($uploads) . ' document(s) received.' : '';
        $this->flash('success', "Application submitted successfully! Your reference number is {$reference}.{$attached} The school will contact you regarding next steps.");
        $this->redirect('/apply');
    }

    // --- ADMIN: review queue ---

    public function index(): void {
        $this->requirePermission(['admissions.manage']);
        $tid = $this->tenantId() ?? 0;
        $status = $_GET['status'] ?? 'pending';
        $params = [$tid];
        $where = "a.tenant_id=?";
        if ($status) { $where .= " AND a.status=?"; $params[] = $status; }

        $applications = $this->db->fetchAll(
            "SELECT a.*, c.name AS desired_class_name
             FROM admission_applications a LEFT JOIN classes c ON a.desired_class_id=c.id
             WHERE $where ORDER BY a.created_at DESC", $params
        );
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending,
                    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) approved,
                    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected
             FROM admission_applications WHERE tenant_id=?", [$tid]
        );
        $this->view('school/highschool/admissions/index', [
            'pageTitle' => 'Online Applications', 'panelType' => 'school',
            'applications' => $applications, 'status' => $status, 'stats' => $stats,
            'flash' => $this->getFlash(),
        ]);
    }

    public function show(string $id): void {
        $this->requirePermission(['admissions.manage']);
        $tid = $this->tenantId() ?? 0;
        $application = $this->db->fetchOne(
            "SELECT a.*, c.name AS desired_class_name, ru.name AS reviewed_by_name
             FROM admission_applications a
             LEFT JOIN classes c ON a.desired_class_id=c.id
             LEFT JOIN users ru ON a.reviewed_by=ru.id
             WHERE a.id=? AND a.tenant_id=?", [$id, $tid]
        );
        if (!$application) { $this->redirect('/school/admissions'); }
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$tid]);
        $documents = $this->db->fetchAll(
            "SELECT * FROM application_documents WHERE application_id=? AND tenant_id=? ORDER BY id", [$id, $tid]
        );
        $pickupPersons = $this->db->fetchAll(
            "SELECT * FROM application_pickup_persons WHERE application_id=? AND tenant_id=? ORDER BY sort_order", [$id, $tid]
        );
        $this->view('school/highschool/admissions/show', [
            'pageTitle' => 'Application: ' . $application['first_name'] . ' ' . $application['last_name'],
            'panelType' => 'school', 'application' => $application, 'classes' => $classes,
            'documents' => $documents, 'pickupPersons' => $pickupPersons,
            'flash' => $this->getFlash(),
        ]);
    }

    public function approve(string $id): void {
        $this->requirePermission(['admissions.manage']);
        $tid = $this->tenantId() ?? 0;
        $application = $this->db->fetchOne("SELECT * FROM admission_applications WHERE id=? AND tenant_id=? AND status='pending'", [$id, $tid]);
        if (!$application) { $this->redirect('/school/admissions'); }

        $name = trim(preg_replace('/\s+/', ' ', $application['first_name'].' '.($application['middle_name']??'').' '.$application['last_name']));
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Student' LIMIT 1")['id'] ?? 7;
        $pin = $this->generateUniquePin();
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,phone,gender,date_of_birth,address,avatar,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $tid, $roleId, $name, $application['guardian_phone'] ?? '', $application['gender'],
                $application['date_of_birth'], $application['address'] ?? '', $application['student_photo'] ?? null, 'active',
            ]
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($pin, PASSWORD_BCRYPT), $userId]);

        $classId = $_POST['class_id'] ?: $application['desired_class_id'];
        $admissionDate = $_POST['admission_date'] ?: date('Y-m-d');
        $admNo = $this->generateAdmissionNo($tid);
        $studentId = $this->db->insert(
            "INSERT INTO students (
                tenant_id,user_id,admission_no,class_id,admission_date,status,
                guardian_name,guardian_phone,guardian_relationship,emergency_contact_phone,
                first_name,middle_name,last_name,previous_school,previous_school_address,previous_class,admission_type
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tid, $userId, $admNo, $classId ?: null, $admissionDate, 'active',
                $application['guardian_name'], $application['guardian_phone'], $application['guardian_relationship'],
                $application['emergency_phone'] ?: $application['guardian_phone'],
                $application['first_name'], $application['middle_name'], $application['last_name'],
                $application['previous_school'], $application['previous_school_address'],
                $application['last_class'] ?: $application['previous_class'], 'new',
            ]
        );

        // Carry any attachments across so the paperwork submitted with the application
        // lands on the new student's profile instead of being stranded on the application.
        $documents = $this->db->fetchAll(
            "SELECT * FROM application_documents WHERE application_id=? AND tenant_id=?", [$id, $tid]
        );
        foreach ($documents as $doc) {
            $this->db->insert(
                "INSERT INTO student_documents (tenant_id,student_id,document_type,title,issued_by,file_url,file_name,notes,uploaded_by)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $tid, $studentId, $doc['document_type'], $doc['document_type'],
                    $application['previous_school'] ?: null, $doc['file_url'], $doc['file_name'],
                    'Submitted with online application ' . $application['reference_no'], $_SESSION['user_id'],
                ]
            );
        }

        $this->db->execute(
            "UPDATE admission_applications SET status='approved', reviewed_by=?, reviewed_at=NOW(), student_id=? WHERE id=?",
            [$_SESSION['user_id'], $studentId, $id]
        );
        $this->flash('success', "Application approved and enrolled. Admission No: {$admNo} — Login PIN: {$pin} (write this down, it will not be shown again).");
        $this->redirect('/school/students/'.$studentId);
    }

    public function reject(string $id): void {
        $this->requirePermission(['admissions.manage']);
        $tid = $this->tenantId() ?? 0;
        $application = $this->db->fetchOne("SELECT id FROM admission_applications WHERE id=? AND tenant_id=? AND status='pending'", [$id, $tid]);
        if (!$application) { $this->redirect('/school/admissions'); }
        $this->db->execute(
            "UPDATE admission_applications SET status='rejected', reviewed_by=?, reviewed_at=NOW(), review_notes=? WHERE id=?",
            [$_SESSION['user_id'], $_POST['review_notes'] ?: null, $id]
        );
        $this->flash('success', 'Application rejected.');
        $this->redirect('/school/admissions');
    }
}
