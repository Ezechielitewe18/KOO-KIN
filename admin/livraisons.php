<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('DELETE FROM livraisons WHERE id = ?', [$id]);
        Flash::success('La zone de livraison a été supprimée.');
        admin_redirect('livraisons.php');
    }

    if ($action === 'toggle') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('UPDATE livraisons SET actif = 1 - actif WHERE id = ?', [$id]);
        Flash::success('La zone a été mise à jour.');
        admin_redirect('livraisons.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $v = (new Validation())->make($_POST, [
            'commune' => ['label' => 'commune', 'rules' => 'required|max:100'],
            'tarif'   => ['label' => 'tarif', 'rules' => 'required|numeric'],
        ]);

        if ($v->fails()) {
            $erreur = $v->first();
        } else {
            $donnees = [
                'commune' => trim((string) $_POST['commune']),
                'zone'    => trim((string) ($_POST['zone'] ?? '')) ?: null,
                'tarif'   => admin_entier($_POST['tarif'] ?? 0),
                'delai'   => trim((string) ($_POST['delai'] ?? '')) ?: null,
                'actif'   => isset($_POST['actif']) ? 1 : 0,
            ];

            if ($id > 0) {
                $donnees['id'] = $id;
                db()->run('UPDATE livraisons SET commune=:commune, zone=:zone, tarif=:tarif, delai=:delai, actif=:actif WHERE id=:id', $donnees);
                Flash::success('La zone de livraison a été mise à jour.');
            } else {
                db()->run('INSERT INTO livraisons (commune, zone, tarif, delai, actif) VALUES (:commune, :zone, :tarif, :delai, :actif)', $donnees);
                Flash::success('La zone de livraison a été ajoutée.');
            }
            admin_redirect('livraisons.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$zone = $editId > 0 ? db()->one('SELECT * FROM livraisons WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $zone !== null || $erreur !== null;

$zones = db()->all('SELECT * FROM livraisons ORDER BY commune, zone');

$valeurs = static function (string $champ, mixed $defaut = '') use ($zone): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $zone[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($zone): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($zone[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Zones de livraison';
$admin_actif = 'livraisons';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $zone ? 'Modifier la zone' : 'Nouvelle zone de livraison' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('livraisons.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('livraisons.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouvelle zone</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('livraisons.php')) ?>" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($zone['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="commune">Commune *</label>
                        <input type="text" id="commune" name="commune" required maxlength="100" value="<?= e($valeurs('commune')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="zone">Zone / précision</label>
                        <input type="text" id="zone" name="zone" maxlength="100" value="<?= e($valeurs('zone')) ?>">
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="tarif">Tarif (CDF) *</label>
                        <input type="number" id="tarif" name="tarif" required min="0" step="500" value="<?= e($valeurs('tarif', 6000)) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="delai">Délai indicatif</label>
                        <input type="text" id="delai" name="delai" maxlength="100" value="<?= e($valeurs('delai', '30 min')) ?>">
                    </div>
                </div>

                <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Zone active</label>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $zone ? 'Enregistrer' : 'Ajouter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete"><h2>Zones (<?= count($zones) ?>)</h2></div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($zones): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Commune</th><th>Zone</th><th class="num">Tarif</th><th>Délai</th><th>État</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zones as $z): ?>
                            <tr>
                                <td><strong><?= e($z['commune']) ?></strong></td>
                                <td><?= e((string) $z['zone']) ?></td>
                                <td class="num"><?= e(format_prix((int) $z['tarif'])) ?></td>
                                <td><?= e((string) $z['delai']) ?></td>
                                <td>
                                    <?php if ((int) $z['actif'] === 1): ?>
                                        <span class="adm-badge adm-badge--succes">Active</span>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge--neutre">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="adm-table__actions">
                                        <a href="<?= e(admin_url('livraisons.php?edit=' . (int) $z['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                        <form method="post" action="<?= e(admin_url('livraisons.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn--contour adm-btn--sm"><?= (int) $z['actif'] === 1 ? 'Désactiver' : 'Activer' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(admin_url('livraisons.php')) ?>" data-confirm="Supprimer cette zone ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
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
            <div class="adm-vide"><strong>Aucune zone</strong>Ajoutez les communes desservies.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';