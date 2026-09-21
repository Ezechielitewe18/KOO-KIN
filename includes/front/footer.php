<?php

declare(strict_types=1);

require BASE_PATH . 'includes/front/chatbox.php';
?>

<footer class="footer">
    <div class="conteneur">
        <div class="footer__grid">
            <div>
                <h4>KOO-KIN</h4>
                <p style="font-size:.9rem"><?= e((string) param('footer_texte', 'Cuisine Congolaise Authentique — Saveurs de chez nous.')) ?></p>
                <p class="statut-ouvert <?php
                    $h = db()->one('SELECT ouverture, fermeture, ferme FROM horaires WHERE id = ? LIMIT 1', [(int) date('N')]);
                    $open = false;
                    if ($h) { $n = time(); $o = strtotime('today ' . $h['ouverture']); $f = strtotime('today ' . $h['fermeture']); if ($f < $o) { $f += 86400; } $open = ((int) $h['ferme'] === 0 && $n >= $o && $n <= $f); }
                echo $open ? 'ouvert' : 'ferme'; ?>"><span class="point"></span><span style="font-size:.82rem"><?= $open ? 'Ouvert maintenant' : 'Fermé actuellement' ?></span></p>
            </div>
            <div>
                <h4>Navigation</h4>
                <ul>
                    <li><a href="<?= e(url('menu.php')) ?>">Notre menu</a></li>
                    <li><a href="<?= e(url('commander.php')) ?>">Commander</a></li>
                    <li><a href="<?= e(url('reservation.php')) ?>">Réserver une table</a></li>
                    <li><a href="<?= e(url('traiteur.php')) ?>">Traiteur &amp; événements</a></li>
                    <li><a href="<?= e(url('galerie.php')) ?>">Galerie</a></li>
                </ul>
            </div>
            <div>
                <h4>Informations</h4>
                <ul>
                    <li><a href="<?= e(url('livraison.php')) ?>">Livraison</a></li>
                    <li><a href="<?= e(url('promotions.php')) ?>">Promotions</a></li>
                    <li><a href="<?= e(url('histoire.php')) ?>">Notre histoire</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4>Horaires &amp; contact</h4>
                <ul>
                    <li><?= e((string) param('horaires_texte', 'Lundi — Dimanche')) ?></li>
                    <li><?= e((string) param('adresse', '')) ?></li>
                    <li><a href="tel:<?= e(preg_replace('/\s+/', '', (string) param('telephone', ''))) ?>"><?= e((string) param('telephone', '')) ?></a></li>
                    <li><a href="mailto:<?= e((string) param('email', '')) ?>"><?= e((string) param('email', '')) ?></a></li>
                </ul>
            </div>
        </div>
        <div class="footer__bas">
            <span>© <?= date('Y') ?> <?= e((string) param('site_nom', 'KOO-KIN')) ?>. Tous droits réservés.</span>
            <span><?= e((string) param('site_slogan', 'Cuisine Congolaise Authentique')) ?> — <?= e((string) param('site_description', 'Saveurs de chez nous')) ?></span>
        </div>
    </div>
</footer>
</main>
</div>

<div class="drawer" id="drawer-panier" aria-hidden="true">
    <div class="drawer__entete">
        <h3>Mon panier</h3>
        <button class="drawer__fermer js-panier-fermer" aria-label="Fermer le panier">×</button>
    </div>
    <div class="drawer__corps js-panier-contenu"></div>
    <div class="drawer__pied">
        <div class="panier-total"><span>Total</span><strong class="js-panier-total">0 CDF</strong></div>
        <a href="<?= e(url('commander.php')) ?>" class="btn btn--or btn--bloc">Passer la commande</a>
    </div>
</div>

<div class="overlay" id="overlay-panier"></div>

<script>
    const KOOKIN_URL = <?= json_encode(url('/')) ?>;
    const KOOKIN_PARAMS = <?= json_encode([
        'whatsapp' => (string) param('whatsapp', '243994266536'),
        'map_lien' => (string) param('map_lien', ''),
        'telephone' => (string) param('telephone', ''),
    ]) ?>;
</script>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<script src="<?= e(asset('js/panier.js')) ?>" defer></script>
<script src="<?= e(asset('js/chatbox.js')) ?>" defer></script>
</body>
</html>