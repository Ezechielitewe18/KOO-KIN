<?php

declare(strict_types=1);

use KooKin\Core\Config;
use KooKin\Core\Database;
use KooKin\Core\Session;

const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

spl_autoload_register(static function (string $class): void {
    $prefix = 'KooKin\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . 'classes' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Config::load(require BASE_PATH . 'config' . DIRECTORY_SEPARATOR . 'config.php');

date_default_timezone_set(Config::get('app.timezone', 'Africa/Kinshasa'));
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

if (Config::get('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

Session::start(
    Config::get('security.session_name', 'KOOKIN_SESSION'),
    (int) Config::get('security.session_lifetime', 7200)
);

Database::init(Config::get('db'));

require_once BASE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';