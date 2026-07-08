<?php
require_once ROOT_DIR . '/core/Controller.php';

class StaffController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Accountant']);
        $staff = $this->db->fetchAll(
            "SELECT u.*, r.name AS role_name, sal.basic_salary, sal.allowances, sal.deductions
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN staff_salaries sal ON sal.user_id = u.id
             WHERE u.tenant_id = ? AND r.name IN ('Staff','Accountant')
             ORDER BY u.name",
            [$this->tid]
        );
        $this->view('school/hr/staff/index', ['pageTitle' => 'Staff', 'panelType' => 'school', 'staff' => $staff, 'flash' => $this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Accountant']);
        $errors = $this->validate($_POST, [
            'name'          => 'required|max:150',
            'email'         => 'required|email|max:150',
            'phone'         => 'max:30',
            'role'          => 'required|in:Staff,Accountant',
            'basic_salary'  => 'required|numeric',
            'allowances'    => 'numeric',
            'deductions'    => 'numeric',
            'effective_from'=> 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/staff'); }

        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name=? LIMIT 1", [$_POST['role']])['id'];
        $pw = password_hash($_POST['password'] ?: 'Staff@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,status) VALUES (?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $_POST['name'], $_POST['email'], $_POST['phone'] ?? '', $_POST['gender'] ?: null, 'active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [$pw, $userId]);

        $this->db->insert(
            "INSERT INTO staff_salaries (tenant_id,user_id,basic_salary,allowances,deductions,effective_from) VALUES (?,?,?,?,?,?)",
            [$this->tid, $userId, $_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, $_POST['effective_from'] ?: date('Y-m-d')]
        );

        $this->flash('success', 'Staff account created.');
        $this->redirect('/school/staff');
    }
}
