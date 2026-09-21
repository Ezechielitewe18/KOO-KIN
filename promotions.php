<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$promotions = db()->all('SELECT p.*, pl.nom AS plat_nom FROM promotions p LEFT JOIN plats pl ON pl.id = p.plat_id WHERE p.actif = 1 ORDER BY p.id DESC');

$page = [
    'titre' => 'Promotions — KOO-KIN',
    'actif' => 'promotions',
    'description' => 'Les offres promotionnelles en cours chez KOO-KIN : profitez de prix avantageux sur nos spécialités congolaises.',
    'fil' => 'Promotions',
];



require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if ($promotions): ?>
            <div class="grille grille--2">
                <?php foreach ($promotions as $promo):
                    $active = (!empty($promo['date_debut']) && strtotime($promo['date_debut']) > time()) ||
                              (!empty($promo['date_fin']) && strtotime($promo['date_fin']) < time()); ?>
                    <article class="carte animer" style="flex-direction:row;align-items:stretch">
                        <div class="carte__media" style="min-width:220px;aspect-ratio:auto">
                            <img src="<?= e(plat_photo($promo['photo'] ?? null)) ?>" alt="<?= e($promo['nom']) ?>" loading="lazy">
                            <?php if ($active): ?>
                                <div class="carte__etiquettes carte__etiquettes--droite"><span class="badge badge--erreur">Terminée</span></div>
                            <?php else: ?>
                                <div class="carte__etiquettes carte__etiquettes--droite"><span class="badge badge--or">Offre du moment</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="carte__corps">
                            <h3 class="carte__titre"><?= e($promo['nom']) ?></h3>
                            <p class="carte__desc"><?= e($promo['description'] ?: '') ?></p>
                            <div style="display:flex;gap:1rem;align-items:baseline;margin:.4rem 0">
                                <span class="prix" style="font-size:1.8rem"><?= e(format_prix($promo['prix_promo'])) ?></span>
                                <span class="prix--barre"><?= e(format_prix($promo['prix_normal'])) ?></span>
                                <span class="badge badge--succes">-<?= e((string) round(100 * (1 - (int) $promo['prix_promo'] / max(1, (int) $promo['prix_normal'])))) ?>%</span>
                            </div>
                            <?php if (!empty($promo['date_debut']) && !empty($promo['date_fin'])): ?>
                                <p class="texte-doux" style="font-size:.82rem">Du <?= e(date('d/m/Y', strtotime($promo['date_debut']))) ?> au <?= e(date('d/m/Y', strtotime($promo['date_fin']))) ?></p>
                            <?php endif; ?>
                            <div style="margin-top:auto;display:flex;gap:.7rem;flex-wrap:wrap">
                                <a href="<?= e(url('commander.php')) ?>" class="btn btn--or btn--sm">Commander</a>
                                <?php if ($promo['plat_id']): ?>
                                    <button type="button" class="btn btn--contour btn--sm" data-panier="<?= (int) $promo['plat_id'] ?>">Ajouter au panier</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="menu-note animer" style="margin-top:2.4rem">
                Les promotions sont valables dans la limite des stocks disponibles, selon les conditions communiquées par KOO-KIN.
            </div>
        <?php else: ?>
            <div class="titre-section centre animer">
                <span class="eyebrow">Offre du moment</span>
                <h2>Aucune promotion en cours</h2>
                <div class="separateur centre"></div>
                <p class="texte-doux">Une nouvelle offre arrive bientôt. Suivez KOO-KIN sur nos réseaux pour être informé en premier.</p>
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Découvrir notre menu</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';