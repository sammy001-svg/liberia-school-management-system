<?php
require_once ROOT_DIR . '/core/Controller.php';

class AdminSchoolController extends Controller {

    public function index(): void {
        $this->requireSuperAdmin();
        $search = $_GET['q'] ?? '';
        $type   = $_GET['type'] ?? '';
        $where  = "t.slug != 'platform'";
        $params = [];
        if ($search) { $where .= " AND t.name LIKE ?"; $params[] = "%$search%"; }
        if ($type)   { $where .= " AND t.institution_type = ?"; $params[] = $type; }
        $schools = $this->db->fetchAll("SELECT t.*, r.name AS reseller_name, p.name AS plan_name FROM tenants t LEFT JOIN resellers r ON t.reseller_id=r.id LEFT JOIN plans p ON t.plan_id=p.id WHERE $where ORDER BY t.created_at DESC", $params);
        $resellers = $this->db->fetchAll("SELECT id,name FROM resellers WHERE status='active' AND slug!='platform'");
        $plans = $this->db->fetchAll("SELECT id,name FROM plans WHERE is_active=1");
        $this->view('super_admin/schools/index', ['pageTitle'=>'Schools','panelType'=>'admin','schools'=>$schools,'resellers'=>$resellers,'plans'=>$plans,'search'=>$search,'type'=>$type,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireSuperAdmin();
        $resellers = $this->db->fetchAll("SELECT id,name FROM resellers WHERE status='active' AND slug!='platform'");
        $plans     = $this->db->fetchAll("SELECT id,name FROM plans WHERE is_active=1");
        $this->view('super_admin/schools/form', ['pageTitle'=>'Add School','panelType'=>'admin','school'=>null,'resellers'=>$resellers,'plans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireSuperAdmin();
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!$name || !$email || !$password) {
            $this->flash('error', 'Name, email, and password are required.');
            $this->redirect('/admin/schools/create');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();
        
        // 1. Create the tenant (school)
        $tenantId = $this->db->insert("INSERT INTO tenants (reseller_id, plan_id, institution_type, name, slug, email, phone, address, country, status, trial_ends_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            $_POST['reseller_id'] ?: null, $_POST['plan_id'] ?: null, $_POST['institution_type'],
            $name, $slug, $email, trim($_POST['phone'] ?? ''),
            trim($_POST['address'] ?? ''), trim($_POST['country'] ?? ''),
            $_POST['status'] ?? 'pending', $_POST['trial_ends_at'] ?: null
        ]);

        // 2. Create the School Admin user (Role ID 4)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->db->insert("INSERT INTO users (tenant_id, role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)", [
            $tenantId, 4, $name . ' Admin', $email, $passwordHash, 'active'
        ]);

        $this->flash('success', 'School and Admin user created successfully.');
        $this->redirect('/admin/schools');
    }

    public function show(string $id): void {
        $this->requireSuperAdmin();
        $school  = $this->db->fetchOne("SELECT t.*,r.name AS reseller_name,p.name AS plan_name FROM tenants t LEFT JOIN resellers r ON t.reseller_id=r.id LEFT JOIN plans p ON t.plan_id=p.id WHERE t.id=?",[$id]);
        if (!$school) { $this->redirect('/admin/schools'); }
        $students = $this->db->fetchOne("SELECT COUNT(*) AS c FROM students WHERE tenant_id=?",[$id])['c'] ?? 0;
        $teachers = $this->db->fetchOne("SELECT COUNT(*) AS c FROM teachers WHERE tenant_id=?",[$id])['c'] ?? 0;
        $this->view('super_admin/schools/show',['pageTitle'=>$school['name'],'panelType'=>'admin','school'=>$school,'students'=>$students,'teachers'=>$teachers,'flash'=>$this->getFlash()]);
    }

    public function edit(string $id): void {
        $this->requireSuperAdmin();
        $school    = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?",[$id]);
        $resellers = $this->db->fetchAll("SELECT id,name FROM resellers WHERE status='active' AND slug!='platform'");
        $plans     = $this->db->fetchAll("SELECT id,name FROM plans WHERE is_active=1");
        $this->view('super_admin/schools/form',['pageTitle'=>'Edit School','panelType'=>'admin','school'=>$school,'resellers'=>$resellers,'plans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireSuperAdmin();
        $this->db->execute("UPDATE tenants SET reseller_id=?,plan_id=?,institution_type=?,name=?,email=?,phone=?,address=?,country=?,status=? WHERE id=?",[
            $_POST['reseller_id']?:null,$_POST['plan_id']?:null,$_POST['institution_type'],
            $_POST['name'],trim($_POST['email']??''),trim($_POST['phone']??''),
            trim($_POST['address']??''),trim($_POST['country']??''),$_POST['status']??'pending',$id
        ]);
        $this->flash('success','School updated.'); $this->redirect('/admin/schools');
    }

    public function delete(string $id): void {
        $this->requireSuperAdmin();
        $this->db->execute("DELETE FROM tenants WHERE id=? AND slug!='platform'",[$id]);
        $this->flash('success','School deleted.'); $this->redirect('/admin/schools');
    }
}
