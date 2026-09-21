<?php

declare(strict_types=1);

namespace KooKin\Core;

class Panier
{
    private const KEY = '_panier';

    public static function add(int $platId, string $nom, int $prix, int $quantite = 1, ?string $photo = null): void
    {
        $items = self::items();
        $found = false;

        foreach ($items as $i => $item) {
            if ((int) $item['id'] === $platId) {
                $items[$i]['quantite'] += max(1, $quantite);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $items[] = [
                'id' => $platId,
                'nom' => $nom,
                'prix' => (int) $prix,
                'photo' => $photo,
                'quantite' => max(1, $quantite),
            ];
        }

        Session::set(self::KEY, $items);
    }

    public static function update(int $platId, int $quantite): void
    {
        $items = self::items();
        foreach ($items as $i => $item) {
            if ((int) $item['id'] === $platId) {
                if ($quantite <= 0) {
                    unset($items[$i]);
                } else {
                    $items[$i]['quantite'] = $quantite;
                }
                break;
            }
        }
        Session::set(self::KEY, array_values($items));
    }

    public static function remove(int $platId): void
    {
        $items = array_values(array_filter(self::items(), fn($item) => (int) $item['id'] !== $platId));
        Session::set(self::KEY, $items);
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    public static function items(): array
    {
        $items = Session::get(self::KEY, []);
        return is_array($items) ? $items : [];
    }

    public static function count(): int
    {
        return array_sum(array_column(self::items(), 'quantite'));
    }

    public static function total(): int
    {
        return array_sum(array_map(
            fn($item) => (int) $item['prix'] * (int) $item['quantite'],
            self::items()
        ));
    }

    public static function isEmpty(): bool
    {
        return self::items() === [];
    }
}