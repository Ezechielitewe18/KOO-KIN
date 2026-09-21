<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Panier;

$page = [
    'titre' => 'KOO-KIN — Cuisine Congolaise Authentique à Kinshasa',
    'actif' => 'accueil',
    'home'  => true,
];

$speciaux = db()->all('SELECT * FROM plats WHERE populaire = 1 AND disponible = 1 ORDER BY ordre ASC, id ASC LIMIT 4');
$menuJour = db()->one('SELECT * FROM menus_jour WHERE actif = 1 ORDER BY updated_at DESC LIMIT 1');
$promoActuelle = db()->one(
    'SELECT p.* FROM promotions p WHERE p.actif = 1 AND (p.date_debut IS NULL OR p.date_debut <= CURDATE()) AND (p.date_fin IS NULL OR p.date_fin >= CURDATE()) ORDER BY p.id DESC LIMIT 1'
);

$specialite = static function (array $plat): void {
    $photo = plat_photo($plat['photo'] ?? null);
    echo '
    <article class="carte animer">
        <div class="carte__media">
            <img src="' . e($photo) . '" alt="' . e($plat['nom']) . '" loading="lazy">
            <div class="carte__etiquettes">';
    if ((int) $plat['populaire'] === 1) {
        echo '<span class="badge badge--or">Populaire</span>';
    }
    echo '</div></div>
        <div class="carte__corps">
            <h3 class="carte__titre">' . e($plat['nom']) . '</h3>
            <p class="carte__desc">' . e($plat['description'] ?: 'Servi avec soin, préparé avec des ingrédients frais.') . '</p>
            <div class="carte__pied">
                <span class="prix">' . e(format_prix($plat['prix'])) . '</span>
                <button type="button" class="btn btn--or btn--sm" data-panier="' . (int) $plat['id'] . '">Ajouter au panier</button>
            </div>
        </div>
    </article>';
};

require BASE_PATH . 'includes/front/header.php';
?>

<section class="hero">
    <div class="hero__media">
        <img src="<?= e(hero_photo(null)) ?>" alt="KOO-KIN — Cuisine Congolaise Authentique">
    </div>
    <div class="conteneur">
        <div class="hero__contenu animer visible">
            <span class="eyebrow" style="color:var(--or-vif)"><?= e((string) param('site_slogan', 'Cuisine Congolaise Authentique')) ?></span>
            <h1 class="hero__titre">KOO-KIN</h1>
            <p class="hero__sous-titre"><?= e((string) param('site_description', 'Saveurs de chez nous')) ?></p>
            <p class="hero__texte"><?= e((string) param('hero_sous_titre', 'Découvrez les saveurs authentiques de chez nous.')) ?></p>
            <div class="hero__actions">
                <a href="<?= e(url('commander.php')) ?>" class="btn btn--or">Commander maintenant</a>
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--clair">Découvrir le menu</a>
                <a href="<?= e(url('reservation.php')) ?>" class="discret">Réserver une table <?= icone('fleche') ?></a>
            </div>
            <div class="hero__mini">
                <div><strong><?= e((string) param('livraison_min', '6 000')) ?> CDF</strong><span>Livraison dès</span></div>
                <div><strong>11h</strong><span>Nous livrons dès</span></div>
                <div><strong><?= e((string) param('telephone', '+243 8xx xxx xxx')) ?></strong><span>Appelez-nous</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <div class="titre-section centre animer">
            <span class="eyebrow">Nos spécialités</span>
            <h2>Les plats préférés de chez nous</h2>
            <div class="separateur centre"></div>
            <p class="texte-doux">Des recettes congolaises préparées avec des ingrédients frais, du feu de bois et beaucoup d'amour.</p>
        </div>
        <div class="grille grille--4" style="margin-top:2rem">
            <?php foreach ($speciaux as $plat): $specialite($plat); endforeach; ?>
        </div>
        <div class="texte-centre animer" style="margin-top:2.2rem">
            <a href="<?= e(url('menu.php')) ?>" class="btn btn--contour">Voir tout notre menu</a>
        </div>
    </div>
</section>

<?php if ($promoActuelle): ?>
<section class="section section--or">
    <div class="conteneur">
        <div class="grille grille--2" style="align-items:center">
            <div class="animer">
                <span class="eyebrow" style="color:var(--noir)">Offre du moment</span>
                <h2 style="font-size:clamp(1.9rem,3.5vw,2.8rem)"><?= e($promoActuelle['nom']) ?></h2>
                <p><?= e($promoActuelle['description'] ?: '') ?></p>
                <div style="display:flex;gap:1rem;align-items:baseline;margin:1.2rem 0">
                    <span class="prix" style="color:var(--noir);font-size:2.2rem"><?= e(format_prix($promoActuelle['prix_promo'])) ?></span>
                    <span class="prix--barre" style="color:rgba(31,27,20,.65)"><?= e(format_prix($promoActuelle['prix_normal'])) ?></span>
                </div>
                <a href="<?= e(url('commander.php')) ?>" class="btn btn--sombre">Commander cette offre</a>
            </div>
            <div class="animer" style="justify-self:center">
                <div style="max-width:460px;border-radius:22px;overflow:hidden;box-shadow:0 30px 60px -30px rgba(0,0,0,.6)">
                    <img src="<?= e(plat_photo($promoActuelle['photo'] ?? null)) ?>" alt="<?= e($promoActuelle['nom']) ?>">
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($menuJour): ?>
<section class="section section--creme2">
    <div class="conteneur">
        <div class="titre-section centre animer">
            <span class="eyebrow">Aujourd'hui</span>
            <h2>Menu du jour</h2>
            <div class="separateur centre"></div>
        </div>
        <div class="menu-du-jour animer" style="margin-top:1.5rem">
            <div class="menu-du-jour__grid">
                <div>
                    <h3 style="display:flex;align-items:center;gap:.5rem">Plats</h3>
                    <ul class="liste-menu">
                        <?php foreach (array_filter(array_map('trim', explode("\n", (string) $menuJour['plats_text']))) as $item): ?>
                            <li>• <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3 style="display:flex;align-items:center;gap:.5rem">Accompagnements</h3>
                    <ul class="liste-menu">
                        <?php foreach (array_filter(array_map('trim', explode("\n", (string) $menuJour['accompagnements_text']))) as $item): ?>
                            <li>• <?= e($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top:1.4rem">
                        <span class="prix"><?= e(format_prix((int) $menuJour['prix'])) ?> / plat</span>
                    </div>
                </div>
            </div>
            <?php if ($menuJour['note']): ?>
                <div class="menu-note"><?= e($menuJour['note']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="conteneur">
        <div class="grille grille--2" style="align-items:center;gap:3rem">
            <div class="animer">
                <span class="eyebrow">Nos services</span>
                <h2>Sur place, à emporter ou livrés à Kinshasa</h2>
                <div class="separateur"></div>
                <p class="texte-doux">
                    Profitez de la cuisine KOO-KIN où vous voulez : au restaurant à Kintambo,
                    à emporter, ou livrée dans toute la ville selon votre commune.
                </p>
                <div style="display:grid;gap:1rem;margin:1.6rem 0">
                    <div style="display:flex;gap:1rem;align-items:flex-start">
                        <span class="ico-info"><?= icone('livraison') ?></span>
                        <div><strong>Livraison</strong><br><span class="texte-doux" style="font-size:.9rem"><?= e((string) param('livraison_texte', 'À partir de 6 000 CDF, selon le trajet et la commune.')) ?></span></div>
                    </div>
                    <div style="display:flex;gap:1rem;align-items:flex-start">
                        <span class="ico-info"><?= icone('traiteur') ?></span>
                        <div><strong>Traiteur &amp; événements</strong><br><span class="texte-doux" style="font-size:.9rem">Mariages, anniversaires, cérémonies, réunions et fêtes familiales.</span></div>
                    </div>
                    <div style="display:flex;gap:1rem;align-items:flex-start">
                        <span class="ico-info"><?= icone('reserver') ?></span>
                        <div><strong>Réservation de table</strong><br><span class="texte-doux" style="font-size:.9rem">Réservez votre table en quelques secondes.</span></div>
                    </div>
                </div>
                <div style="display:flex;gap:.8rem;flex-wrap:wrap">
                    <a href="<?= e(url('livraison.php')) ?>" class="btn btn--or">Voir la livraison</a>
                    <a href="<?= e(url('traiteur.php')) ?>" class="btn btn--contour">Service traiteur</a>
                </div>
            </div>
            <div class="animer">
                <div style="border-radius:22px;overflow:hidden;box-shadow:var(--ombre-forte)">
                    <img src="<?= e(hero_photo(null)) ?>" alt="KOO-KIN">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--sombre">
    <div class="conteneur">
        <div class="grille grille--2" style="align-items:center;gap:3rem">
            <div class="animer">
                <span class="eyebrow" style="color:var(--or-vif)">Nous trouver</span>
                <h2><?= e((string) param('adresse', '')) ?></h2>
                <p style="color:rgba(250,246,238,.8)">Repère : <?= e((string) param('repere', '')) ?></p>
                <div style="display:inline-flex;gap:.8rem;flex-wrap:wrap;margin-top:1rem">
                    <a href="<?= e((string) param('map_lien', '#')) ?>" target="_blank" rel="noopener" class="btn btn--or">Itinéraire</a>
                    <a href="<?= e(url('contact.php')) ?>" class="btn btn--contour">Coordonnées complètes</a>
                </div>
                <div style="margin-top:2rem;display:inline-block;background:rgba(201,162,39,.14);padding:1rem 1.4rem;border-radius:14px;border-left:3px solid var(--or)">
                    <strong style="color:var(--or-vif)">Nos horaires</strong><br>
                    <span style="font-size:.92rem"><?= e((string) param('horaires_texte', 'Lundi — Dimanche')) ?></span>
                </div>
            </div>
            <div class="animer">
                <div class="carte-map">
                    <iframe
                        title="Carte KOO-KIN"
                        loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=15.232372%2C-4.344208%2C15.298878%2C-4.318201&layer=mapnik&marker=-4.3312%2C15.2656"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
Panier::count();
require BASE_PATH . 'includes/front/footer.php';