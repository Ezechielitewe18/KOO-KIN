<?php

declare(strict_types=1);

use KooKin\Core\Auth;

$admin = Auth::user();
$admin_titre = $admin_titre ?? 'Tableau de bord';
$admin_actif = $admin_actif ?? '';

$nb = [
    'commandes'    => (int) db()->value("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'"),
    'reservations' => (int) db()->value("SELECT COUNT(*) FROM reservations WHERE statut = 'en_attente'"),
    'traiteur'     => (int) db()->value("SELECT COUNT(*) FROM demandes_traiteur WHERE statut = 'nouvelle'"),
    'messages'     => (int) db()->value('SELECT COUNT(*) FROM messages WHERE lu = 0'),
];

$navigation = [
    [
        'section' => 'Activité',
        'liens' => [
            ['cle' => 'dashboard', 'url' => 'index.php', 'label' => 'Tableau de bord', 'icone' => 'etoile'],
            ['cle' => 'commandes', 'url' => 'commandes.php', 'label' => 'Commandes', 'icone' => 'panier', 'badge' => $nb['commandes']],
            ['cle' => 'reservations', 'url' => 'reservations.php', 'label' => 'Réservations', 'icone' => 'reserver', 'badge' => $nb['reservations']],
            ['cle' => 'traiteur', 'url' => 'traiteur.php', 'label' => 'Traiteur', 'icone' => 'traiteur', 'badge' => $nb['traiteur']],
            ['cle' => 'messages', 'url' => 'messages.php', 'label' => 'Messages', 'icone' => 'chat', 'badge' => $nb['messages']],
        ],
    ],
    [
        'section' => 'Contenu',
        'liens' => [
            ['cle' => 'plats', 'url' => 'plats.php', 'label' => 'Plats', 'icone' => 'menu'],
            ['cle' => 'categories', 'url' => 'categories.php', 'label' => 'Catégories', 'icone' => 'rechercher'],
            ['cle' => 'menu-jour', 'url' => 'menu-jour.php', 'label' => 'Menu du jour', 'icone' => 'reserver'],
            ['cle' => 'promotions', 'url' => 'promotions.php', 'label' => 'Promotions', 'icone' => 'pinceau'],
            ['cle' => 'galerie', 'url' => 'galerie.php', 'label' => 'Galerie', 'icone' => 'rechercher'],
        ],
    ],
    [
        'section' => 'Configuration',
        'liens' => [
            ['cle' => 'livraisons', 'url' => 'livraisons.php', 'label' => 'Livraison', 'icone' => 'livraison'],
            ['cle' => 'horaires', 'url' => 'horaires.php', 'label' => 'Horaires', 'icone' => 'telephone'],
            ['cle' => 'parametres', 'url' => 'parametres.php', 'label' => 'Paramètres', 'icone' => 'pinceau'],
            ['cle' => 'compte', 'url' => 'compte.php', 'label' => 'Mon compte', 'icone' => 'utilisateur'],
        ],
    ],
];

if (admin_est_superadmin()) {
    $navigation[2]['liens'][] = ['cle' => 'comptes', 'url' => 'comptes.php', 'label' => 'Comptes admin', 'icone' => 'cle'];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($admin_titre) ?> — Administration KOO-KIN</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="adm">
    <aside class="adm-sidebar" id="adm-sidebar">
        <div class="adm-sidebar__logo">
            <a href="<?= e(admin_url('index.php')) ?>">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="KOO-KIN">
            </a>
            <span>Administration</span>
        </div>
        <nav class="adm-nav">
            <?php foreach ($navigation as $groupe): ?>
                <p class="adm-nav__section"><?= e($groupe['section']) ?></p>
                <?php foreach ($groupe['liens'] as $lien): ?>
                    <a href="<?= e(admin_url($lien['url'])) ?>" class="adm-nav__lien <?= $admin_actif === $lien['cle'] ? 'actif' : '' ?>">
                        <span class="adm-nav__ico"><?= icone($lien['icone']) ?></span>
                        <span><?= e($lien['label']) ?></span>
                        <?php if (!empty($lien['badge'])): ?>
                            <span class="adm-nav__badge"><?= (int) $lien['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="adm-sidebar__pied">
            <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener" class="adm-nav__lien">
                <span class="adm-nav__ico"><?= icone('fleche') ?></span>
                <span>Voir le site</span>
            </a>
            <a href="<?= e(admin_url('logout.php')) ?>" class="adm-nav__lien adm-nav__lien--sortie">
                <span class="adm-nav__ico"><?= icone('fermer') ?></span>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <div class="adm-main">
        <header class="adm-topbar">
            <button type="button" class="adm-burger" id="adm-burger" aria-label="Ouvrir le menu"><?= icone('menu') ?></button>
            <h1 class="adm-topbar__titre"><?= e($admin_titre) ?></h1>
            <a href="<?= e(admin_url('compte.php')) ?>" class="adm-topbar__user" title="Mon compte">
                <span><?= e((string) ($admin['username'] ?? 'admin')) ?></span>
                <span class="adm-role"><?= e((string) ($admin['role'] ?? 'admin')) ?></span>
            </a>
        </header>

        <main class="adm-content">
            <?php foreach (flash_success() as $m): ?><div class="adm-alerte adm-alerte--succes"><?= e($m) ?></div><?php endforeach; ?>
            <?php foreach (flash_error() as $m): ?><div class="adm-alerte adm-alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
            <?php foreach (flash_info() as $m): ?><div class="adm-alerte adm-alerte--info"><?= e($m) ?></div><?php endforeach; ?>
