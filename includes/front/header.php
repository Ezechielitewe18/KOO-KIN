<?php

declare(strict_types=1);

$page = $page ?? [];
$titre = $page['titre'] ?? 'KOO-KIN — Cuisine Congolaise Authentique';
$description = $page['description'] ?? 'Restaurant de cuisine congolaise authentique à Kinshasa (Kintambo). Saveurs de chez nous, livraison et traiteur.';
$actif = $page['actif'] ?? '';
$isHome = $page['home'] ?? false;

$phone = (string) param('telephone', '+243 8xx xxx xxx');
$whatsapp = (string) param('whatsapp', '2438xxxxxxx');

if (config('app.debug')) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

$horaireDuJour = db()->one(
    'SELECT ouverture, fermeture, ferme FROM horaires WHERE id = ? LIMIT 1',
    [(int) date('N')]
);
$statut = 'ferme';
if ($horaireDuJour && (int) $horaireDuJour['ferme'] === 0 && $horaireDuJour['ouverture'] && $horaireDuJour['fermeture']) {
    $now = time();
    $ouvre = strtotime('today ' . $horaireDuJour['ouverture']);
    $ferme = strtotime('today ' . $horaireDuJour['fermeture']);
    if ($ferme < $ouvre) {
        $ferme += 86400;
    }
    $statut = ($now >= $ouvre && $now <= $ferme) ? 'ouvert' : 'ferme';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="theme-color" content="#1f1b14">
    <meta property="og:site_name" content="KOO-KIN">
    <meta property="og:title" content="<?= e($titre) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_CD">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "<?= e((string) param('site_nom', 'KOO-KIN')) ?>",
        "servesCuisine": "Congolaise",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "<?= e((string) param('adresse', '')) ?>",
            "addressLocality": "Kinshasa",
            "addressCountry": "CD"
        },
        "telephone": "<?= e($phone) ?>",
        "openingHours": "Mo-Su 08:00-22:00",
        "priceRange": "CDF"
    }
    </script>
</head>
<body>
<a class="sr-only" href="#contenu">Aller au contenu</a>

<aside class="sidebar" id="sidebar" aria-label="Navigation principale">
    <div class="sidebar__logo">
        <a href="<?= e(url('/')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="KOO-KIN">
        </a>
        <span>Kintambo · Kinshasa</span>
    </div>
    <nav class="sidebar__nav">
        <a href="<?= e(url('/')) ?>" class="<?= $actif === 'accueil' ? 'actif' : '' ?>">Accueil</a>
        <a href="<?= e(url('menu.php')) ?>" class="<?= $actif === 'menu' ? 'actif' : '' ?>">Notre menu</a>
        <a href="<?= e(url('commander.php')) ?>" class="<?= $actif === 'commander' ? 'actif' : '' ?>">Commander</a>
        <a href="<?= e(url('reservation.php')) ?>" class="<?= $actif === 'reservation' ? 'actif' : '' ?>">Réserver</a>
        <a href="<?= e(url('livraison.php')) ?>" class="<?= $actif === 'livraison' ? 'actif' : '' ?>">Livraison</a>
        <a href="<?= e(url('histoire.php')) ?>" class="<?= $actif === 'histoire' ? 'actif' : '' ?>">Notre histoire</a>
        <a href="<?= e(url('galerie.php')) ?>" class="<?= $actif === 'galerie' ? 'actif' : '' ?>">Galerie</a>
        <a href="<?= e(url('traiteur.php')) ?>" class="<?= $actif === 'traiteur' ? 'actif' : '' ?>">Traiteur &amp; événements</a>
        <a href="<?= e(url('promotions.php')) ?>" class="<?= $actif === 'promotions' ? 'actif' : '' ?>">Promotions</a>
        <a href="<?= e(url('contact.php')) ?>" class="<?= $actif === 'contact' ? 'actif' : '' ?>">Contact</a>
    </nav>
    <div class="sidebar__contact">
        <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a>
        <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
        <div class="sidebar__social">
            <a href="<?= e((string) param('facebook', '#')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><?= icone('facebook') ?></a>
            <a href="<?= e((string) param('instagram', '#')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><?= icone('instagram') ?></a>
            <a href="<?= e((string) param('tiktok', '#')) ?>" target="_blank" rel="noopener" aria-label="TikTok"><?= icone('tiktok') ?></a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icone('whatsapp') ?></a>
        </div>
        <p class="statut-ouvert <?= $statut === 'ouvert' ? 'ouvert' : 'ferme' ?>" style="margin:1rem 0 0">
            <span class="point"></span>
            <?= $statut === 'ouvert' ? 'Ouvert maintenant' : 'Fermé actuellement' ?>
        </p>
    </div>
</aside>

<div class="overlay" id="overlay"></div>

<header class="topbar">
    <button class="icon-btn" id="js-burger" aria-label="Ouvrir le menu"><?= icone('menu') ?></button>
    <a class="topbar__brand" href="<?= e(url('/')) ?>"><img src="<?= e(asset('img/logo.svg')) ?>" alt="KOO-KIN"></a>
    <div class="topbar__actions">
        <a href="<?= e(url('reservation.php')) ?>" class="icon-btn" aria-label="Réserver"><?= icone('reserver') ?></a>
        <button class="icon-btn js-panier-ouvrir" aria-label="Panier">
            <?= icone('panier') ?>
            <span class="panier-compteur js-panier-nb"><?= \KooKin\Core\Panier::count() ?></span>
        </button>
    </div>
</header>

<div class="page">
    <main id="contenu">