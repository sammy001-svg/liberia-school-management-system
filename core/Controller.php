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
}
