<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$menuJour = db()->one('SELECT * FROM menus_jour WHERE actif = 1 ORDER BY updated_at DESC LIMIT 1');

$page = [
    'titre' => 'Menu du jour — KOO-KIN',
    'actif' => 'menu',
    'description' => 'Le menu du jour de KOO-KIN : plats du jour et accompagnements, livrés dès 11h.',
    'fil' => 'Menu du jour',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if ($menuJour): $plats = array_filter(array_map('trim', explode("\n", (string) $menuJour['plats_text'])));
            $accomp = array_filter(array_map('trim', explode("\n", (string) $menuJour['accompagnements_text']))); ?>
            <div class="menu-du-jour animer">
                <div class="texte-centre" style="margin-bottom:1.5rem">
                    <span class="eyebrow">Aujourd'hui</span>
                    <h2><?= e($menuJour['titre'] ?: 'Menu du jour') ?></h2>
                    <div class="separateur centre"></div>
                </div>
                <div class="menu-du-jour__grid">
                    <div>
                        <h3>Plats</h3>
                        <ul class="liste-menu">
                            <?php foreach ($plats as $item): ?>
                                <li>• <?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <h3>Accompagnements</h3>
                        <ul class="liste-menu">
                            <?php foreach ($accomp as $item): ?>
                                <li>• <?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php if ($menuJour['prix']): ?>
                    <div class="texte-centre" style="margin:1.8rem 0 .6rem">
                        <span class="prix" style="font-size:1.9rem"><?= e(format_prix((int) $menuJour['prix'])) ?></span>
                        <span class="texte-doux" style="display:block;font-size:.9rem">par plat, accompagnement inclus</span>
                    </div>
                <?php endif; ?>
                <?php if ($menuJour['note']): ?>
                    <div class="menu-note"><?= e($menuJour['note']) ?></div>
                <?php endif; ?>
            </div>

            <div class="grille grille--3" style="margin-top:2.4rem">
                <div class="carte-form animer">
                    <h3>Livraison</h3>
                    <p class="texte-doux" style="font-size:.9rem"><?= e((string) param('livraison_texte', 'À partir de 6 000 CDF, selon le trajet et la commune.')) ?></p>
                    <p class="texte-or" style="font-weight:600">À partir de <?= e((string) param('livraison_min', '6 000')) ?> CDF</p>
                </div>
                <div class="carte-form animer">
                    <h3>Nous livrons dès</h3>
                    <p class="texte-doux" style="font-size:.9rem">Les livraisons commencent à 11h.</p>
                    <p class="texte-or" style="font-weight:600">Dès 11h, tous les jours</p>
                </div>
                <div class="carte-form animer">
                    <h3>Réservation sur place</h3>
                    <p class="texte-doux" style="font-size:.9rem">Vous préférez profiter du menu du jour au restaurant ?</p>
                    <a href="<?= e(url('reservation.php')) ?>" class="btn btn--or btn--sm">Réserver une table</a>
                </div>
            </div>
        <?php else: ?>
            <div class="titre-section centre animer">
                <span class="eyebrow">Aujourd'hui</span>
                <h2>Menu du jour</h2>
                <div class="separateur centre"></div>
                <p class="texte-doux">Le menu du jour sera bientôt disponible. Contactez-nous pour connaître les plats du moment.</p>
                <div style="margin-top:1rem">
                    <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Voir notre menu complet</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';