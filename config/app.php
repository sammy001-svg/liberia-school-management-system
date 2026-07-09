<?php
return [
    'name'      => 'Liberia School Management System',
    'url'       => (function () {
        if (!isset($_SERVER['HTTP_HOST'])) { return 'http://localhost:8001'; }
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) == 443
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        return ($isHttps ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    })(),
    'version'   => '1.0.0',
    // IMPORTANT: keep this false in production — true re-throws exceptions with
    // full stack traces instead of showing a friendly error and logging quietly.
    'debug'     => false,
    'timezone'  => 'Africa/Monrovia',
    'upload_dir' => dirname(__DIR__) . '/uploads/',
    'session_name' => 'schoolms_session',
];
