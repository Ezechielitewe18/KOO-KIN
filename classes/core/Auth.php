<?php

declare(strict_types=1);

namespace KooKin\Core;

class Auth
{
    private const KEY = '_admin';
    private const ACTIVITE = '_admin_activite';

    public static function attempt(string $username, string $password): bool
    {
        $admin = db()->one('SELECT * FROM admins WHERE username = ? LIMIT 1', [$username]);

        if ($admin === null || (int) $admin['actif'] !== 1) {
            return false;
        }

        if (self::estBloque($admin)) {
            return false;
        }

        if (!password_verify($password, $admin['password_hash'])) {
            self::enregistrerEchec($admin);
            return false;
        }

        self::reinitialiserTentatives((int) $admin['id']);
        Session::regenerate();
        Session::set(self::KEY, [
            'id'       => (int) $admin['id'],
            'username' => $admin['username'],
            'role'     => $admin['role'],
        ]);
        Session::set(self::ACTIVITE, time());

        return true;
    }

    public static function bloque(string $username): bool
    {
        $admin = db()->one('SELECT * FROM admins WHERE username = ? LIMIT 1', [$username]);
        return $admin !== null && self::estBloque($admin);
    }

    public static function check(): bool
    {
        return Session::has(self::KEY);
    }

    public static function id(): ?int
    {
        $admin = Session::get(self::KEY);
        return $admin['id'] ?? null;
    }

    public static function user(): ?array
    {
        return Session::get(self::KEY);
    }

    public static function estSuperadmin(): bool
    {
        $admin = self::user();
        return ($admin['role'] ?? '') === 'superadmin';
    }

    public static function changerMotDePasse(int $id, string $motDePasse): void
    {
        db()->run(
            'UPDATE admins SET password_hash = ?, tentatives_echouees = 0, bloque_jusqua = NULL WHERE id = ?',
            [password_hash($motDePasse, PASSWORD_DEFAULT), $id]
        );
    }

    public static function verifier(): bool
    {
        if (!self::check()) {
            return false;
        }

        $derniere = (int) Session::get(self::ACTIVITE, 0);
        $limite = (int) config('security.session_lifetime', 7200);
        if ($derniere > 0 && (time() - $derniere) > $limite) {
            self::logout();
            return false;
        }

        $admin = db()->one('SELECT id, username, role, actif FROM admins WHERE id = ? LIMIT 1', [self::id()]);
        if ($admin === null || (int) $admin['actif'] !== 1) {
            self::logout();
            return false;
        }

        Session::set(self::KEY, [
            'id'       => (int) $admin['id'],
            'username' => $admin['username'],
            'role'     => $admin['role'],
        ]);
        Session::set(self::ACTIVITE, time());

        return true;
    }

    public static function logout(): void
    {
        Session::forget(self::KEY);
        Session::forget(self::ACTIVITE);
        Session::regenerate();
    }

    public static function requireLogin(string $redirect = '/admin/login.php'): void
    {
        if (!self::verifier()) {
            Flash::error('Veuillez vous connecter pour accéder à l\'administration.');
            redirect($redirect);
        }
    }

    public static function requireSuperadmin(string $redirect = '/admin/index.php'): void
    {
        self::requireLogin();
        if (!self::estSuperadmin()) {
            Flash::error('Vous n\'avez pas les droits nécessaires pour accéder à cette section.');
            redirect($redirect);
        }
    }

    private static function estBloque(array $admin): bool
    {
        if (empty($admin['bloque_jusqua'])) {
            return false;
        }
        return strtotime((string) $admin['bloque_jusqua']) > time();
    }

    private static function enregistrerEchec(array $admin): void
    {
        $max = (int) config('security.max_tentatives', 5);
        $minutes = (int) config('security.blocage_minutes', 15);
        $tentatives = (int) $admin['tentatives_echouees'] + 1;

        if ($tentatives >= $max) {
            db()->run(
                'UPDATE admins SET tentatives_echouees = 0, bloque_jusqua = ? WHERE id = ?',
                [date('Y-m-d H:i:s', time() + $minutes * 60), (int) $admin['id']]
            );
            return;
        }

        db()->run('UPDATE admins SET tentatives_echouees = ? WHERE id = ?', [$tentatives, (int) $admin['id']]);
    }

    private static function reinitialiserTentatives(int $id): void
    {
        db()->run('UPDATE admins SET tentatives_echouees = 0, bloque_jusqua = NULL WHERE id = ?', [$id]);
    }
}
