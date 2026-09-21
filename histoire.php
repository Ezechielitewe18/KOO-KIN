<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$page = [
    'titre' => 'Notre histoire — KOO-KIN',
    'actif' => 'histoire',
    'description' => 'L\'histoire de KOO-KIN : une cuisine congolaise authentique, préparée avec passion et servie avec générosité à Kinshasa.',
    'fil' => 'Notre histoire',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="grille grille--2" style="align-items:center;gap:3rem">
            <div class="animer">
                <span class="eyebrow">Qui sommes-nous</span>
                <h2>Une cuisine de chez nous, servie avec fierté</h2>
                <div class="separateur"></div>
                <p class="texte-doux"><?= nl2br(e((string) param('histoire', 'KOO-KIN propose une cuisine congolaise authentique, avec le souci des saveurs de chez nous.'))) ?></p>
                <p class="texte-doux">
                    Nos plats sont préparés à la commande, avec des produits frais et des recettes qui
                    racontent notre pays. Du feu de bois aux accompagnements traditionnels, chaque assiette
                    est pensée pour rappeler la chaleur d'un repas partagé en famille.
                </p>
            </div>
            <div class="animer">
                <div style="border-radius:22px;overflow:hidden;box-shadow:var(--ombre-forte)">
                    <img src="<?= e(hero_photo(null)) ?>" alt="KOO-KIN">
                </div>
            </div>
        </div>

        <div class="grille grille--3" style="margin-top:3rem">
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('etoile') ?></span>
                <h3>Saveurs authentiques</h3>
                <p class="texte-doux">Des recettes fidèles à la cuisine congolaise, préparées avec des produits de qualité.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('traiteur') ?></span>
                <h3>Un service humain</h3>
                <p class="texte-doux">Une équipe attentionnée, à l'écoute, pour que chaque visite soit un bon moment.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('livraison') ?></span>
                <h3>Proche de vous</h3>
                <p class="texte-doux">Sur place à Kintambo, à emporter ou en livraison partout à Kinshasa.</p>
            </div>
        </div>

        <div class="carte-form animer" style="margin-top:3rem;text-align:center">
            <h2 style="font-size:1.5rem">Venez partager un repas chez KOO-KIN</h2>
            <p class="texte-doux"><?= e((string) param('adresse', '')) ?> — <?= e((string) param('repere', '')) ?></p>
            <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1rem">
                <a href="<?= e(url('reservation.php')) ?>" class="btn btn--or">Réserver une table</a>
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--contour">Voir le menu</a>
            </div>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';