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

function image_url(?string $path, ?string $defaut = null): string
{
    if ($path !== null && $path !== '' && is_file(rtrim((string) config('paths.uploads'), '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\'))) {
        return upload_url($path);
    }
    return url('assets/img/placeholders/' . ($defaut ?? 'plat.svg'));
}

function plat_photo(?string $photo): string
{
    return image_url($photo, 'plat.svg');
}

function hero_photo(?string $photo): string
{
    return image_url($photo, 'hero.svg');
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

function icone(string $nom, string $classe = ''): string
{
    $svg = [
        'menu' => '<line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'panier' => '<path d="M6 2 4 7v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7l-2-5Z"/><path d="M4 7h16"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'reserver' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/><path d="M11 14l2 2 3-3"/>',
        'rechercher' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'livraison' => '<path d="M4 13l2-4h8l2 2h3a1 1 0 0 1 1 1v2a6 6 0 0 1-6 6H8a6 6 0 0 1-4-3Z"/><path d="M2 17v2a2 2 0 0 0 2 2h4M16 7h1a3 3 0 0 1 3 3v1"/>',
        'traiteur' => '<path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 8v4l3 2"/><path d="M17 3h4v4"/>',
        'chat' => '<path d="M21 12a8.8 8.8 0 0 1-5.4 8.1L3 22l1.9-6A8 8 0 1 1 21 12Z"/>',
        'telephone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/>',
        'facebook' => '<path d="M13.5 9H16l.5-3H13V5.7C13 5 13.3 4.5 14.4 4.5H16.5V1.5C15.5 1.4 14.5 1.4 13.6 1.4c-2.7 0-3.6 1.6-3.6 3.6V6H7.5v3H10v12h3.5Z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.2-3.5"/><path d="M14 4c.7 2.4 2.5 4 5 4.3"/>',
        'whatsapp' => '<path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.6L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5Z"/><path d="M9 10.5c.5.5 1.5 2.5 3 4s3.5 2.5 4 3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'moins' => '<path d="M5 12h14"/>',
        'fermer' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'fleche' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'verifier' => '<path d="M20 6 9 17l-5-5"/>',
        'etoile' => '<path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1Z" fill="currentColor" stroke="none"/>',
        'pinceau' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19H4v-3L16.5 3.5Z"/>',
        'plat' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/>',
        'boisson' => '<path d="M6 3h12l-1.5 16a2 2 0 0 1-2 1.8H9.5a2 2 0 0 1-2-1.8Z"/><path d="M6.4 9h11.2"/>',
        'grill' => '<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-9-1.5 2-3 3-4 5-1-1-1.5-2-1.5-3C7 10 5 12 5 15a7 7 0 0 0 7 7Z"/>',
        'dessert' => '<path d="M8 11a4 4 0 0 1 8 0"/><path d="M8 11h8l-4 10Z"/><path d="M12 5V3"/>',
        'accompagnement' => '<path d="M3 12h18"/><path d="M5 12a7 7 0 0 0 14 0"/><path d="M2 21h20"/>',
    ];

    $corps = $svg[$nom] ?? $svg['fleche'];
    $visuel = str_contains($corps, 'fill="currentColor"') ? '' : 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';
    $cls = $classe !== '' ? ' class="' . e($classe) . '"' : '';

    return '<svg' . $cls . ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" ' . $visuel . '>' . $corps . '</svg>';
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