<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$zones = db()->all('SELECT * FROM livraisons WHERE actif = 1 ORDER BY tarif, commune');
$tarifMin = (int) param('livraison_min', 6000);
$whatsapp = (string) param('whatsapp', '243994266536');

$page = [
    'titre' => 'Livraison — KOO-KIN',
    'actif' => 'livraison',
    'description' => 'Livraison de plats KOO-KIN à Kinshasa dès 11h. Consultez les frais par commune et commandez en ligne.',
    'fil' => 'Livraison',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="grille grille--3" style="margin-bottom:2.6rem">
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('panier') ?></span>
                <h3>1. Commandez</h3>
                <p class="texte-doux">Ajoutez vos plats au panier et validez votre commande en ligne.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('telephone') ?></span>
                <h3>2. Confirmation</h3>
                <p class="texte-doux">Nous vous appelons pour confirmer le contenu et l'adresse de livraison.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('livraison') ?></span>
                <h3>3. Livraison</h3>
                <p class="texte-doux">Votre repas part dès 11h et arrive chaud, emballé avec soin.</p>
            </div>
        </div>

        <div class="titre-section animer">
            <span class="eyebrow">Frais de livraison</span>
            <h2>Nos zones desservies</h2>
            <div class="separateur"></div>
            <p class="texte-doux"><?= e((string) param('livraison_texte', 'Livraison à Kinshasa. À partir de 6 000 CDF, selon le trajet et la commune.')) ?></p>
        </div>

        <?php if ($zones): ?>
            <div style="overflow-x:auto" class="animer">
                <table class="tableau">
                    <thead>
                        <tr><th>Commune</th><th>Zone</th><th style="text-align:right">Frais</th><th>Délai estimé</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zones as $z): ?>
                            <tr>
                                <td><?= e($z['commune']) ?></td>
                                <td><?= e($z['zone'] ?: '—') ?></td>
                                <td style="text-align:right;font-weight:600;color:var(--or-fonce)"><?= e(format_prix((int) $z['tarif'])) ?></td>
                                <td><?= e($z['delai'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="menu-note animer" style="margin-top:1.4rem">
                Frais minimum : <?= e(format_prix($tarifMin)) ?>. Pour toute commune non listée, contactez-nous : le tarif sera confirmé selon le trajet.
            </div>
        <?php else: ?>
            <div class="menu-note animer">Les zones de livraison seront bientôt précisées. Contactez-nous pour connaître les frais applicables.</div>
        <?php endif; ?>

        <div class="carte-form animer" style="margin-top:2.6rem;text-align:center">
            <h2 style="font-size:1.5rem">Une envie de chez nous ?</h2>
            <p class="texte-doux">Choisissez vos plats et faites-vous livrer à Kinshasa.</p>
            <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1rem">
                <a href="<?= e(url('commander.php')) ?>" class="btn btn--or">Commander maintenant</a>
                <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn--contour"><?= icone('whatsapp') ?> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';