<?php
require_once ROOT_DIR . '/core/Controller.php';

class RegistrationController extends Controller {

    public function schoolPage(): void {
        $plans = $this->db->fetchAll("SELECT id, name, description, price_monthly FROM plans WHERE is_active = 1");
        $this->view('auth/register_school', [
            'pageTitle' => 'School Registration',
            'plans' => $plans,
            'flash' => $this->getFlash()
        ]);
    }

    public function resellerPage(): void {
        $plans = $this->db->fetchAll("SELECT id, name, description, price, max_schools FROM reseller_plans WHERE is_active = 1");
        $this->view('auth/register_reseller', [
            'pageTitle' => 'Become a Reseller',
            'plans' => $plans,
            'flash' => $this->getFlash()
        ]);
    }

    public function registerSchool(): void {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $plan_id  = $_POST['plan_id'] ?? null;
        $type     = $_POST['institution_type'] ?? 'high_school';

        if (!$name || !$email || !$password) {
            $this->flash('error', 'All fields are required.');
            $this->redirect('/register/school');
        }

        // Check if email exists
        $exists = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) {
            $this->flash('error', 'Email already registered.');
            $this->redirect('/register/school');
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . time();
        
        try {
            // 1. Create the tenant (school)
            $tenantId = $this->db->insert("INSERT INTO tenants (plan_id, institution_type, name, slug, email, status, trial_ends_at) VALUES (?,?,?,?,?,?,?)", [
                $plan_id, $type, $name, $slug, $email, 'trial', date('Y-m-d', strtotime('+14 days'))
            ]);

            // 2. Create the School Admin user (Role ID 4)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->insert("INSERT INTO users (tenant_id, role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)", [
                $tenantId, 4, $name . ' Admin', $email, $passwordHash, 'active'
            ]);

            $this->flash('success', 'Registration successful! You can now login.');
            $this->redirect('/login');
        } catch (Exception $e) {
            $this->flash('error', 'Registration failed: ' . $e->getMessage());
            $this->redirect('/register/school');
        }
    }

    public function registerReseller(): void {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $planId   = $_POST['reseller_plan_id'] ?? null;

        if (!$name || !$email || !$password || !$planId) {
            $this->flash('error', 'All fields and a package selection are required.');
            $this->redirect('/register/reseller');
        }

        // Check if email exists
        $exists = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) {
            $this->flash('error', 'Email already registered.');
            $this->redirect('/register/reseller');
        }

        $plan = $this->db->fetchOne("SELECT max_schools FROM reseller_plans WHERE id=?", [$planId]);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        
        try {
            // 1. Create the reseller
            $resellerId = $this->db->insert("INSERT INTO resellers (name, slug, email, status, reseller_plan_id, max_schools) VALUES (?,?,?,?,?,?)", [
                $name, $slug, $email, 'pending', $planId, (int)($plan['max_schools'] ?? 5)
            ]);

            // 2. Create the Reseller Owner user (Role ID 2)
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->insert("INSERT INTO users (reseller_id, role_id, name, email, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)", [
                $resellerId, 2, $name . ' Owner', $email, $passwordHash, 'active'
            ]);

            $this->flash('success', 'Application submitted! We will review your reseller account soon.');
            $this->redirect('/login');
        } catch (Exception $e) {
            $this->flash('error', 'Application failed: ' . $e->getMessage());
            $this->redirect('/register/reseller');
        }
    }
}
