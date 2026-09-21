<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$checks = [
    'PHP'         => PHP_VERSION,
    'Namespace'   => class_exists(\KooKin\Core\Database::class) ? 'OK' : 'KO',
    'Connexion DB'=> db() ? 'OK' : 'KO',
    'Paramètres'  => param('site_nom', '—'),
    'Panier'      => \KooKin\Core\Panier::count() . ' article(s)',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KOO-KIN — Vérification</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #faf7ef; color: #1a1712; padding: 2rem; }
        h1 { color: #a67c00; }
        table { border-collapse: collapse; margin-top: 1rem; }
        td, th { border: 1px solid #ddd; padding: .5rem 1rem; text-align: left; }
    </style>
</head>
<body>
    <h1>KOO-KIN — Vérification de l'installation</h1>
    <p>Structure du projet opérationnelle.</p>
    <table>
        <tr><th>Étape</th><th>Résultat</th></tr>
        <?php foreach ($checks as $k => $v): ?>
        <tr><td><?= e($k) ?></td><td><?= e((string) $v) ?></td></tr>
        <?php endforeach; ?>
    </table>
</body>
</html>