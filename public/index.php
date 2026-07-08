<?php
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR',  ROOT_DIR . '/app');
define('CORE_DIR', ROOT_DIR . '/core');

// Autoload core files
foreach (['Database', 'Router', 'Controller', 'Model'] as $cls) {
    require_once CORE_DIR . "/{$cls}.php";
}

// Optional: Load bundled controllers if they exist
if (file_exists(APP_DIR . '/Controllers/AllControllers.php')) {
    require_once APP_DIR . '/Controllers/AllControllers.php';
}

// Load routes
$router = new Router();
require_once ROOT_DIR . '/config/routes.php';
$router->dispatch();
