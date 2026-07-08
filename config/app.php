<?php
return [
    'name'      => 'Liberia School Management System',
    'url'       => (isset($_SERVER['HTTP_HOST']) ? (($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) : 'http://localhost:8001'),
    'version'   => '1.0.0',
    'debug'     => true,
    'timezone'  => 'Africa/Nairobi',
    'upload_dir' => dirname(__DIR__) . '/uploads/',
    'session_name' => 'schoolms_session',
];
