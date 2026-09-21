<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$statuts = [
    'nouvelle'       => 'Nouvelle',
    'confirmee'      => 'Confirmée',
    'en_preparation' => 'En préparation',
    'prete'          => 'Prête',
    'en_livraison'   => 'En livraison',
    'livree'         => 'Livrée',
    'annulee'        => 'Annulée',
];
$couleurs = [
    'nouvelle'       => 'or',
    'confirmee'      => 'info',
    'en_preparation' => 'info',
    'prete'          => 'info',
    'en_livraison'   => 'info',
    'livree'         => 'succes',
    'annulee'        => 'erreur',
];
$types = ['sur_place' => 'Sur place', 'emporter' => 'À emporter', 'livraison' => 'Livraison'];
$paiements = ['sur_place' => 'Sur place', 'livraison' => 'À la livraison', 'mobile' => 'Mobile'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = admin_entier($_POST['id'] ?? 0);
    $statut = (string) ($_POST['statut'] ?? '');

    if ($id > 0 && isset($statuts[$statut])) {
        db()->run('UPDATE commandes SET statut = ? WHERE id = ?', [$statut, $id]);
        Flash::success('Le statut de la commande a été mis à jour.');
    }
    admin_redirect('commandes.php?id=' . $id);
}

$idDetail = admin_entier($_GET['id'] ?? 0);
$commande = null;
$lignes = [];
if ($idDetail > 0) {
    $commande = db()->one('SELECT * FROM commandes WHERE id = ?', [$idDetail]);
    if ($commande) {
        $lignes = db()->all('SELECT * FROM commande_details WHERE commande_id = ? ORDER BY id', [$idDetail]);
    }
}

$filtre = (string) ($_GET['statut'] ?? '');
$sql = 'SELECT * FROM commandes';
$params = [];
if (isset($statuts[$filtre])) {
    $sql .= ' WHERE statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY id DESC';

$commandes = $commande ? [] : db()->all($sql, $params);
$compteurs = [];
foreach (array_keys($statuts) as $s) {
    $compteurs[$s] = (int) db()->value('SELECT COUNT(*) FROM commandes WHERE statut = ?', [$s]);
}

$admin_titre = $commande ? 'Commande ' . $commande['code'] : 'Commandes';
$admin_actif = 'commandes';

require __DIR__ . '/includes/header.php';

if ($commande): ?>
    <div class="adm-detail">
        <div>
            <div class="adm-carte">
                <div class="adm-carte__entete">
                    <h2>Articles commandés</h2>
                    <div class="adm-actions">
                        <a href="<?= e(admin_url('commandes.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Retour à la liste</a>
                    </div>
                </div>
                <div class="adm-carte__corps adm-carte__corps--serre">
                    <div class="adm-table__wrap">
                        <table class="adm-table">
                            <thead><tr><th>Plat</th><th class="num">Prix</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($lignes as $l): ?>
                                    <tr>
                                        <td><?= e($l['plat_nom']) ?></td>
                                        <td class="num"><?= e(format_prix((int) $l['prix_unitaire'])) ?></td>
                                        <td class="num"><?= (int) $l['quantite'] ?></td>
                                        <td class="num"><?= e(format_prix((int) $l['total'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="3" class="num">Sous-total</td><td class="num"><?= e(format_prix((int) $commande['sous_total'])) ?></td></tr>
                                <tr><td colspan="3" class="num">Frais de livraison</td><td class="num"><?= e(format_prix((int) $commande['frais_livraison'])) ?></td></tr>
                                <tr><td colspan="3" class="num"><strong>Total</strong></td><td class="num"><strong><?= e(format_prix((int) $commande['total'])) ?></strong></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <?php if ($commande['note']): ?>
                <div class="adm-carte">
                    <div class="adm-carte__entete"><h2>Note du client</h2></div>
                    <div class="adm-carte__corps"><?= nl2br(e($commande['note'])) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="adm-carte">
                <div class="adm-carte__entete"><h2>Statut</h2></div>
                <div class="adm-carte__corps">
                    <p>
                        <span class="adm-badge adm-badge--<?= e($couleurs[$commande['statut']] ?? 'neutre') ?>"><?= e($statuts[$commande['statut']] ?? $commande['statut']) ?></span>
                    </p>
                    <form method="post" action="<?= e(admin_url('commandes.php')) ?>" class="adm-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                        <div class="adm-champ">
                            <label for="statut">Changer le statut</label>
                            <select id="statut" name="statut">
                                <?php foreach ($statuts as $cle => $label): ?>
                                    <option value="<?= e($cle) ?>" <?= $commande['statut'] === $cle ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="adm-btn adm-btn--or">Mettre à jour</button>
                    </form>
                </div>
            </div>

            <div class="adm-carte">
                <div class="adm-carte__entete"><h2>Client</h2></div>
                <div class="adm-carte__corps">
                    <ul class="adm-info">
                        <li><span>Code</span><span><?= e($commande['code']) ?></span></li>
                        <li><span>Nom</span><span><?= e($commande['client_nom']) ?></span></li>
                        <li><span>Téléphone</span><span><a href="tel:<?= e($commande['client_telephone']) ?>"><?= e($commande['client_telephone']) ?></a></span></li>
                        <li><span>Type</span><span><?= e($types[$commande['type']] ?? $commande['type']) ?></span></li>
                        <li><span>Paiement</span><span><?= e($paiements[$commande['paiement']] ?? $commande['paiement']) ?></span></li>
                        <li><span>Commune</span><span><?= e((string) $commande['commune']) ?></span></li>
                        <li><span>Adresse</span><span><?= e((string) $commande['client_adresse']) ?></span></li>
                        <li><span>Date</span><span><?= e(date('d/m/Y H:i', strtotime((string) $commande['created_at']))) ?></span></li>
                    </ul>
                    <?php $tel = preg_replace('/[^0-9]/', '', (string) $commande['client_telephone']); ?>
                    <?php if ($tel): ?>
                        <div class="adm-actions" style="margin-top:1rem">
                            <a href="https://wa.me/<?= e($tel) ?>" target="_blank" rel="noopener" class="adm-btn adm-btn--contour adm-btn--sm"><?= icone('whatsapp') ?> WhatsApp</a>
                            <a href="tel:<?= e($commande['client_telephone']) ?>" class="adm-btn adm-btn--contour adm-btn--sm"><?= icone('telephone') ?> Appeler</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="adm-carte">
        <div class="adm-carte__entete">
            <h2>Toutes les commandes</h2>
            <div class="adm-actions">
                <a href="<?= e(admin_url('commandes.php')) ?>" class="adm-filtre <?= $filtre === '' ? 'actif' : '' ?>">Tout</a>
                <?php foreach ($statuts as $cle => $label): if ($compteurs[$cle] === 0) continue; ?>
                    <a href="<?= e(admin_url('commandes.php?statut=' . $cle)) ?>" class="adm-filtre <?= $filtre === $cle ? 'actif' : '' ?>"><?= e($label) ?> (<?= $compteurs[$cle] ?>)</a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="adm-carte__corps adm-carte__corps--serre">
            <?php if ($commandes): ?>
                <div class="adm-table__wrap">
                    <table class="adm-table">
                        <thead>
                            <tr><th>Code</th><th>Client</th><th>Type</th><th>Date</th><th class="num">Total</th><th>Statut</th><th class="num">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commandes as $c): ?>
                                <tr>
                                    <td><strong><?= e($c['code']) ?></strong></td>
                                    <td><?= e($c['client_nom']) ?><br><small style="color:var(--brun-doux)"><?= e($c['client_telephone']) ?></small></td>
                                    <td><?= e($types[$c['type']] ?? $c['type']) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $c['created_at']))) ?></td>
                                    <td class="num"><?= e(format_prix((int) $c['total'])) ?></td>
                                    <td><span class="adm-badge adm-badge--<?= e($couleurs[$c['statut']] ?? 'neutre') ?>"><?= e($statuts[$c['statut']] ?? $c['statut']) ?></span></td>
                                    <td>
                                        <div class="adm-table__actions">
                                            <a href="<?= e(admin_url('commandes.php?id=' . (int) $c['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Voir</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="adm-vide"><strong>Aucune commande</strong>Les commandes reçues s'afficheront ici.</div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php';