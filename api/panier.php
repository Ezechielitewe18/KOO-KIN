<?php

declare(strict_types=1);

require __DIR__ . '/../includes/init.php';

use KooKin\Core\Panier;
use KooKin\Core\Validation;

function panier_etat(): array
{
    $items = Panier::items();
    foreach ($items as &$item) {
        $item['photo_url'] = plat_photo($item['photo'] ?? null);
    }
    unset($item);
    return [
        'count' => Panier::count(),
        'total' => Panier::total(),
        'items' => $items,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !is_ajax()) {
    json_response(['status' => 'error', 'message' => 'Requête invalide.'], 400);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
    json_response(['status' => 'error', 'message' => 'Session expirée, actualisez la page.'], 419);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $v = (new Validation())->make($_POST, [
            'id' => ['label' => 'plat', 'rules' => 'required|int'],
        ]);
        if ($v->fails()) {
            json_response(['status' => 'error', 'message' => $v->first()], 422);
        }
        $plat = db()->one('SELECT id, nom, prix, photo FROM plats WHERE id = ? AND disponible = 1', [(int) $_POST['id']]);
        if ($plat === null) {
            json_response(['status' => 'error', 'message' => 'Ce plat n\'est pas disponible.'], 404);
        }
        Panier::add((int) $plat['id'], $plat['nom'], (int) $plat['prix'], max(1, (int) ($_POST['quantite'] ?? 1)), $plat['photo']);
        json_response(flash_json('success', 'Ajouté au panier.', panier_etat()));
        break;

    case 'update':
        $v = (new Validation())->make($_POST, [
            'id' => ['label' => 'plat', 'rules' => 'required|int'],
            'quantite' => ['label' => 'quantité', 'rules' => 'required|int|min_value:1'],
        ]);
        if ($v->fails()) {
            json_response(['status' => 'error', 'message' => $v->first()], 422);
        }
        Panier::update((int) $_POST['id'], (int) $_POST['quantite']);
        json_response(flash_json('success', 'Panier mis à jour.', panier_etat()));
        break;

    case 'remove':
        $v = (new Validation())->make($_POST, [
            'id' => ['label' => 'plat', 'rules' => 'required|int'],
        ]);
        if ($v->fails()) {
            json_response(['status' => 'error', 'message' => $v->first()], 422);
        }
        Panier::remove((int) $_POST['id']);
        json_response(flash_json('success', 'Article retiré du panier.', panier_etat()));
        break;

    case 'clear':
        Panier::clear();
        json_response(flash_json('success', 'Panier vidé.', ['count' => 0, 'total' => 0, 'items' => []]));
        break;

    case 'view':
        json_response(array_merge(['status' => 'ok'], panier_etat()));
        break;

    default:
        json_response(['status' => 'error', 'message' => 'Action inconnue.'], 400);
}