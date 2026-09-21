<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$statuts = [
    'nouvelle' => 'Nouvelle',
    'contacte' => 'Contactée',
    'traitee'  => 'Traitée',
    'annulee'  => 'Annulée',
];
$couleurs = [
    'nouvelle' => 'or',
    'contacte' => 'info',
    'traitee'  => 'succes',
    'annulee'  => 'erreur',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = admin_entier($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        db()->run('DELETE FROM demandes_traiteur WHERE id = ?', [$id]);
        Flash::success('La demande a été supprimée.');
        admin_redirect('traiteur.php');
    }

    $statut = (string) ($_POST['statut'] ?? '');
    if ($id > 0 && isset($statuts[$statut])) {
        db()->run('UPDATE demandes_traiteur SET statut = ? WHERE id = ?', [$statut, $id]);
        Flash::success('Le statut de la demande a été mis à jour.');
    }
    admin_redirect('traiteur.php');
}

$filtre = (string) ($_GET['statut'] ?? '');
$sql = 'SELECT * FROM demandes_traiteur';
$params = [];
if (isset($statuts[$filtre])) {
    $sql .= ' WHERE statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY id DESC';
$demandes = db()->all($sql, $params);

$compteurs = [];
foreach (array_keys($statuts) as $s) {
    $compteurs[$s] = (int) db()->value('SELECT COUNT(*) FROM demandes_traiteur WHERE statut = ?', [$s]);
}

$admin_titre = 'Demandes traiteur';
$admin_actif = 'traiteur';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Demandes de devis traiteur</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('traiteur.php')) ?>" class="adm-filtre <?= $filtre === '' ? 'actif' : '' ?>">Toutes</a>
            <?php foreach ($statuts as $cle => $label): if ($compteurs[$cle] === 0) continue; ?>
                <a href="<?= e(admin_url('traiteur.php?statut=' . $cle)) ?>" class="adm-filtre <?= $filtre === $cle ? 'actif' : '' ?>"><?= e($label) ?> (<?= $compteurs[$cle] ?>)</a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($demandes): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Client</th><th>Type</th><th>Date</th><th class="num">Pers.</th><th>Budget</th><th>Message</th><th>Statut</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $d): ?>
                            <tr>
                                <td><strong><?= e($d['nom']) ?></strong><br><a href="tel:<?= e((string) $d['telephone']) ?>"><small><?= e((string) $d['telephone']) ?></small></a></td>
                                <td><?= e((string) $d['type_evenement']) ?></td>
                                <td><?= $d['date_evenement'] ? e(date('d/m/Y', strtotime((string) $d['date_evenement']))) : '—' ?></td>
                                <td class="num"><?= $d['nb_personnes'] ? (int) $d['nb_personnes'] : '—' ?></td>
                                <td><?= e((string) $d['budget']) ?></td>
                                <td><small><?= e(mb_strimwidth((string) $d['message'], 0, 70, '…')) ?></small></td>
                                <td><span class="adm-badge adm-badge--<?= e($couleurs[$d['statut']] ?? 'neutre') ?>"><?= e($statuts[$d['statut']] ?? $d['statut']) ?></span></td>
                                <td>
                                    <div class="adm-table__actions">
                                        <form method="post" action="<?= e(admin_url('traiteur.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                            <select name="statut" onchange="this.form.submit()" style="padding:.35rem .5rem;border:1px solid var(--ligne);border-radius:9px;font-size:.8rem">
                                                <?php foreach ($statuts as $cle => $label): ?>
                                                    <option value="<?= e($cle) ?>" <?= $d['statut'] === $cle ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form method="post" action="<?= e(admin_url('traiteur.php')) ?>" data-confirm="Supprimer cette demande ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="adm-vide"><strong>Aucune demande</strong>Les demandes de devis s'afficheront ici.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';