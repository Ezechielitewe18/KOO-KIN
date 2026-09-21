<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$jours = db()->all('SELECT * FROM horaires ORDER BY id');
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $ouverture = $_POST['ouverture'] ?? [];
    $fermeture = $_POST['fermeture'] ?? [];
    $ferme = $_POST['ferme'] ?? [];

    foreach ($jours as $jour) {
        $id = (int) $jour['id'];
        $estFerme = isset($ferme[$id]) ? 1 : 0;

        if ($estFerme === 0) {
            $v = (new Validation())->make(
                ['ouverture' => $ouverture[$id] ?? '', 'fermeture' => $fermeture[$id] ?? ''],
                [
                    'ouverture' => ['label' => 'heure d\'ouverture (' . $jour['jour'] . ')', 'rules' => 'required|time'],
                    'fermeture' => ['label' => 'heure de fermeture (' . $jour['jour'] . ')', 'rules' => 'required|time'],
                ]
            );
            if ($v->fails()) {
                $erreur = $v->first();
                break;
            }
        }
    }

    if ($erreur === null) {
        foreach ($jours as $jour) {
            $id = (int) $jour['id'];
            $estFerme = isset($ferme[$id]) ? 1 : 0;
            db()->run(
                'UPDATE horaires SET ouverture = :ouverture, fermeture = :fermeture, ferme = :ferme WHERE id = :id',
                [
                    'ouverture' => $estFerme === 1 ? null : (($ouverture[$id] ?? '') . ':00'),
                    'fermeture' => $estFerme === 1 ? null : (($fermeture[$id] ?? '') . ':00'),
                    'ferme'     => $estFerme,
                    'id'        => $id,
                ]
            );
        }
        Flash::success('Les horaires ont été enregistrés.');
        admin_redirect('horaires.php');
    }
}

$admin_titre = 'Horaires';
$admin_actif = 'horaires';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Horaires d'ouverture</h2>
        <div class="adm-actions">
            <a href="<?= e(url('contact.php')) ?>" target="_blank" rel="noopener" class="adm-btn adm-btn--contour adm-btn--sm">Voir sur le site</a>
        </div>
    </div>
    <div class="adm-carte__corps">
        <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
        <p class="texte-doux" style="margin-top:0">Ces horaires déterminent l'état « Ouvert / Fermé » affiché sur le site public.</p>

        <form method="post" action="<?= e(admin_url('horaires.php')) ?>" class="adm-form">
            <?= csrf_field() ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Jour</th><th>Ouverture</th><th>Fermeture</th><th>Fermé</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jours as $j): $id = (int) $j['id']; ?>
                            <tr>
                                <td><strong><?= e($j['jour']) ?></strong></td>
                                <td>
                                    <input type="time" name="ouverture[<?= $id ?>]" value="<?= e(substr((string) ($j['ouverture'] ?? ''), 0, 5)) ?>" style="padding:.45rem .6rem;border:1px solid var(--ligne);border-radius:9px">
                                </td>
                                <td>
                                    <input type="time" name="fermeture[<?= $id ?>]" value="<?= e(substr((string) ($j['fermeture'] ?? ''), 0, 5)) ?>" style="padding:.45rem .6rem;border:1px solid var(--ligne);border-radius:9px">
                                </td>
                                <td>
                                    <label class="adm-case"><input type="checkbox" name="ferme[<?= $id ?>]" <?= (int) $j['ferme'] === 1 ? 'checked' : '' ?>> Fermé ce jour</label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="adm-actions">
                <button type="submit" class="adm-btn adm-btn--or">Enregistrer les horaires</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';