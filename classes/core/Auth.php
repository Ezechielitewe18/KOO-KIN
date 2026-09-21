<?php

declare(strict_types=1);

namespace KooKin\Core;

class Auth
{
    private const KEY = '_admin';

    public static function attempt(string $username, string $password): bool
    {
        $admin = db()->one(
            'SELECT * FROM admins WHERE username = ? AND actif = 1 LIMIT 1',
            [$username]
        );

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        Session::regenerate();
        Session::set(self::KEY, [
            'id'       => (int) $admin['id'],
            'username' => $admin['username'],
            'role'     => $admin['role'],
        ]);

        return true;
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

    public static function logout(): void
    {
        Session::forget(self::KEY);
        Session::regenerate();
    }

    public static function requireLogin(string $redirect = '/admin/login.php'): void
    {
        if (!self::check()) {
            Flash::error('Veuillez vous connecter pour accéder à l\'administration.');
            redirect($redirect);
        }
    }
}