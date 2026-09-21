<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$sections = [
    'plats'       => 'Nos plats',
    'restaurant'  => 'Le restaurant',
    'preparation' => 'En cuisine',
    'evenements'  => 'Événements',
    'traiteur'    => 'Traiteur',
];

$section = trim((string) ($_GET['section'] ?? ''));
if (!isset($sections[$section])) {
    $section = '';
}

if ($section !== '') {
    $images = db()->all('SELECT * FROM galerie WHERE actif = 1 AND section = ? ORDER BY ordre, id DESC', [$section]);
} else {
    $images = db()->all('SELECT * FROM galerie WHERE actif = 1 ORDER BY ordre, id DESC');
}

$page = [
    'titre' => 'Galerie — KOO-KIN',
    'actif' => 'galerie',
    'description' => 'Découvrez KOO-KIN en images : nos plats, le restaurant, la préparation et nos événements.',
    'fil' => 'Galerie',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if ($sections): ?>
            <div class="grille-categories animer" style="margin-bottom:2.2rem">
                <a href="<?= e(url('galerie.php')) ?>" class="puce-cat <?= $section === '' ? 'actif' : '' ?>">Tout</a>
                <?php foreach ($sections as $cle => $libelle): ?>
                    <a href="<?= e(url('galerie.php?section=' . urlencode($cle))) ?>" class="puce-cat <?= $section === $cle ? 'actif' : '' ?>"><?= e($libelle) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($images): ?>
            <div class="galerie">
                <?php foreach ($images as $img): ?>
                    <figure class="galerie__item animer" data-lightbox="<?= e(plat_photo($img['image'])) ?>" role="button" tabindex="0" aria-label="Agrandir : <?= e($img['titre'] ?: 'Photo KOO-KIN') ?>">
                        <img src="<?= e(plat_photo($img['image'])) ?>" alt="<?= e($img['titre'] ?: 'Photo KOO-KIN') ?>" loading="lazy">
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="titre-section centre animer">
                <span class="eyebrow">Galerie</span>
                <h2>Photos à venir</h2>
                <div class="separateur centre"></div>
                <p class="texte-doux">Notre galerie s'enrichit bientôt de nouvelles photos de nos plats et de nos événements.</p>
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Découvrir le menu</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Photo agrandie">
    <button type="button" class="lightbox__fermer" id="lightbox-fermer" aria-label="Fermer"><?= icone('fermer') ?></button>
    <img src="" alt="Photo KOO-KIN">
</div>

<?php require BASE_PATH . 'includes/front/footer.php';