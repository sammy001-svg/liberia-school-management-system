<?php
require_once ROOT_DIR . '/core/Controller.php';

class AuthController extends Controller {
    
    public function loginPage(): void {
        if ($this->isLoggedIn()) {
            $this->redirectByRole();
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $branding = [
            'name' => 'Liberia School Management System',
            'primary_color' => '#10B981',
            'secondary_color' => '#059669',
            'logo' => null
        ];

        // Check if the current domain matches a custom domain in tenants
        $tenant = $this->db->fetchOne("SELECT name, primary_color, secondary_color, logo FROM tenants WHERE domain = ? AND status = 'active' LIMIT 1", [$host]);

        if ($tenant) {
            $branding['name'] = $tenant['name'];
            $branding['primary_color'] = $tenant['primary_color'];
            $branding['secondary_color'] = $tenant['secondary_color'];
            $branding['logo'] = $tenant['logo'];
        }

        $this->view('auth/login', [
            'pageTitle' => 'Login',
            'branding' => $branding,
            'flash' => $this->getFlash()
        ]);
    }

    public function loginPost(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $this->flash('danger', 'Please enter email and password.');
            $this->redirect('/login');
        }

        $user = $this->db->fetchOne(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.email = ? AND u.status = 'active'", 
            [$email]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['user_name']   = $user['name'];
            $_SESSION['user']        = $user;

            // Update last login
            $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            // Store institution type and school branding
            if ($user['tenant_id']) {
                $tenant = $this->db->fetchOne("SELECT institution_type, name, primary_color, secondary_color, logo FROM tenants WHERE id = ?", [$user['tenant_id']]);
                if ($tenant) {
                    $_SESSION['institution_type'] = $tenant['institution_type'] ?? 'high_school';
                    $_SESSION['branding'] = [
                        'name' => $tenant['name'],
                        'primary_color' => $tenant['primary_color'],
                        'secondary_color' => $tenant['secondary_color'],
                        'logo' => $tenant['logo'],
                    ];
                }
            }

            // Store Student/Parent specific IDs
            if ($user['role_name'] === 'Student') {
                $student = $this->db->fetchOne("SELECT id, class_id FROM students WHERE user_id = ?", [$user['id']]);
                $_SESSION['student_id'] = $student['id'] ?? null;
                $_SESSION['class_id']   = $student['class_id'] ?? null;
            } elseif ($user['role_name'] === 'Parent') {
                $parent = $this->db->fetchOne("SELECT id FROM parents WHERE user_id = ?", [$user['id']]);
                $_SESSION['parent_id'] = $parent['id'] ?? null;
            }

            $this->redirectByRole();
        } else {
            $this->flash('danger', 'Invalid credentials or account inactive.');
            $this->redirect('/login');
        }
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('/login');
    }

    private function redirectByRole(): void {
        $role = $_SESSION['role'] ?? '';
        
        switch ($role) {
            case 'School Admin':
            case 'Teacher':
            case 'Accountant':
            case 'Staff':
                $this->redirect('/school/dashboard');
                break;
            case 'Student':
                $this->redirect('/student/dashboard');
                break;
            case 'Parent':
                $this->redirect('/parent/dashboard');
                break;
            default:
                $this->redirect('/login');
                break;
        }
    }

    private function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}
