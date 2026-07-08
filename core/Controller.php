<?php
require_once dirname(__DIR__) . '/core/Database.php';

abstract class Controller {
    protected Database $db;
    protected array $data = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    protected function startSession(): void {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_name($cfg['session_name']);
            session_start();
        }
    }

    protected function requireAuth(array $allowedRoles = []): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
            $this->redirect('/unauthorized');
        }
    }

    protected function requireSchoolAdmin(): void {
        $this->requireAuth(['School Admin']);
    }

    protected function view(string $viewPath, array $data = []): void {
        // Automatically inject tenant data if it exists in session but not in $data
        if (!isset($data['tenant']) && isset($_SESSION['tenant_id'])) {
            $data['tenant'] = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$_SESSION['tenant_id']]);
        }
        $data['csrf_token'] = $this->csrfToken();

        extract($data);
        $viewFile = dirname(__DIR__) . "/app/Views/{$viewPath}.php";
        if (!file_exists($viewFile)) {
            die("View not found: {$viewPath}");
        }
        require $viewFile;
    }

    protected function redirect(string $url): never {
        $cfg  = require dirname(__DIR__) . '/config/app.php';
        $base = rtrim($cfg['url'], '/');
        if ($this->isAjax()) {
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            $this->json(['redirect' => $base . $url, 'flash' => $flash]);
        }
        header("Location: {$base}{$url}");
        exit;
    }

    protected function isAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    protected function json(mixed $data, int $status = 200): never {
        if (ob_get_level()) { ob_end_clean(); }
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function currentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    protected function tenantId(): ?int {
        return $_SESSION['tenant_id'] ?? null;
    }

    /**
     * Validate $_POST-style data against simple pipe-delimited rules, e.g.
     * ['email' => 'required|email', 'name' => 'required|max:150'].
     * Returns an assoc array of field => first error message (empty if valid).
     */
    protected function validate(array $data, array $rules): array {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $label = ucfirst(str_replace('_', ' ', $field));
            $value = trim((string)($data[$field] ?? ''));
            foreach (explode('|', $ruleStr) as $rule) {
                if ($rule === '') continue;
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'required' && $value === '') {
                    $errors[$field] = "{$label} is required.";
                    break;
                }
                if ($value === '') continue; // remaining rules only apply when a value is present
                switch ($name) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) { $errors[$field] = 'Enter a valid email address.'; break 2; }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) { $errors[$field] = "{$label} must be a number."; break 2; }
                        break;
                    case 'integer':
                        if (!ctype_digit(ltrim($value, '-'))) { $errors[$field] = "{$label} must be a whole number."; break 2; }
                        break;
                    case 'min':
                        if (strlen($value) < (int)$param) { $errors[$field] = "{$label} must be at least {$param} characters."; break 2; }
                        break;
                    case 'max':
                        if (strlen($value) > (int)$param) { $errors[$field] = "{$label} must be at most {$param} characters."; break 2; }
                        break;
                    case 'date':
                        if (strtotime($value) === false) { $errors[$field] = "Enter a valid date for {$label}."; break 2; }
                        break;
                    case 'in':
                        if (!in_array($value, explode(',', (string)$param), true)) { $errors[$field] = "Invalid value for {$label}."; break 2; }
                        break;
                }
            }
        }
        return $errors;
    }

    /**
     * Send validation errors back to the client: JSON for AJAX requests
     * (with per-field messages the modal JS renders inline), or a flash +
     * redirect for normal form posts.
     */
    protected function failValidation(array $errors, string $redirectUrl): never {
        if ($this->isAjax()) {
            $this->json(['error' => 'Please fix the highlighted fields and try again.', 'errors' => $errors], 422);
        }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
        $this->redirect($redirectUrl);
    }

    /**
     * Pagination helper: given a total row count, works out the current
     * page (from $_GET['page']), page size, and SQL OFFSET.
     */
    protected function paginate(int $totalCount, int $perPage = 20): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        return [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'total'      => $totalCount,
            'offset'     => ($page - 1) * $perPage,
        ];
    }
}
