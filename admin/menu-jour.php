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
        db()->run('DELETE FROM menus_jour WHERE id = ?', [$id]);
        Flash::success('Le menu a été supprimé.');
        admin_redirect('menu-jour.php');
    }

    if ($action === 'activer') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('UPDATE menus_jour SET actif = 0');
        db()->run('UPDATE menus_jour SET actif = 1 WHERE id = ?', [$id]);
        Flash::success('Ce menu est désormais le menu affiché sur le site.');
        admin_redirect('menu-jour.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $v = (new Validation())->make($_POST, [
            'titre'      => ['label' => 'titre', 'rules' => 'required|max:150'],
            'prix'       => ['label' => 'prix', 'rules' => 'numeric'],
            'date_debut' => ['label' => 'date de début', 'rules' => 'date'],
            'date_fin'   => ['label' => 'date de fin', 'rules' => 'date'],
        ]);

        if ($v->fails()) {
            $erreur = $v->first();
        } else {
            $actif = isset($_POST['actif']) ? 1 : 0;
            $donnees = [
                'titre'                 => trim((string) $_POST['titre']),
                'plats_text'            => trim((string) ($_POST['plats_text'] ?? '')),
                'accompagnements_text'  => trim((string) ($_POST['accompagnements_text'] ?? '')),
                'prix'                  => trim((string) ($_POST['prix'] ?? '')) === '' ? null : admin_entier($_POST['prix']),
                'note'                  => trim((string) ($_POST['note'] ?? '')),
                'date_debut'            => trim((string) ($_POST['date_debut'] ?? '')) === '' ? null : (string) $_POST['date_debut'],
                'date_fin'              => trim((string) ($_POST['date_fin'] ?? '')) === '' ? null : (string) $_POST['date_fin'],
                'actif'                 => $actif,
            ];

            if ($id > 0) {
                $donnees['id'] = $id;
                db()->run(
                    'UPDATE menus_jour SET titre=:titre, plats_text=:plats_text, accompagnements_text=:accompagnements_text, prix=:prix, note=:note, date_debut=:date_debut, date_fin=:date_fin, actif=:actif WHERE id=:id',
                    $donnees
                );
                Flash::success('Le menu a été mis à jour.');
            } else {
                db()->run(
                    'INSERT INTO menus_jour (titre, plats_text, accompagnements_text, prix, note, date_debut, date_fin, actif)
                     VALUES (:titre, :plats_text, :accompagnements_text, :prix, :note, :date_debut, :date_fin, :actif)',
                    $donnees
                );
                $id = (int) db()->lastId();
                Flash::success('Le menu a été ajouté.');
            }

            if ($actif === 1) {
                db()->run('UPDATE menus_jour SET actif = 0 WHERE id <> ?', [$id]);
            }
            admin_redirect('menu-jour.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$menu = $editId > 0 ? db()->one('SELECT * FROM menus_jour WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $menu !== null || $erreur !== null;

$menus = db()->all('SELECT * FROM menus_jour ORDER BY actif DESC, updated_at DESC');

$valeurs = static function (string $champ, mixed $defaut = '') use ($menu): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $menu[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($menu): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($menu[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Menu du jour';
$admin_actif = 'menu-jour';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $menu ? 'Modifier le menu' : 'Nouveau menu du jour' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('menu-jour.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('menu-jour.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouveau menu</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('menu-jour.php')) ?>" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($menu['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="titre">Titre *</label>
                        <input type="text" id="titre" name="titre" required maxlength="150" value="<?= e($valeurs('titre', 'Menu du jour')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="prix">Prix (CDF)</label>
                        <input type="number" id="prix" name="prix" min="0" step="100" value="<?= e($valeurs('prix')) ?>">
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="plats_text">Plats (un par ligne)</label>
                        <textarea id="plats_text" name="plats_text" rows="6"><?= e($valeurs('plats_text')) ?></textarea>
                    </div>
                    <div class="adm-champ">
                        <label for="accompagnements_text">Accompagnements (un par ligne)</label>
                        <textarea id="accompagnements_text" name="accompagnements_text" rows="6"><?= e($valeurs('accompagnements_text')) ?></textarea>
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="date_debut">Date de début</label>
                        <input type="date" id="date_debut" name="date_debut" value="<?= e($valeurs('date_debut')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="date_fin">Date de fin</label>
                        <input type="date" id="date_fin" name="date_fin" value="<?= e($valeurs('date_fin')) ?>">
                    </div>
                </div>

                <div class="adm-champ adm-champ--large">
                    <label for="note">Note (affichée sous le menu)</label>
                    <input type="text" id="note" name="note" maxlength="255" value="<?= e($valeurs('note')) ?>">
                </div>

                <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Afficher ce menu sur le site</label>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $menu ? 'Enregistrer' : 'Ajouter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete"><h2>Menus (<?= count($menus) ?>)</h2></div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($menus): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Titre</th><th>Période</th><th class="num">Prix</th><th>État</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menus as $m): ?>
                            <tr>
                                <td><strong><?= e($m['titre']) ?></strong><br><small style="color:var(--brun-doux)">Modifié le <?= e(date('d/m/Y H:i', strtotime((string) $m['updated_at']))) ?></small></td>
                                <td><?= $m['date_debut'] ? e(date('d/m', strtotime((string) $m['date_debut']))) : '—' ?> → <?= $m['date_fin'] ? e(date('d/m/Y', strtotime((string) $m['date_fin']))) : '—' ?></td>
                                <td class="num"><?= $m['prix'] ? e(format_prix((int) $m['prix'])) : '—' ?></td>
                                <td>
                                    <?php if ((int) $m['actif'] === 1): ?>
                                        <span class="adm-badge adm-badge--succes">Affiché</span>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge--neutre">Masqué</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="adm-table__actions">
                                        <a href="<?= e(admin_url('menu-jour.php?edit=' . (int) $m['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                        <?php if ((int) $m['actif'] !== 1): ?>
                                            <form method="post" action="<?= e(admin_url('menu-jour.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="activer">
                                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn--or adm-btn--sm">Afficher</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= e(admin_url('menu-jour.php')) ?>" data-confirm="Supprimer ce menu ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
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
            <div class="adm-vide"><strong>Aucun menu</strong>Créez le menu du jour à afficher sur le site.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';