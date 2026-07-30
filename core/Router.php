<?php
class Router {
    private array $routes = [];

    public function get(string $path, array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Smarter base path detection
        $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /public/index.php
        $basePath   = dirname($scriptName);     // e.g., /public
        
        // If the URI doesn't start with the basePath (common in .htaccess rewrites),
        // we should adjust the base to not include the 'public' part if it was rewritten.
        if (strpos($uri, $basePath) !== 0) {
            $basePath = str_replace('/public', '', $basePath);
        }
        
        $path = '/' . ltrim(substr($uri, strlen($basePath)), '/');
        $path = $path === '' ? '/' : $path;

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $path, $matches)) {
                http_response_code(200);
                array_shift($matches);
                [$controllerClass, $action] = $handler;
                if (!class_exists($controllerClass)) {
                    $cfile = dirname(__DIR__) . "/app/Controllers/{$controllerClass}.php";
                    if (file_exists($cfile)) {
                        require_once $cfile;
                    }
                }
                $ctrl   = new $controllerClass();
                $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
                // Buffer output so stray PHP notices/warnings can't corrupt the JSON contract.
                if ($isAjax) {
                    ob_start();
                }

                if ($method === 'POST' && !$this->verifyCsrf()) {
                    if ($isAjax) {
                        if (ob_get_level()) { ob_end_clean(); }
                        http_response_code(403);
                        header('Content-Type: application/json');
                        echo json_encode(['error' => 'Your session expired. Please refresh and try again.']);
                        exit;
                    }
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Your session expired. Please try again.'];
                    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                    exit;
                }

                try {
                    call_user_func_array([$ctrl, $action], $matches);
                    if ($isAjax && ob_get_level()) { ob_end_flush(); }
                } catch (\Throwable $e) {
                    $cfg = require dirname(__DIR__) . '/config/app.php';
                    if ($isAjax) {
                        if (ob_get_level()) { ob_end_clean(); }
                        http_response_code(422);
                        header('Content-Type: application/json');
                        echo json_encode(['error' => $this->friendlyError($e, $cfg['debug'])]);
                        exit;
                    }
                    if ($cfg['debug']) {
                        throw $e;
                    }
                    error_log($e->getMessage());
                    // Never redirect back to HTTP_REFERER here. Authenticated pages send
                    // failures toward /login, and /login sends logged-in users straight back
                    // to their dashboard — so any crash became an infinite redirect loop
                    // ("redirected you too many times") that completely hid the real error.
                    // Render the failure instead, so a broken page is diagnosable.
                    $this->renderError($e);
                }
                return;
            }
        }

        http_response_code(404);
        require dirname(__DIR__) . '/app/Views/layouts/404.php';
    }

    /**
     * Render a terminal error page instead of redirecting. Technical details are shown
     * to a logged-in School Admin (a trusted operator who needs to act on them) or when
     * debug is on; everyone else gets the friendly message only.
     */
    private function renderError(\Throwable $e): never {
        // Drop any half-rendered markup so the error isn't buried inside a broken layout.
        while (ob_get_level()) { ob_end_clean(); }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $showDetail = ($_SESSION['role'] ?? '') === 'School Admin';
        $friendly   = htmlspecialchars($this->friendlyError($e, false));
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>"
           . "<title>Something went wrong</title><style>"
           . "body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;"
           . "align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}"
           . ".box{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:32px;max-width:720px;width:100%}"
           . "h1{margin:0 0 8px;font-size:20px}p{color:#94a3b8;line-height:1.6}"
           . "pre{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;overflow-x:auto;"
           . "font-size:12px;color:#fca5a5;white-space:pre-wrap;word-break:break-word}"
           . "a{color:#38bdf8}</style></head><body><div class='box'>"
           . "<h1>Something went wrong</h1><p>{$friendly}</p>";
        if ($showDetail) {
            echo "<p style='color:#64748b;font-size:12px'>Shown because you are signed in as School Admin:</p><pre>"
               . htmlspecialchars($e->getMessage()) . "\n\n"
               . htmlspecialchars($e->getFile()) . ':' . (int)$e->getLine() . "</pre>";
        }
        echo "<p><a href='javascript:history.back()'>Go back</a></p></div></body></html>";
        exit;
    }

    private function verifyCsrf(): bool {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function friendlyError(\Throwable $e, bool $debug): string {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate entry')) {
            return 'That record already exists.';
        }
        if (str_contains($msg, 'foreign key constraint fails') || str_contains($msg, 'a foreign key constraint fails')) {
            return 'One of the selected options is invalid. Please refresh the page and try again.';
        }
        return $debug ? $msg : 'Something went wrong. Please check your input and try again.';
    }
}
