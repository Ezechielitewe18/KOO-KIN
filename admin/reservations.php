<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$statuts = [
    'en_attente' => 'En attente',
    'confirmee'  => 'Confirmée',
    'terminee'   => 'Terminée',
    'annulee'    => 'Annulée',
];
$couleurs = [
    'en_attente' => 'or',
    'confirmee'  => 'succes',
    'terminee'   => 'neutre',
    'annulee'    => 'erreur',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = admin_entier($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        db()->run('DELETE FROM reservations WHERE id = ?', [$id]);
        Flash::success('La réservation a été supprimée.');
        admin_redirect('reservations.php');
    }

    $statut = (string) ($_POST['statut'] ?? '');
    if ($id > 0 && isset($statuts[$statut])) {
        db()->run('UPDATE reservations SET statut = ? WHERE id = ?', [$statut, $id]);
        Flash::success('Le statut de la réservation a été mis à jour.');
    }
    admin_redirect('reservations.php');
}

$filtre = (string) ($_GET['statut'] ?? '');
$sql = 'SELECT * FROM reservations';
$params = [];
if (isset($statuts[$filtre])) {
    $sql .= ' WHERE statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY date_reservation DESC, heure_reservation DESC';
$reservations = db()->all($sql, $params);

$compteurs = [];
foreach (array_keys($statuts) as $s) {
    $compteurs[$s] = (int) db()->value('SELECT COUNT(*) FROM reservations WHERE statut = ?', [$s]);
}

$admin_titre = 'Réservations';
$admin_actif = 'reservations';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Demandes de réservation</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('reservations.php')) ?>" class="adm-filtre <?= $filtre === '' ? 'actif' : '' ?>">Toutes</a>
            <?php foreach ($statuts as $cle => $label): if ($compteurs[$cle] === 0) continue; ?>
                <a href="<?= e(admin_url('reservations.php?statut=' . $cle)) ?>" class="adm-filtre <?= $filtre === $cle ? 'actif' : '' ?>"><?= e($label) ?> (<?= $compteurs[$cle] ?>)</a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($reservations): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Client</th><th>Téléphone</th><th>Date</th><th>Heure</th><th class="num">Pers.</th><th>Message</th><th>Statut</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><strong><?= e($r['nom']) ?></strong></td>
                                <td><a href="tel:<?= e($r['telephone']) ?>"><?= e($r['telephone']) ?></a></td>
                                <td><?= e(date('d/m/Y', strtotime((string) $r['date_reservation']))) ?></td>
                                <td><?= e(substr((string) $r['heure_reservation'], 0, 5)) ?></td>
                                <td class="num"><?= (int) $r['nb_personnes'] ?></td>
                                <td><small><?= e(mb_strimwidth((string) $r['message'], 0, 60, '…')) ?></small></td>
                                <td><span class="adm-badge adm-badge--<?= e($couleurs[$r['statut']] ?? 'neutre') ?>"><?= e($statuts[$r['statut']] ?? $r['statut']) ?></span></td>
                                <td>
                                    <div class="adm-table__actions">
                                        <form method="post" action="<?= e(admin_url('reservations.php')) ?>" class="adm-actions">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <select name="statut" onchange="this.form.submit()" style="padding:.35rem .5rem;border:1px solid var(--ligne);border-radius:9px;font-size:.8rem">
                                                <?php foreach ($statuts as $cle => $label): ?>
                                                    <option value="<?= e($cle) ?>" <?= $r['statut'] === $cle ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                        <form method="post" action="<?= e(admin_url('reservations.php')) ?>" data-confirm="Supprimer cette réservation ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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
            <div class="adm-vide"><strong>Aucune réservation</strong>Les demandes de table s'afficheront ici.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';