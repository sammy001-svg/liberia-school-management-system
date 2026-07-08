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
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => $this->friendlyError($e, false)];
                    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                    exit;
                }
                return;
            }
        }

        http_response_code(404);
        require dirname(__DIR__) . '/app/Views/layouts/404.php';
    }

    private function verifyCsrf(): bool {
        $token = $_POST['csrf_token'] ?? '';
        return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function friendlyError(\Throwable $e, bool $debug): string {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate entry')) {
            return 'That record already exists (duplicate email or unique field).';
        }
        if (str_contains($msg, 'foreign key constraint fails') || str_contains($msg, 'a foreign key constraint fails')) {
            return 'One of the selected options is invalid. Please refresh the page and try again.';
        }
        return $debug ? $msg : 'Something went wrong. Please check your input and try again.';
    }
}
