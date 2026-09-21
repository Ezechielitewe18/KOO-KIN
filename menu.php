<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$categories = db()->all('SELECT * FROM categories WHERE actif = 1 ORDER BY ordre, id');

$catSlug = trim((string) ($_GET['cat'] ?? ''));
$filtreId = null;
$categorieActive = null;
foreach ($categories as $c) {
    if ($c['slug'] === $catSlug) {
        $filtreId = (int) $c['id'];
        $categorieActive = $c;
    }
}

if ($filtreId !== null) {
    $plats = db()->all('SELECT * FROM plats WHERE categorie_id = ? ORDER BY ordre, id', [$filtreId]);
} else {
    $plats = db()->all('SELECT * FROM plats ORDER BY ordre, id');
}

$parCategorie = [];
foreach ($plats as $plat) {
    $parCategorie[(int) $plat['categorie_id']][] = $plat;
}

$page = [
    'titre' => 'Notre menu — KOO-KIN',
    'actif' => 'menu',
    'description' => 'Découvrez le menu de KOO-KIN : spécialités congolaises authentiques, grillades, accompagnements, boissons et desserts.',
    'fil' => 'Notre menu',
];

$carte = static function (array $plat) : void {
    $dispo = (int) $plat['disponible'] === 1;
    echo '<article class="carte animer">
        <div class="carte__media">
            <img src="' . e(plat_photo($plat['photo'] ?? null)) . '" alt="' . e($plat['nom']) . '" loading="lazy">
            <div class="carte__etiquettes">';
    if ((int) $plat['populaire'] === 1) {
        echo '<span class="badge badge--or">Populaire</span>';
    }
    if ((int) $plat['vegan'] === 1) {
        echo '<span class="badge etiquette-veg">Végétarien</span>';
    }
    echo '</div></div>
        <div class="carte__corps">
            <h3 class="carte__titre">' . e($plat['nom']) . '</h3>
            <p class="carte__desc">' . e($plat['description'] ?: 'Préparé avec soin et des ingrédients frais.') . '</p>
            <div class="carte__pied">
                <span class="prix">' . e(format_prix($plat['prix'])) . '</span>';
    if ($dispo) {
        echo '<button type="button" class="btn btn--or btn--sm" data-panier="' . (int) $plat['id'] . '">Ajouter</button>';
    } else {
        echo '<span class="badge badge--erreur">Indisponible</span>';
    }
    echo '</div></div></article>';
};

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="grille-categories animer" style="margin-bottom:2.2rem">
            <a href="<?= e(url('menu.php')) ?>" class="puce-cat <?= $filtreId === null ? 'actif' : '' ?>">Tout le menu</a>
            <?php foreach ($categories as $c): ?>
                <a href="<?= e(url('menu.php?cat=' . urlencode($c['slug']))) ?>" class="puce-cat <?= $filtreId === (int) $c['id'] ? 'actif' : '' ?>"><?= e($c['nom']) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($categorieActive): ?>
            <div class="titre-section animer" style="margin-bottom:2rem">
                <span class="eyebrow"><?= e($categorieActive['nom']) ?></span>
                <h2><?= e($categorieActive['nom']) ?></h2>
                <?php if ($categorieActive['description']): ?>
                    <p class="texte-doux"><?= e($categorieActive['description']) ?></p>
                <?php endif; ?>
                <div class="separateur"></div>
            </div>
            <div class="grille grille--3">
                <?php foreach (($parCategorie[$filtreId] ?? []) as $plat): $carte($plat); endforeach; ?>
            </div>
        <?php else: ?>
            <?php $premiere = true; ?>
            <?php foreach ($categories as $c):
                $platsCategorie = $parCategorie[(int) $c['id']] ?? [];
                if ($platsCategorie === []) {
                    continue;
                }
            ?>
                <div class="titre-section animer" style="<?= $premiere ? '' : 'margin-top:2.4rem' ?>;margin-bottom:1.6rem">
                    <?php $premiere = false; ?>
                    <span class="eyebrow"><?= e($c['nom']) ?></span>
                    <h2><?= e($c['nom']) ?></h2>
                    <?php if ($c['description']): ?>
                        <p class="texte-doux"><?= e($c['description']) ?></p>
                    <?php endif; ?>
                    <div class="separateur"></div>
                </div>
                <div class="grille grille--3">
                    <?php foreach ($platsCategorie as $plat): $carte($plat); endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="menu-note animer" style="margin-top:2.6rem">
            <?= e((string) param('livraison_texte', 'Livraison à Kinshasa. À partir de 6 000 CDF, selon le trajet et la commune.')) ?>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';