<?php
require_once ROOT_DIR . '/core/Controller.php';

class AuthController extends Controller {
    
    // Resolves the current tenant from a custom domain, falling back to "the one active
    // tenant" for single-school deployments (there's no way to know which school a bare,
    // shared login page belongs to otherwise). Returns the full row — callers needing only
    // branding fields can just ignore the rest — so login-mode columns come along for free.
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

    public function loginPage(): void {
        if ($this->isLoggedIn()) {
            $this->redirectByRole();
        }

        $branding = [
            'name' => 'Liberia School Management System',
            'primary_color' => '#10B981',
            'secondary_color' => '#059669',
            'logo' => null
        ];
        $tenant = $this->resolveTenant();
        if ($tenant) {
            $branding['name'] = $tenant['name'];
            $branding['primary_color'] = $tenant['primary_color'];
            $branding['secondary_color'] = $tenant['secondary_color'];
            $branding['logo'] = $tenant['logo'];
        }

        // The school's own carousel announcements. Empty means the view falls back
        // to the built-in slides, so a fresh install still looks finished.
        require_once ROOT_DIR . '/app/Controllers/LoginSlideController.php';
        $slides = LoginSlideController::activeSlides($this->db, $tenant['id'] ?? null);

        $this->view('auth/login', [
            'pageTitle' => 'Login',
            'branding' => $branding,
            'studentLoginMode' => $tenant['student_login_mode'] ?? 'admission_pin',
            'parentLoginMode' => $tenant['parent_login_mode'] ?? 'username_password',
            'slides' => $slides,
            'flash' => $this->getFlash()
        ]);
    }

    public function loginPost(): void {
        $loginType = $_POST['login_type'] ?? 'staff'; // staff | student | parent
        $identifier = trim($_POST['identifier'] ?? '');
        $secret = $_POST['secret'] ?? '';

        if (!$identifier || !$secret) {
            $this->flash('danger', 'Please fill in both fields.');
            $this->redirect('/login');
        }

        $tenant = $this->resolveTenant();
        $tenantId = $tenant['id'] ?? null;
        $studentLoginMode = $tenant['student_login_mode'] ?? 'admission_pin';
        $parentLoginMode = $tenant['parent_login_mode'] ?? 'username_password';

        // $params is built alongside $sql rather than after it, because the branches
        // no longer all bind the identifier exactly once.
        if ($loginType === 'student' && $studentLoginMode === 'admission_pin') {
            $sql = "SELECT u.*, r.name as role_name FROM users u
                    JOIN roles r ON u.role_id = r.id
                    JOIN students s ON s.user_id = u.id
                    WHERE s.admission_no = ? AND u.status = 'active'";
            $params = [$identifier];
        } elseif ($loginType === 'parent' && $parentLoginMode === 'username_password') {
            $sql = "SELECT u.*, r.name as role_name FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.username = ? AND u.status = 'active'";
            $params = [$identifier];
        } else {
            // Email OR username. Teachers imported from a CSV often have no email at
            // all, which previously left them unable to sign in even though the
            // account looked active. This branch also covers student/parent accounts
            // configured for email_password, so both columns are matched there too.
            $sql = "SELECT u.*, r.name as role_name FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE (u.email = ? OR u.username = ?) AND u.status = 'active'";
            $params = [$identifier, $identifier];
        }
        if ($tenantId) { $sql .= " AND u.tenant_id = ?"; $params[] = $tenantId; }

        $user = $this->db->fetchOne($sql, $params);

        if ($user && password_verify($secret, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['user_name']   = $user['name'];
            $_SESSION['user']        = $user;

            // Cache this role's granted "module.action" permissions for the session so
            // access checks don't need a DB hit on every request.
            $perms = $this->db->fetchAll(
                "SELECT p.module, p.action FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?",
                [$user['role_id']]
            );
            $_SESSION['permissions'] = array_map(fn($p) => "{$p['module']}.{$p['action']}", $perms);

            // Anyone with a teachers row is structurally an instructor regardless of their role's
            // permission grants — used to let every instructor take attendance (see Controller::isInstructor()).
            $_SESSION['is_instructor'] = (bool)$this->db->fetchOne(
                "SELECT id FROM teachers WHERE user_id = ? AND tenant_id = ?",
                [$user['id'], $user['tenant_id']]
            );

            // Update last login
            $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

            // Store school branding
            if ($user['tenant_id']) {
                $tenant = $this->db->fetchOne("SELECT name, primary_color, secondary_color, logo FROM tenants WHERE id = ?", [$user['tenant_id']]);
                if ($tenant) {
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

    // Reached when requireAuth() rejects a logged-in user's role for a given page
    // (e.g. an Accountant hitting a Teacher-only URL) — shown inside the same
    // layout/sidebar the user already has, rather than a bare 404.
    public function unauthorized(): void {
        if (!$this->isLoggedIn()) { $this->redirect('/login'); }
        $panelType = match ($_SESSION['role'] ?? '') {
            'Student' => 'student',
            'Parent'  => 'parent',
            default   => 'school',
        };
        $this->view('errors/unauthorized', ['pageTitle' => 'Access Denied', 'panelType' => $panelType]);
    }

    // A student's own secret is a short PIN (not a password) when their school is configured
    // for admission_pin login — this shows a matching PIN-flavored form instead of a "password"
    // one so validation (exact-4-digit vs. min-length) and copy line up with what they typed at login.
    private function isPinAccount(): bool {
        if (empty($_SESSION['student_id'])) { return false; }
        $tenant = $this->db->fetchOne("SELECT student_login_mode FROM tenants WHERE id=?", [$_SESSION['tenant_id'] ?? 0]);
        return ($tenant['student_login_mode'] ?? '') === 'admission_pin';
    }

    private function accountPanelType(): string {
        if (!empty($_SESSION['student_id'])) { return 'student'; }
        if (!empty($_SESSION['parent_id'])) { return 'parent'; }
        return 'school';
    }

    // "My Account": the one page any signed-in user — School Admin included — can change
    // their own sign-in details from. Both the email/username they are identified by and
    // the password behind it live here; the admin-facing equivalent for *other* people's
    // accounts is UserAccountController (Login Accounts).
    public function accountPage(): void {
        if (!$this->isLoggedIn()) { $this->redirect('/login'); }
        $isPin  = $this->isPinAccount();
        $userId = (int)$_SESSION['user_id'];
        $me = $this->db->fetchOne("SELECT id, name, email, username FROM users WHERE id=?", [$userId]);
        $this->view('auth/account', [
            'pageTitle'   => 'My Account',
            'panelType'   => $this->accountPanelType(),
            'isPin'       => $isPin,
            'me'          => $me ?: ['name' => '', 'email' => null, 'username' => null],
            'accountType' => $this->accountTypeOf($userId),
            'loginModes'  => $this->loginModes(),
            'flash'       => $this->getFlash(),
        ]);
    }

    /** Kept so existing "Change Password" links and bookmarks land on the same page. */
    public function changePasswordPage(): void {
        $this->accountPage();
    }

    /**
     * Change the email/username you sign in with. Confirmed with the current password:
     * moving an account to a different address is a credential change, and an unattended
     * session shouldn't be enough to take the account over.
     */
    public function updateLoginDetailsPost(): void {
        if (!$this->isLoggedIn()) { $this->redirect('/login'); }
        $redirectUrl = '/account';
        $userId = (int)$_SESSION['user_id'];

        $email    = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $current  = $_POST['current_secret'] ?? '';
        $label    = $this->isPinAccount() ? 'PIN' : 'password';

        $errors = $this->loginIdentifierErrors($userId, $this->accountTypeOf($userId), $email, $username);
        $user = $this->db->fetchOne("SELECT password_hash FROM users WHERE id=?", [$userId]);
        if ($current === '' || !$user || !password_verify($current, $user['password_hash'])) {
            $errors['current_secret'] = "Enter your current {$label} to confirm this change.";
        }
        if ($errors) { $this->failValidation($errors, $redirectUrl); }

        $this->db->execute(
            "UPDATE users SET email=?, username=? WHERE id=?",
            [$email ?: null, $username ?: null, $userId]
        );
        // The row is cached in the session at login, so the topbar would keep showing the old one.
        $_SESSION['user']['email']    = $email ?: null;
        $_SESSION['user']['username'] = $username ?: null;

        $this->flash('success', 'Your login details were updated. Use them the next time you sign in.');
        $this->redirect($redirectUrl);
    }

    public function changePasswordPost(): void {
        if (!$this->isLoggedIn()) { $this->redirect('/login'); }
        $isPin = $this->isPinAccount();
        $redirectUrl = '/account';

        $current = $_POST['current_secret'] ?? '';
        $new = $_POST['new_secret'] ?? '';
        $confirm = $_POST['confirm_secret'] ?? '';
        $label = $isPin ? 'PIN' : 'password';

        $errors = [];
        if ($current === '') { $errors['current_secret'] = "Enter your current {$label}."; }
        if ($isPin) {
            if (!preg_match('/^\d{4}$/', $new)) { $errors['new_secret'] = 'New PIN must be exactly 4 digits.'; }
        } else {
            if (strlen($new) < 8) { $errors['new_secret'] = 'New password must be at least 8 characters.'; }
        }
        if (!isset($errors['new_secret']) && $new !== $confirm) {
            $errors['confirm_secret'] = "New {$label}s do not match.";
        }
        if (!$errors) {
            $user = $this->db->fetchOne("SELECT password_hash FROM users WHERE id=?", [$_SESSION['user_id']]);
            if (!$user || !password_verify($current, $user['password_hash'])) {
                $errors['current_secret'] = "Current {$label} is incorrect.";
            }
        }
        if ($errors) { $this->failValidation($errors, $redirectUrl); }

        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);
        $this->flash('success', $isPin ? 'PIN changed successfully.' : 'Password changed successfully.');
        $this->redirect($redirectUrl);
    }

    private function redirectByRole(): void {
        // Student/Parent detection is based on the linked-record session values set above
        // (not the role name) so that any custom role a School Admin creates also has a
        // valid landing page rather than falling through to a dead default case.
        if (!empty($_SESSION['student_id'])) {
            $this->redirect('/student/dashboard');
        }
        if (!empty($_SESSION['parent_id'])) {
            $this->redirect('/parent/dashboard');
        }
        $this->redirect('/school/dashboard');
    }

    private function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}
