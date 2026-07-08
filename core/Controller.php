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

    protected function requireSuperAdmin(): void {
        $this->requireAuth(['Super Admin']);
    }

    protected function requireReseller(): void {
        $this->requireAuth(['Reseller', 'Reseller Owner', 'Reseller Staff', 'Super Admin']);
    }

    protected function requireSchoolAdmin(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
    }

    protected function view(string $viewPath, array $data = []): void {
        // Automatically inject tenant data if it exists in session but not in $data
        if (!isset($data['tenant']) && isset($_SESSION['tenant_id'])) {
            $data['tenant'] = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$_SESSION['tenant_id']]);
        }
        // Automatically inject reseller data if it exists in session
        if (!isset($data['reseller']) && isset($_SESSION['reseller_id'])) {
            $data['reseller'] = $this->db->fetchOne("SELECT * FROM resellers WHERE id=?", [$_SESSION['reseller_id']]);
        }

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
        header("Location: {$base}{$url}");
        exit;
    }

    protected function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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
