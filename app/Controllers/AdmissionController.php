<?php
require_once ROOT_DIR . '/core/Controller.php';

class AdmissionController extends Controller {

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
            'first_name'     => 'required|max:100',
            'last_name'      => 'required|max:100',
            'date_of_birth'  => 'date',
            'guardian_name'  => 'required|max:150',
            'guardian_phone' => 'required|max:30',
            'guardian_email' => 'email|max:150',
        ]);
        if ($errors) { $this->failValidation($errors, '/apply'); }

        $tid = $tenant['id'];
        $appId = $this->db->insert(
            "INSERT INTO admission_applications (
                tenant_id,reference_no,first_name,middle_name,last_name,gender,date_of_birth,desired_class_id,
                guardian_name,guardian_relationship,guardian_phone,guardian_email,address,previous_school,previous_class,notes
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tid, '', $_POST['first_name'], $_POST['middle_name'] ?: null, $_POST['last_name'],
                $_POST['gender'] ?: null, $_POST['date_of_birth'] ?: null, $_POST['desired_class_id'] ?: null,
                $_POST['guardian_name'], $_POST['guardian_relationship'] ?: null, $_POST['guardian_phone'],
                $_POST['guardian_email'] ?: null, $_POST['address'] ?: null,
                $_POST['previous_school'] ?: null, $_POST['previous_class'] ?: null, $_POST['notes'] ?: null,
            ]
        );
        $reference = 'APP-' . date('Y') . '-' . str_pad((string)$appId, 4, '0', STR_PAD_LEFT);
        $this->db->execute("UPDATE admission_applications SET reference_no=? WHERE id=?", [$reference, $appId]);

        $this->flash('success', "Application submitted successfully! Your reference number is {$reference}. The school will contact you regarding next steps.");
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
        $this->view('school/highschool/admissions/show', [
            'pageTitle' => 'Application: ' . $application['first_name'] . ' ' . $application['last_name'],
            'panelType' => 'school', 'application' => $application, 'classes' => $classes,
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
            "INSERT INTO users (tenant_id,role_id,name,phone,gender,date_of_birth,address,status) VALUES (?,?,?,?,?,?,?,?)",
            [$tid, $roleId, $name, '', $application['gender'], $application['date_of_birth'], $application['address'] ?? '', 'active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($pin, PASSWORD_BCRYPT), $userId]);

        $classId = $_POST['class_id'] ?: $application['desired_class_id'];
        $admissionDate = $_POST['admission_date'] ?: date('Y-m-d');
        $admNo = 'ADM-'.date('Y').'-'.str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
        $studentId = $this->db->insert(
            "INSERT INTO students (
                tenant_id,user_id,admission_no,class_id,admission_date,status,
                guardian_name,guardian_phone,guardian_relationship,emergency_contact_phone,
                first_name,middle_name,last_name,previous_school,previous_class,admission_type
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tid, $userId, $admNo, $classId ?: null, $admissionDate, 'active',
                $application['guardian_name'], $application['guardian_phone'], $application['guardian_relationship'],
                $application['guardian_phone'],
                $application['first_name'], $application['middle_name'], $application['last_name'],
                $application['previous_school'], $application['previous_class'], 'new',
            ]
        );

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
