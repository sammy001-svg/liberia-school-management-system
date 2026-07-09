<?php
require_once ROOT_DIR . '/core/Controller.php';

class StaffController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Accountant']);
        $staff = $this->db->fetchAll(
            "SELECT u.*, r.name AS role_name, sal.basic_salary, sal.allowances, sal.deductions,
                    t.id AS teacher_id, COALESCE(t.employee_no, u.employee_no) AS staff_no
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN staff_salaries sal ON sal.user_id = u.id
             LEFT JOIN teachers t ON t.user_id = u.id
             WHERE u.tenant_id = ? AND r.name IN ('Staff','Accountant','Teacher')
             ORDER BY u.name",
            [$this->tid]
        );
        $stats = [
            'total' => count($staff),
            'monthlyCost' => array_sum(array_map(fn($s) => (float)($s['basic_salary'] ?? 0) + (float)($s['allowances'] ?? 0) - (float)($s['deductions'] ?? 0), $staff)),
            'noSalary' => count(array_filter($staff, fn($s) => $s['basic_salary'] === null)),
        ];
        $this->view('school/hr/staff/index', ['pageTitle' => 'Staff', 'panelType' => 'school', 'staff' => $staff, 'stats' => $stats, 'flash' => $this->getFlash()]);
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
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,employee_no,position,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid, $roleId, $_POST['name'], $_POST['email'], $_POST['phone'] ?? '', $_POST['gender'] ?: null, $_POST['employee_no'] ?: null, $_POST['position'] ?: null, 'active']
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
        $this->requireAuth(['School Admin','Accountant']);
        $staff = $this->db->fetchOne(
            "SELECT u.*, r.name AS role_name, sal.basic_salary, sal.allowances, sal.deductions, sal.effective_from
             FROM users u JOIN roles r ON u.role_id=r.id LEFT JOIN staff_salaries sal ON sal.user_id=u.id
             WHERE u.id=? AND u.tenant_id=? AND r.name IN ('Staff','Accountant')", [$id, $this->tid]
        );
        if (!$staff) { $this->redirect('/school/staff'); }
        $this->view('school/hr/staff/form', ['pageTitle'=>'Edit Staff','panelType'=>'school','staff'=>$staff,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin','Accountant']);
        $staff = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$staff) { $this->redirect('/school/staff'); }
        $errors = $this->validate($_POST, [
            'name' => 'required|max:150', 'email' => 'required|email|max:150',
            'basic_salary' => 'required|numeric', 'allowances' => 'numeric', 'deductions' => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/staff/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,employee_no=?,position=? WHERE id=?",
            [$_POST['name'],$_POST['email'],$_POST['phone']??'',$_POST['gender']??null,$_POST['employee_no']?:null,$_POST['position']?:null,$id]);
        $existing = $this->db->fetchOne("SELECT id FROM staff_salaries WHERE user_id=? AND tenant_id=?", [$id, $this->tid]);
        if ($existing) {
            $this->db->execute("UPDATE staff_salaries SET basic_salary=?,allowances=?,deductions=? WHERE id=?",
                [$_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, $existing['id']]);
        } else {
            $this->db->insert("INSERT INTO staff_salaries (tenant_id,user_id,basic_salary,allowances,deductions,effective_from) VALUES (?,?,?,?,?,?)",
                [$this->tid, $id, $_POST['basic_salary'], $_POST['allowances'] ?: 0, $_POST['deductions'] ?: 0, date('Y-m-d')]);
        }
        $this->flash('success', 'Staff details updated.');
        $this->redirect('/school/staff');
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin']);
        $staff = $this->db->fetchOne("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($staff) {
            $this->db->execute("DELETE FROM staff_salaries WHERE user_id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?", [$id]);
        }
        $this->flash('success', 'Staff member removed.');
        $this->redirect('/school/staff');
    }
}
