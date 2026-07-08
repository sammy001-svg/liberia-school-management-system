<?php
require_once ROOT_DIR . '/core/Controller.php';

class AdminResellerController extends Controller {

    public function index(): void {
        $this->requireSuperAdmin();
        $search   = $_GET['q'] ?? '';
        $where    = "slug != 'platform'";
        $params   = [];
        if ($search) { $where .= " AND (name LIKE ? OR email LIKE ?)"; $params = ["%$search%", "%$search%"]; }
        $resellers = $this->db->fetchAll("SELECT r.*, (SELECT COUNT(*) FROM tenants t WHERE t.reseller_id = r.id) AS school_count FROM resellers r WHERE $where ORDER BY r.created_at DESC", $params);
        $this->view('super_admin/resellers/index', ['pageTitle'=>'Resellers','panelType'=>'admin','resellers'=>$resellers,'search'=>$search,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireSuperAdmin();
        $plans = $this->db->fetchAll("SELECT * FROM reseller_plans WHERE is_active=1");
        $this->view('super_admin/resellers/form', ['pageTitle'=>'Add Reseller','panelType'=>'admin','reseller'=>null,'resellerPlans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireSuperAdmin();
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $planId   = $_POST['reseller_plan_id'] ?? null;

        if (!$name || !$email || !$password || !$planId) {
            $this->flash('error', 'Name, email, password, and package are required.');
            $this->redirect('/admin/resellers/create');
        }

        $plan = $this->db->fetchOne("SELECT max_schools FROM reseller_plans WHERE id=?", [$planId]);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        
        // 1. Create the reseller
        $resellerId = $this->db->insert("INSERT INTO resellers (name, slug, email, phone, domain, primary_color, secondary_color, status, commission_rate, max_schools, reseller_plan_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            $name, $slug, $email, trim($_POST['phone'] ?? ''),
            trim($_POST['domain'] ?? '') ?: null,
            $_POST['primary_color'] ?? '#10B981',
            $_POST['secondary_color'] ?? '#059669',
            $_POST['status'] ?? 'pending',
            $_POST['commission_rate'] ?? 0,
            (int)($plan['max_schools'] ?? 5),
            $planId
        ]);

        // 2. Create the Reseller Owner user (Role ID 2)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->db->insert("INSERT INTO users (reseller_id, role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)", [
            $resellerId, 2, $name . ' Owner', $email, $passwordHash, 'active'
        ]);

        $this->flash('success', 'Reseller and Owner user created successfully.');
        $this->redirect('/admin/resellers');
    }

    public function show(string $id): void {
        $this->requireSuperAdmin();
        $reseller = $this->db->fetchOne("SELECT r.*, rp.name AS plan_name FROM resellers r LEFT JOIN reseller_plans rp ON r.reseller_plan_id=rp.id WHERE r.id=?", [$id]);
        if (!$reseller) { $this->redirect('/admin/resellers'); }
        $schools = $this->db->fetchAll("SELECT * FROM tenants WHERE reseller_id=? ORDER BY created_at DESC", [$id]);
        $this->view('super_admin/resellers/show', ['pageTitle'=>$reseller['name'],'panelType'=>'admin','reseller'=>$reseller,'schools'=>$schools,'flash'=>$this->getFlash()]);
    }

    public function edit(string $id): void {
        $this->requireSuperAdmin();
        $reseller = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?", [$id]);
        $plans = $this->db->fetchAll("SELECT * FROM reseller_plans WHERE is_active=1");
        $this->view('super_admin/resellers/form', ['pageTitle'=>'Edit Reseller','panelType'=>'admin','reseller'=>$reseller,'resellerPlans'=>$plans,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireSuperAdmin();
        $planId = $_POST['reseller_plan_id'] ?? null;
        $plan = $this->db->fetchOne("SELECT max_schools FROM reseller_plans WHERE id=?", [$planId]);

        $this->db->execute("UPDATE resellers SET name=?,email=?,phone=?,domain=?,primary_color=?,secondary_color=?,status=?,commission_rate=?,max_schools=?,reseller_plan_id=? WHERE id=?", [
            $_POST['name'],trim($_POST['email']),trim($_POST['phone']??''),trim($_POST['domain']??'')?:null,
            $_POST['primary_color']??'#10B981',$_POST['secondary_color']??'#059669',
            $_POST['status']??'pending',$_POST['commission_rate']??0,(int)($plan['max_schools']??5),$planId,$id
        ]);
        $this->flash('success','Reseller updated.'); $this->redirect('/admin/resellers');
    }

    public function delete(string $id): void {
        $this->requireSuperAdmin();
        $this->db->execute("DELETE FROM resellers WHERE id=? AND slug!='platform'", [$id]);
        $this->flash('success','Reseller deleted.'); $this->redirect('/admin/resellers');
    }
}
