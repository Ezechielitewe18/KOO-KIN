<?php

declare(strict_types=1);

const APP_NAME   = 'KOO-KIN';
const APP_SLOGAN = 'Cuisine Congolaise Authentique';

$env = getenv('KOOKIN_ENV') ?: 'local';

$config = [
    'env'  => $env,
    'debug' => true,
    'app'  => [
        'name'   => APP_NAME,
        'url'    => 'http://localhost/KOO-KIN',
        'timezone' => 'Africa/Kinshasa',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'kookin',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'paths' => [
        'uploads'  => BASE_PATH . 'uploads',
        'logs'     => BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'logs',
    ],
    'security' => [
        'session_name'   => 'KOOKIN_SESSION',
        'session_lifetime' => 7200,
        'csrf_name'      => '_token',
        'max_upload_mb'  => 4,
    ],
];

return $config;