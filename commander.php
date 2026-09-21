<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Panier;
use KooKin\Core\Validation;
use KooKin\Core\Flash;

$communes = db()->all('SELECT commune, zone, tarif FROM livraisons WHERE actif = 1 ORDER BY commune, tarif');
$listCommunes = [];
foreach ($communes as $c) {
    $listCommunes[$c['commune']] = (int) $c['tarif'];
}
$tarifDefaut = (int) param('livraison_min', 6000);

$confirmation = null;
$codeMerci = trim((string) ($_GET['merci'] ?? ''));
if ($codeMerci !== '') {
    $confirmation = db()->one('SELECT * FROM commandes WHERE code = ?', [$codeMerci]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commande'])) {
    csrf_check();

    $v = (new Validation())->make($_POST, [
        'nom'            => ['label' => 'nom complet', 'rules' => 'required|max:150'],
        'telephone'      => ['label' => 'téléphone', 'rules' => 'required|phone'],
        'type'           => ['label' => 'type de commande', 'rules' => 'required|in:sur_place,emporter,livraison'],
        'paiement'       => ['label' => 'mode de paiement', 'rules' => 'required|in:sur_place,livraison,mobile'],
        'commune'        => ['label' => 'commune', 'rules' => 'max:100'],
        'adresse'        => ['label' => 'adresse', 'rules' => 'max:255'],
        'note'           => ['label' => 'note', 'rules' => 'max:500'],
    ]);

    if (Panier::isEmpty()) {
        Flash::error('Votre panier est vide.');
        redirect('commander.php');
    }

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('commander.php');
    }

    $d = $v->data();
    $type = $d['type'];
    $sousTotal = Panier::total();

    $frais = 0;
    if ($type === 'livraison') {
        $commune = trim((string) ($d['commune'] ?? ''));
        $frais = $commune !== '' && isset($listCommunes[$commune])
            ? $listCommunes[$commune]
            : $tarifDefaut;
    }
    $total = $sousTotal + $frais;

    db()->begin();
    try {
        $client = db()->one('SELECT id FROM clients WHERE telephone = ? LIMIT 1', [$d['telephone']]);
        if ($client === null) {
            db()->run('INSERT INTO clients (nom, telephone, adresse, commune) VALUES (?, ?, ?, ?)', [
                $d['nom'], $d['telephone'], trim((string) ($d['adresse'] ?? '')), trim((string) ($d['commune'] ?? '')),
            ]);
            $clientId = db()->lastId();
        } else {
            $clientId = (int) $client['id'];
        }

        $code = 'CMD-' . date('ymd') . '-' . strtoupper((string) random_int(1000, 9999));
        db()->run(
            'INSERT INTO commandes (code, client_id, client_nom, client_telephone, client_adresse, commune, type, frais_livraison, sous_total, total, paiement, note)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $code, $clientId, $d['nom'], $d['telephone'],
                trim((string) ($d['adresse'] ?? '')), trim((string) ($d['commune'] ?? '')),
                $type, $frais, $sousTotal, $total, $d['paiement'], trim((string) ($d['note'] ?? '')),
            ]
        );
        $commandeId = db()->lastId();

        foreach (Panier::items() as $item) {
            $ligne = (int) $item['prix'] * (int) $item['quantite'];
            db()->run(
                'INSERT INTO commande_details (commande_id, plat_id, plat_nom, prix_unitaire, quantite, total) VALUES (?, ?, ?, ?, ?, ?)',
                [(int) $commandeId, (int) $item['id'], $item['nom'], (int) $item['prix'], (int) $item['quantite'], $ligne]
            );
        }

        db()->commit();
        Panier::clear();
        Flash::success('Votre commande ' . $code . ' a bien été enregistrée.');
        redirect('commander.php?merci=' . urlencode($code));
    } catch (Throwable $e) {
        db()->rollback();
        Flash::error('Une erreur est survenue lors de l\'enregistrement de la commande. Réessayez.');
        redirect('commander.php');
    }
}

if ($confirmation) {
    $details = db()->all('SELECT * FROM commande_details WHERE commande_id = ?', [(int) $confirmation['id']]);
    $waMessage = rawurlencode(
        "Bonjour KOO-KIN, je viens de passer la commande " . $confirmation['code'] . " d'un total de " . format_prix($confirmation['total']) . ". Merci de la confirmer."
    );
    $waLien = 'https://wa.me/' . (string) param('whatsapp', '2438xxxxxxx') . '?text=' . $waMessage;
}

$page = [
    'titre' => 'Commander — KOO-KIN',
    'actif' => 'commander',
    'description' => 'Commandez vos plats KOO-KIN en ligne : sur place, à emporter ou en livraison à Kinshasa.',
    'fil' => 'Commander',
];

require BASE_PATH . 'includes/front/header.php';

if ($confirmation): ?>
    <section class="entete-page">
        <div class="conteneur animer visible">
            <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Commander</p>
            <h1>Commande confirmée</h1>
        </div>
    </section>

    <section class="section">
        <div class="conteneur">
            <div class="carte-form animer" style="max-width:640px;margin-inline:auto">
                <?php foreach (flash_success() as $m): ?><div class="alerte alerte--succes"><?= e($m) ?></div><?php endforeach; ?>
                <div class="texte-centre" style="margin-bottom:1.2rem">
                    <span style="font-size:3rem;color:var(--succes);display:inline-block"><?= icone('verifier', '') ?></span>
                    <h2 style="font-size:1.6rem;margin:.4rem 0">Merci <?= e($confirmation['client_nom']) ?> !</h2>
                    <p class="texte-doux">Votre commande <strong style="color:var(--or-fonce)"><?= e($confirmation['code']) ?></strong> a bien été enregistrée.</p>
                </div>

                <h3 style="font-size:1.1rem;margin-bottom:.6rem">Récapitulatif</h3>
                <table class="tableau">
                    <?php foreach ($details as $d): ?>
                        <tr><td><?= e($d['plat_nom']) ?> × <?= (int) $d['quantite'] ?></td><td style="text-align:right"><?= e(format_prix($d['total'])) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ((int) $confirmation['frais_livraison'] > 0): ?>
                        <tr><td>Livraison (<?= e($confirmation['commune']) ?>)</td><td style="text-align:right"><?= e(format_prix($confirmation['frais_livraison'])) ?></td></tr>
                    <?php endif; ?>
                    <tr style="font-weight:600"><td>Total</td><td style="text-align:right;color:var(--or-fonce);font-size:1.15rem"><?= e(format_prix($confirmation['total'])) ?></td></tr>
                </table>

                <div class="menu-note" style="margin-top:1.2rem">
                    Mode : <strong><?= e(strtoupper(str_replace('_', ' ', $confirmation['type']))) ?></strong> ·
                    Paiement : <strong><?= e(strtoupper(str_replace('_', ' ', $confirmation['paiement']))) ?></strong><br>
                    Nous vous contactons au <strong><?= e($confirmation['client_telephone']) ?></strong> pour confirmer.
                </div>

                <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-top:1.4rem">
                    <a href="<?= e($waLien) ?>" target="_blank" rel="noopener" class="btn btn--or"><?= icone('whatsapp') ?> Confirmer sur WhatsApp</a>
                    <a href="<?= e(url('menu.php')) ?>" class="btn btn--contour">Commander encore</a>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>

<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Commander</p>
        <h1>Commander</h1>
        <p>Vérifiez votre panier, choisissez votre mode de commande puis validez en quelques secondes.</p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
        <?php foreach (flash_info() as $m): ?><div class="alerte alerte--info"><?= e($m) ?></div><?php endforeach; ?>

        <div class="grille grille--2" style="align-items:start">
            <div class="carte-form animer">
                <h2 style="font-size:1.4rem">Mon panier</h2>
                <div class="separateur"></div>
                <div class="js-panier-contenu"></div>
                <div class="panier-total" style="margin-top:1rem"><span>Total</span><strong class="js-panier-total">0 CDF</strong></div>
                <div class="menu-note">Les frais de livraison sont calculés selon la commune au moment de valider.</div>
            </div>

            <form method="post" action="<?= e(url('commander.php')) ?>" class="carte-form animer" id="form-commande">
                <?= csrf_field() ?>
                <h2 style="font-size:1.4rem">Vos informations</h2>
                <div class="separateur"></div>
                <div class="form">
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="nom">Nom complet *</label>
                            <input type="text" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="150" autocomplete="name">
                        </div>
                        <div class="champ">
                            <label for="telephone">Téléphone *</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= e(old('telephone')) ?>" required placeholder="+243 ..." autocomplete="tel">
                        </div>
                    </div>

                    <div class="form__ligne">
                        <div class="champ">
                            <label for="type">Type de commande *</label>
                            <select id="type" name="type" required>
                                <option value="sur_place" <?= old('type') === 'sur_place' ? 'selected' : '' ?>>Sur place</option>
                                <option value="emporter" <?= old('type') === 'emporter' ? 'selected' : '' ?>>À emporter</option>
                                <option value="livraison" <?= old('type') === 'livraison' ? 'selected' : '' ?>>Livraison</option>
                            </select>
                        </div>
                        <div class="champ">
                            <label for="paiement">Mode de paiement *</label>
                            <select id="paiement" name="paiement" required>
                                <option value="sur_place" <?= old('paiement') === 'sur_place' ? 'selected' : '' ?>>Sur place</option>
                                <option value="livraison" <?= old('paiement') === 'livraison' ? 'selected' : '' ?>>À la livraison</option>
                                <option value="mobile" <?= old('paiement') === 'mobile' ? 'selected' : '' ?>>Paiement mobile</option>
                            </select>
                        </div>
                    </div>

                    <div class="js-zone-livraison">
                        <div class="form__ligne">
                            <div class="champ">
                                <label for="commune">Commune</label>
                                <select id="commune" name="commune">
                                    <option value="">— Choisir une commune —</option>
                                    <?php foreach ($listCommunes as $nomCommune => $tarif): ?>
                                        <option value="<?= e($nomCommune) ?>" data-tarif="<?= (int) $tarif ?>" <?= old('commune') === $nomCommune ? 'selected' : '' ?>><?= e($nomCommune) ?> — <?= e(format_prix($tarif)) ?></option>
                                    <?php endforeach; ?>
                                    <option value="autre" data-tarif="<?= $tarifDefaut ?>" <?= old('commune') === 'autre' ? 'selected' : '' ?>>Autre commune (à préciser)</option>
                                </select>
                            </div>
                            <div class="champ">
                                <label for="adresse">Adresse / précision</label>
                                <input type="text" id="adresse" name="adresse" value="<?= e(old('adresse')) ?>" maxlength="255" placeholder="Avenue, n°, repère...">
                            </div>
                        </div>
                        <p class="texte-doux" style="font-size:.85rem" id="frais-affichage">Frais de livraison : à définir</p>
                    </div>

                    <div class="champ champ--large">
                        <label for="note">Note (optionnel)</label>
                        <textarea id="note" name="note" maxlength="500" style="min-height:80px" placeholder="Précisions pour votre commande..."><?= e(old('note')) ?></textarea>
                    </div>

                    <button type="submit" name="commande" value="1" class="btn btn--or btn--bloc" id="btn-commander" <?= Panier::isEmpty() ? 'disabled' : '' ?>>Passer la commande</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    (function () {
        const selectType = document.getElementById('type');
        const selectCommune = document.getElementById('commune');
        const zoneLivraison = document.querySelector('.js-zone-livraison');
        const affichage = document.getElementById('frais-affichage');

        const maj = () => {
            const livraison = selectType.value === 'livraison';
            zoneLivraison.style.display = livraison ? '' : 'none';
            if (livraison) {
                const opt = selectCommune.options[selectCommune.selectedIndex];
                const tarif = opt ? (parseInt(opt.dataset.tarif, 10) || 0) : 0;
                affichage.textContent = tarif > 0 ? 'Frais de livraison : ' + tarif.toLocaleString('fr-FR') + ' CDF' : 'Frais de livraison : à définir';
            }
        };

        selectType.addEventListener('change', maj);
        selectCommune.addEventListener('change', maj);
        maj();
    })();
</script>

<?php endif;

require BASE_PATH . 'includes/front/footer.php';