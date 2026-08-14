<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Login Accounts — one screen where a School Admin can fix any account's sign-in
 * details: the email/username it is identified by, and the password (or PIN) behind it.
 *
 * The per-section reset buttons elsewhere (Staff, Teachers, Students) only ever generate
 * a random secret and can't touch the identifier, so an admin previously had no way to
 * correct a mistyped login email or hand someone a password they chose themselves.
 *
 * Stored passwords are bcrypt hashes: nobody — admin included — can read an existing
 * password back. Setting a new one is the only way to give a user a known secret, which
 * is what the "Set password" action below does.
 */
class UserAccountController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    /** Which linked record a user has decides how their login works, so it's shown and filtered on. */
    private const TYPE_FILTERS = [
        'student' => 's.id IS NOT NULL',
        'parent'  => 'p.id IS NOT NULL',
        'teacher' => 't.id IS NOT NULL',
        'staff'   => 's.id IS NULL AND p.id IS NULL AND t.id IS NULL',
    ];

    private const ACCOUNT_JOINS =
        "FROM users u
         JOIN roles r ON u.role_id = r.id
         LEFT JOIN students s ON s.user_id = u.id
         LEFT JOIN parents  p ON p.user_id = u.id
         LEFT JOIN teachers t ON t.user_id = u.id";

    private const TYPE_CASE =
        "CASE WHEN s.id IS NOT NULL THEN 'student'
              WHEN p.id IS NOT NULL THEN 'parent'
              WHEN t.id IS NOT NULL THEN 'teacher'
              ELSE 'staff' END";

    /** How this school signs students and parents in — decides whether a student's secret is a 4-digit PIN. */
    private function loginModes(): array {
        $tenant = $this->db->fetchOne("SELECT student_login_mode, parent_login_mode FROM tenants WHERE id=?", [$this->tid]);
        return [
            'student' => $tenant['student_login_mode'] ?? 'admission_pin',
            'parent'  => $tenant['parent_login_mode']  ?? 'username_password',
        ];
    }

    public function index(): void {
        $this->requirePermission(['roles.manage']);

        $search = trim($_GET['q'] ?? '');
        $type   = $_GET['type'] ?? '';

        $where  = 'u.tenant_id=?';
        $params = [$this->tid];
        if ($search !== '') {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR s.admission_no LIKE ?)';
            array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
        }
        if (isset(self::TYPE_FILTERS[$type])) {
            $where .= ' AND (' . self::TYPE_FILTERS[$type] . ')';
        }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c " . self::ACCOUNT_JOINS . " WHERE {$where}", $params)['c'];
        $p = $this->paginate($totalCount);

        $users = $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, u.username, u.status, u.last_login,
                    r.name AS role_name, s.admission_no,
                    " . self::TYPE_CASE . " AS account_type
             " . self::ACCOUNT_JOINS . "
             WHERE {$where}
             ORDER BY u.name ASC
             LIMIT {$p['perPage']} OFFSET {$p['offset']}",
            $params
        );

        $counts = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN u.email IS NULL OR u.email='' THEN 1 ELSE 0 END) AS no_email,
                    SUM(CASE WHEN u.last_login IS NULL THEN 1 ELSE 0 END) AS never_signed_in
             " . self::ACCOUNT_JOINS . " WHERE u.tenant_id=?",
            [$this->tid]
        );

        $this->view('school/users/index', [
            'pageTitle' => 'Login Accounts', 'panelType' => 'school',
            'users' => $users, 'search' => $search, 'type' => $type,
            'loginModes' => $this->loginModes(), 'counts' => $counts,
            'page' => $p['page'], 'totalPages' => $p['totalPages'], 'total' => $p['total'], 'perPage' => $p['perPage'],
            'flash' => $this->getFlash(),
        ]);
    }

    /** One account of this school, with its type resolved — or null if it belongs elsewhere. */
    private function findAccount(string $id): ?array {
        $row = $this->db->fetchOne(
            "SELECT u.*, " . self::TYPE_CASE . " AS account_type " . self::ACCOUNT_JOINS . "
             WHERE u.id=? AND u.tenant_id=?",
            [$id, $this->tid]
        );
        return $row ?: null;
    }

    /** True when this account signs in with a 4-digit PIN rather than a password. */
    private function usesPin(array $account): bool {
        return $account['account_type'] === 'student' && $this->loginModes()['student'] === 'admission_pin';
    }

    /**
     * Change what an account signs in *as*. Blank clears a field (stored as NULL, not '',
     * so the per-tenant unique indexes don't collide across every account left empty).
     */
    public function updateCredentials(string $id): void {
        $this->requirePermission(['roles.manage']);
        $redirect = '/school/users';

        $account = $this->findAccount($id);
        if (!$account) { $this->flash('danger', 'Account not found.'); $this->redirect($redirect); }

        $email    = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        $errors = [];
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($username !== '' && !preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)) {
            $errors['username'] = 'Username must be 3–60 characters, using letters, numbers, dot, dash or underscore only.';
        }
        // Clearing the field an account actually signs in with would lock the person out
        // with no way back in, so what may be blank depends on the account type and on
        // how this school has its student/parent login configured.
        $modes = $this->loginModes();
        if ($email === '' && $username === '') {
            if (in_array($account['account_type'], ['staff', 'teacher'], true)) {
                $errors['email'] = 'Staff and teacher accounts need an email or a username to sign in with.';
            } elseif ($account['account_type'] === 'student' && $modes['student'] === 'email_password') {
                $errors['email'] = 'This school has students signing in with an email address, so this account needs one.';
            } elseif ($account['account_type'] === 'parent') {
                $errors[$modes['parent'] === 'username_password' ? 'username' : 'email'] =
                    $modes['parent'] === 'username_password'
                        ? 'This school has parents signing in with a username, so this account needs one.'
                        : 'This school has parents signing in with an email address, so this account needs one.';
            }
        } elseif ($account['account_type'] === 'parent' && $modes['parent'] === 'username_password' && $username === '') {
            $errors['username'] = 'This school has parents signing in with a username, so this account needs one.';
        }
        if ($email !== '' && $this->identifierTaken('email', $email, (int)$account['id'])) {
            $errors['email'] = 'Another account at this school already uses that email.';
        }
        if ($username !== '' && $this->identifierTaken('username', $username, (int)$account['id'])) {
            $errors['username'] = 'Another account at this school already uses that username.';
        }
        if ($errors) { $this->failValidation($errors, $redirect); }

        $this->db->execute(
            "UPDATE users SET email=?, username=? WHERE id=? AND tenant_id=?",
            [$email ?: null, $username ?: null, $account['id'], $this->tid]
        );

        // The signed-in user's own row is cached in the session at login, so an admin
        // editing their own account would otherwise keep seeing the old email in the topbar.
        if ((int)$account['id'] === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['user']['email']    = $email ?: null;
            $_SESSION['user']['username'] = $username ?: null;
        }

        $this->flash('success', 'Login details updated for ' . $account['name'] . '.');
        $this->redirect($redirect);
    }

    private function identifierTaken(string $column, string $value, int $exceptUserId): bool {
        return (bool)$this->db->fetchOne(
            "SELECT id FROM users WHERE {$column}=? AND tenant_id=? AND id<>?",
            [$value, $this->tid, $exceptUserId]
        );
    }

    /** Set a password (or PIN) the admin chooses, so it can be handed to the user directly. */
    public function setPassword(string $id): void {
        $this->requirePermission(['roles.manage']);
        $redirect = '/school/users';

        $account = $this->findAccount($id);
        if (!$account) { $this->flash('danger', 'Account not found.'); $this->redirect($redirect); }

        $isPin   = $this->usesPin($account);
        $label   = $isPin ? 'PIN' : 'password';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = [];
        if ($isPin) {
            if (!preg_match('/^\d{4}$/', $new)) { $errors['new_password'] = 'PIN must be exactly 4 digits.'; }
        } elseif (strlen($new) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        }
        if (!isset($errors['new_password']) && $new !== $confirm) {
            $errors['confirm_password'] = "The two {$label}s do not match.";
        }
        if ($errors) { $this->failValidation($errors, $redirect); }

        $this->db->execute(
            "UPDATE users SET password_hash=? WHERE id=? AND tenant_id=?",
            [password_hash($new, PASSWORD_BCRYPT), $account['id'], $this->tid]
        );

        // Deliberately not echoed back: the admin just typed it, and flash messages
        // are rendered on the next page anyone might be looking at.
        $this->flash('success', ucfirst($label) . ' set for ' . $account['name'] . '. Their old ' . $label . ' no longer works.');
        $this->redirect($redirect);
    }

    /** Random replacement secret, shown once — for when the admin has no particular password in mind. */
    public function generatePassword(string $id): void {
        $this->requirePermission(['roles.manage']);
        $redirect = '/school/users';

        $account = $this->findAccount($id);
        if (!$account) { $this->flash('danger', 'Account not found.'); $this->redirect($redirect); }

        $isPin  = $this->usesPin($account);
        $secret = $isPin ? $this->generateUniquePin() : $this->generateStrongPassword();
        $this->db->execute(
            "UPDATE users SET password_hash=? WHERE id=? AND tenant_id=?",
            [password_hash($secret, PASSWORD_BCRYPT), $account['id'], $this->tid]
        );

        $label = $isPin ? 'PIN' : 'password';
        $this->flash('success', "New {$label} for {$account['name']}: {$secret} — write this down, it will not be shown again.");
        $this->redirect($redirect);
    }
}
