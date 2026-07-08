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
                array_shift($matches);
                [$controllerClass, $action] = $handler;
                if (!class_exists($controllerClass)) {
                    $cfile = dirname(__DIR__) . "/app/Controllers/{$controllerClass}.php";
                    if (file_exists($cfile)) {
                        require_once $cfile;
                    }
                }
                $ctrl = new $controllerClass();
                call_user_func_array([$ctrl, $action], $matches);
                return;
            }
        }

        http_response_code(404);
        require dirname(__DIR__) . '/app/Views/layouts/404.php';
    }
}
