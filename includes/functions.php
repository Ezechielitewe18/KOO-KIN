<?php

declare(strict_types=1);

use KooKin\Core\Config;
use KooKin\Core\Database;
use KooKin\Core\Session;
use KooKin\Core\Flash;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function db(): ?Database
{
    try {
        return Database::instance();
    } catch (\Throwable) {
        return null;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url'), '/');
    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }
    return $base . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function upload_url(string $path): string
{
    return url('uploads/' . ltrim($path, '/'));
}

function format_prix(float|int $montant): string
{
    $devise = (string) (param('devise') ?: 'CDF');
    return number_format((float) $montant, 0, ',', ' ') . ' ' . $devise;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="' . e((string) config('security.csrf_name', '_token')) . '" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $name = (string) config('security.csrf_name', '_token');
    $sent = $_POST[$name] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        Flash::error('Session expirée, veuillez réessayer.');
        redirect('/');
    }
}

function flash_success(): array
{
    return Flash::all('success');
}

function flash_error(): array
{
    return Flash::all('error');
}

function flash_info(): array
{
    return Flash::all('info');
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('_old', []);
    return $old[$key] ?? $default;
}

function keep_old(array $data): void
{
    Session::set('_old', $data);
}

function param(string $cle, mixed $default = null): mixed
{
    $row = db()?->one('SELECT `valeur` FROM parametres_site WHERE `cle` = ?', [$cle]);
    return $row === null ? $default : $row['valeur'];
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function flash_json(string $type, string $message, array $data = []): array
{
    return array_merge(['status' => $type, 'message' => $message], $data);
}

function now_str(): string
{
    return (new DateTime('now', new DateTimeZone(config('app.timezone', 'Africa/Kinshasa'))))->format('Y-m-d H:i:s');
}