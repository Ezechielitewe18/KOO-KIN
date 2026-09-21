<?php

declare(strict_types=1);

namespace KooKin\Core;

class Flash
{
    private const PREFIX = '_flash_';

    public static function push(string $type, string $message): void
    {
        Session::set(self::PREFIX . $type, array_merge(self::all($type), [$message]));
    }

    public static function all(string $type): array
    {
        $messages = Session::get(self::PREFIX . $type, []);
        Session::forget(self::PREFIX . $type);
        return is_array($messages) ? $messages : [];
    }

    public static function success(string $message): void
    {
        self::push('success', $message);
    }

    public static function error(string $message): void
    {
        self::push('error', $message);
    }

    public static function info(string $message): void
    {
        self::push('info', $message);
    }
}