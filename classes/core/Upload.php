<?php

declare(strict_types=1);

namespace KooKin\Core;

use RuntimeException;

class Upload
{
    private const ALLOWED = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private function __construct()
    {
    }

    public static function image(array $file, string $subdir, int $maxMb = 4): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Aucun fichier reçu.');
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Erreur lors du téléversement du fichier.');
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            throw new RuntimeException('Le fichier dépasse la taille maximale de ' . $maxMb . ' Mo.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true)) {
            throw new RuntimeException('Format de fichier non autorisé (jpg, png, webp, gif uniquement).');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Le fichier n\'est pas une image valide.');
        }

        $targetDir = rtrim((string) config('paths.uploads'), '/\\') . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            throw new RuntimeException('Impossible de créer le dossier de destination.');
        }

        $filename = uniqid('kookin_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $targetDir . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('Impossible d\'enregistrer le fichier.');
        }

        return rtrim($subdir, '/') . '/' . $filename;
    }

    public static function delete(string $relativePath): void
    {
        if ($relativePath === '') {
            return;
        }
        $full = rtrim((string) config('paths.uploads'), '/\\') . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
        if (is_file($full)) {
            unlink($full);
        }
    }
}