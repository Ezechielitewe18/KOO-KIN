<?php

declare(strict_types=1);

require __DIR__ . '/../../includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Upload;

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function admin_redirect(string $path): never
{
    header('Location: ' . admin_url($path));
    exit;
}

function admin_est_superadmin(): bool
{
    $u = Auth::user();
    return ($u['role'] ?? '') === 'superadmin';
}

function admin_nombre(int|float $n): string
{
    return number_format((float) $n, 0, ',', ' ');
}

function slugifier(string $texte): string
{
    $texte = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte) ?: $texte;
    $texte = strtolower($texte);
    $texte = preg_replace('/[^a-z0-9]+/', '-', $texte) ?? '';
    return trim($texte, '-') ?: 'element';
}

function slug_unique(string $base, string $table, ?int $ignoreId = null): string
{
    $slug = slugifier($base);
    $essai = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE slug = ?";
        $params = [$essai];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }
        if ((int) db()->value($sql, $params) === 0) {
            return $essai;
        }
        $essai = $slug . '-' . $i++;
    }
}

function admin_upload(string $champ, string $dossier): ?string
{
    if (empty($_FILES[$champ]['name']) || ($_FILES[$champ]['error'] ?? 4) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    return Upload::image($_FILES[$champ], $dossier, (int) config('security.max_upload_mb', 4));
}

function admin_supprimer_image(?string $photo): void
{
    if ($photo !== null && $photo !== '') {
        Upload::delete($photo);
    }
}

function admin_entier(mixed $valeur, int $defaut = 0): int
{
    return filter_var($valeur, FILTER_VALIDATE_INT) !== false ? (int) $valeur : $defaut;
}
