<?php

declare(strict_types=1);

namespace KooKin\Core;

class ClientAuth
{
    private const KEY = '_client';
    private const RETOUR = '_client_retour';

    public static function attempt(string $telephone, string $password): bool
    {
        $client = db()->one('SELECT id, nom, telephone, email, mot_de_passe_hash FROM clients WHERE telephone = ? LIMIT 1', [$telephone]);

        if ($client === null || empty($client['mot_de_passe_hash'])) {
            return false;
        }

        if (!password_verify($password, $client['mot_de_passe_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::set(self::KEY, [
            'id'        => (int) $client['id'],
            'nom'       => $client['nom'],
            'telephone' => $client['telephone'],
            'email'     => $client['email'] ?? null,
        ]);

        return true;
    }

    /**
     * Enregistre un nouveau compte client (ou récupère un compte
     * d'invité existant pour le même téléphone).
     *
     * @return array{0: bool, 1: ?string} succès, erreur éventuelle
     */
    public static function register(string $nom, string $telephone, ?string $email, string $password): array
    {
        $existant = db()->one('SELECT id, mot_de_passe_hash FROM clients WHERE telephone = ? LIMIT 1', [$telephone]);

        if ($existant !== null && !empty($existant['mot_de_passe_hash'])) {
            return [false, 'Un compte existe déjà avec ce numéro de téléphone. Connectez-vous.'];
        }

        if ($email !== null && $email !== '') {
            $emailExistant = db()->one('SELECT id FROM clients WHERE email = ? AND id <> ? LIMIT 1', [$email, $existant['id'] ?? 0]);
            if ($emailExistant !== null) {
                return [false, 'Cette adresse email est déjà utilisée par un autre compte.'];
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existant !== null) {
            db()->run(
                'UPDATE clients SET nom = ?, email = COALESCE(?, email), mot_de_passe_hash = ? WHERE id = ?',
                [$nom, $email !== '' ? $email : null, $hash, (int) $existant['id']]
            );
            $clientId = (int) $existant['id'];
        } else {
            db()->run(
                'INSERT INTO clients (nom, telephone, email, mot_de_passe_hash) VALUES (?, ?, ?, ?)',
                [$nom, $telephone, $email !== '' ? $email : null, $hash]
            );
            $clientId = (int) db()->lastId();
        }

        Session::regenerate();
        Session::set(self::KEY, [
            'id'        => $clientId,
            'nom'       => $nom,
            'telephone' => $telephone,
            'email'     => $email !== '' ? $email : null,
        ]);

        return [true, null];
    }

    public static function check(): bool
    {
        return Session::has(self::KEY);
    }

    public static function id(): ?int
    {
        $client = Session::get(self::KEY);
        return $client['id'] ?? null;
    }

    public static function client(): ?array
    {
        return Session::get(self::KEY);
    }

    public static function mettreAJour(array $donnees): void
    {
        Session::set(self::KEY, $donnees);
    }

    public static function logout(): void
    {
        Session::forget(self::KEY);
        Session::regenerate();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $rel = self::cheminRetour();
            self::fixerRetour($rel);
            Flash::error('Veuillez vous connecter pour continuer.');
            redirect('connexion.php');
        }
    }

    private static function cheminRetour(): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? 'mon-compte.php');
        $rel = '/' . ltrim(str_replace('\\', '/', $script), '/');
        $basePath = (string) parse_url((string) config('app.url'), PHP_URL_PATH);

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($rel, $basePath)) {
            $rel = substr($rel, strlen($basePath));
        }

        $rel = '/' . ltrim($rel, '/');
        $qs = trim((string) ($_SERVER['QUERY_STRING'] ?? ''));
        if ($qs !== '') {
            $rel .= '?' . $qs;
        }

        return $rel;
    }

    public static function fixerRetour(string $chemin): void
    {
        Session::set(self::RETOUR, $chemin);
    }

    public static function retour(): string
    {
        $retour = (string) Session::get(self::RETOUR, '');
        Session::forget(self::RETOUR);
        return $retour !== '' ? $retour : 'mon-compte.php';
    }
}