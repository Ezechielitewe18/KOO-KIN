<?php

declare(strict_types=1);

namespace KooKin\Core;

class Config
{
    private static ?array $items = null;

    public static function load(array $data): void
    {
        self::$items = $data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$items === null) {
            return $default;
        }
        $segments = explode('.', $key);
        $value = self::$items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}