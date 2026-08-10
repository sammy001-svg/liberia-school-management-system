<?php
require_once ROOT_DIR . '/core/Controller.php';

class StaffController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    /**
     * Roles a staff member may be put on: the built-in staff roles, plus every custom role
     * this school created on the Roles & Permissions screen. Without the custom half, a role
     * created there could never actually be assigned to anyone from this page.
     */
    private function assignableRoles(): array {
        return $this->db->fetchAll(
            "SELECT id, name, tenant_id FROM roles
             WHERE (tenant_id IS NULL AND name IN ('Staff','Accountant')) OR tenant_id = ?
             ORDER BY (tenant_id IS NOT NULL), name", [$this->tid]
        );
    }

    /**
     * Full staff data export — every staff-side account including teachers.
     *
     * Includes the username because that (or the email) is what they sign in with,
     * which makes this export usable as the handout when issuing logins. Salary
     * figures are included since this screen already shows them to anyone holding
     * staff.manage.
     */
    public function exportCsv(): void {
        $this->requirePermission(['staff.manage']);
        $rows = $this->db->fetchAll(
            "SELECT COALESCE(t.employee_no, u.employee_no) AS staff_no, u.name, r.name AS role_name,
                    u.username, u.email, u.phone, u.gender, u.position,
                    d.name AS department_name, c.name AS class_name,
                    t.qualification, t.specialization, t.employment_type, t.joined_at,
                    sal.basic_salary, sal.allowances, sal.deductions,
                    (COALESCE(sal.basic_salary,0) + COALESCE(sal.allowances,0) - COALESCE(sal.deductions,0)) AS net_pay,
                    u.status
               FROM users u
               JOIN roles r ON u.role_id = r.id
               LEFT JOIN staff_salaries sal ON sal.user_id = u.id
               LEFT JOIN teachers t ON t.user_id = u.id
               LEFT JOIN departments d ON t.department_id = d.id
               LEFT JOIN classes c ON t.class_id = c.id
              WHERE u.tenant_id = ? AND (r.name IN ('Staff','Accountant','Teacher','School Admin') OR r.tenant_id = ?)
              ORDER BY r.name, u.name",
            [$this->tid, $this->tid]
        );
        $this->downloadCsv('staff_' . date('Y-m-d') . '.csv', [
            'Staff No','Name','Role','Username','Email','Phone','Gender','Position',
            'Department','Homeroom Class','Qualification','Specialization','Employment Type','Joined',
            'Basic Salary','Allowances','Deductions','Net Pay','Status',
        ], $rows);
    }

    public function index(): void {
        $this->requirePermission(['staff.manage']);
        $staff = $this->db->fetchAll(
            "SELECT u.*, r.name AS role_name, sal.basic_salary, sal.allowances, sal.deductions, sal.effective_from,
                    t.id AS teacher_id, COALESCE(t.employee_no, u.employee_no) AS staff_no
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN staff_salaries sal ON sal.user_id = u.id
             LEFT JOIN teachers t ON t.user_id = u.id
             WHERE u.tenant_id = ? AND (r.name IN ('Staff','Accountant','Teacher') OR r.tenant_id = ?)
             ORDER BY u.name",
            [$this->tid, $this->tid]
        );
        $stats = [
            'total' => count($staff),
            'monthlyCost' => array_sum(array_map(fn($s) => (float)($s['basic_salary'] ?? 0) + (float)($s['allowances'] ?? 0) - (float)($s['deductions'] ?? 0), $staff)),
            'noSalary' => count(array_filter($staff, fn($s) => $s['basic_salary'] === null)),
        ];
        $this->view('school/hr/staff/index', [
            'pageTitle' => 'Staff', 'panelType' => 'school', 'staff' => $staff, 'stats' => $stats,
            'assignableRoles' => $this->assignableRoles(), 'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requirePermission(['staff.manage']);
        $errors = $this->validate($_POST, [
            'name'          => 'required|max:150',
            'email'         => 'required|email|max:150',
            'phone'         => 'max:30',
            'role_id'       => 'required',
            'basic_salary'  => 'required|numeric',
            'allowances'    => 'numeric',
            'deductions'    => 'numeric',
            'effective_from'=> 'date',
        ]);

        // Resolve against the assignable set rather than trusting the posted value — this also
        // scopes custom roles to this school, so one tenant can't be put on another's role.
        $role = $this->db->fetchOne(
            "SELECT id FROM roles
             WHERE id = ? AND ((tenant_id IS NULL AND name IN ('Staff','Accountant')) OR tenant_id = ?)",
            [$_POST['role_id'] ?? 0, $this->tid]
        );
        if (!$role) { $errors['role_id'] = 'Select a valid role.'; }
        if ($errors) { $this->failValidation($errors, '/school/staff'); }

        $roleId = $role['id'];
        $pw = password_hash($_POST['password'] ?: 'Staff@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,username,email,phone,gender,employee_no,position,status) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $_POST['name'], $this->generateUniqueUsername($_POST['name'], $this->tid), $_POST['email'],
             $_POST['phone'] ?? '', $_POST['gender'] ?: null, $_POST['employee_no'] ?: null, $_POST['position'] ?: null, 'active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);

        $this->db->insert(
            "INSERT INTO staff_salaries (tenant_id,user_id,basic_salary,allowances,deductions,effective_from) VALUES (?,?,?,?,?,?)",
            [$this->tid, $userId, $_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, $_POST['effective_from'] ?: date('Y-m-d')]
        );

        $this->flash('success', 'Staff account created.');
        $this->redirect('/school/staff');
    }

    public function edit(string $id): void {
        $this->requirePermission(['staff.manage']);
        $staff = $this->db->fetchOne(
            "SELECT u.*, r.name AS role_name, sal.basic_salary, sal.allowances, sal.deductions, sal.effective_from
             FROM users u JOIN roles r ON u.role_id=r.id LEFT JOIN staff_salaries sal ON sal.user_id=u.id
             WHERE u.id=? AND u.tenant_id=? AND (r.name IN ('Staff','Accountant') OR r.tenant_id=?)", [$id, $this->tid, $this->tid]
        );
        if (!$staff) { $this->redirect('/school/staff'); }
        $this->view('school/hr/staff/form', [
            'pageTitle'=>'Edit Staff','panelType'=>'school','staff'=>$staff,
            'assignableRoles'=>$this->assignableRoles(),'flash'=>$this->getFlash(),
        ]);
    }

    public function update(string $id): void {
        $this->requirePermission(['staff.manage']);
        $staff = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$staff) { $this->redirect('/school/staff'); }
        $errors = $this->validate($_POST, [
            'name' => 'required|max:150', 'email' => 'email|max:150',
            'basic_salary' => 'required|numeric', 'allowances' => 'numeric', 'deductions' => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/staff'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,employee_no=?,position=? WHERE id=?",
            [$_POST['name'],$_POST['email']?:null,$_POST['phone']??'',$_POST['gender']??null,$_POST['employee_no']?:null,$_POST['position']?:null,$id]);
        $existing = $this->db->fetchOne("SELECT id FROM staff_salaries WHERE user_id=? AND tenant_id=?", [$id, $this->tid]);
        if ($existing) {
            $this->db->execute("UPDATE staff_salaries SET basic_salary=?,allowances=?,deductions=?,effective_from=? WHERE id=?",
                [$_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, $_POST['effective_from'] ?: date('Y-m-d'), $existing['id']]);
        } else {
            $this->db->insert("INSERT INTO staff_salaries (tenant_id,user_id,basic_salary,allowances,deductions,effective_from) VALUES (?,?,?,?,?,?)",
                [$this->tid, $id, $_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, $_POST['effective_from'] ?: date('Y-m-d')]);
        }
        $this->flash('success', 'Staff details updated.');
        $this->redirect('/school/staff');
    }

    public function delete(string $id): void {
        $this->requirePermission(['staff.delete']);
        $staff = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($staff) {
            $this->db->execute("DELETE FROM staff_salaries WHERE user_id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?", [$id]);
        }
        $this->flash('success', 'Staff member removed.');
        $this->redirect('/school/staff');
    }

    public function resetPassword(string $id): void {
        $this->requirePermission(['staff.manage']);
        $staff = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$staff) { $this->redirect('/school/staff'); }
        $password = $this->generateStrongPassword();
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($password, PASSWORD_BCRYPT), $id]);
        $this->flash('success', "New password: {$password} (write this down, it will not be shown again).");
        $this->redirect('/school/staff');
    }
}
