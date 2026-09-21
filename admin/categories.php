<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$icones = ['plat', 'accompagnement', 'grill', 'boisson', 'dessert', 'etoile', 'menu', 'panier', 'traiteur'];
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = admin_entier($_POST['id'] ?? 0);
        $nb = (int) db()->value('SELECT COUNT(*) FROM plats WHERE categorie_id = ?', [$id]);
        if ($nb > 0) {
            Flash::error('Impossible de supprimer : ' . $nb . ' plat(s) utilisent cette catégorie.');
        } else {
            db()->run('DELETE FROM categories WHERE id = ?', [$id]);
            Flash::success('La catégorie a été supprimée.');
        }
        admin_redirect('categories.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $v = (new Validation())->make($_POST, [
            'nom'   => ['label' => 'nom', 'rules' => 'required|max:100'],
            'ordre' => ['label' => 'ordre', 'rules' => 'int'],
        ]);

        $icone = in_array((string) ($_POST['icone'] ?? ''), $icones, true) ? (string) $_POST['icone'] : 'plat';

        if ($v->fails()) {
            $erreur = $v->first();
        } else {
            $donnees = [
                'nom'         => trim((string) $_POST['nom']),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'icone'       => $icone,
                'ordre'       => admin_entier($_POST['ordre'] ?? 0),
                'actif'       => isset($_POST['actif']) ? 1 : 0,
            ];

            if ($id > 0) {
                $donnees['slug'] = slug_unique((string) $_POST['nom'], 'categories', $id);
                $donnees['id'] = $id;
                db()->run('UPDATE categories SET nom=:nom, slug=:slug, description=:description, icone=:icone, ordre=:ordre, actif=:actif WHERE id=:id', $donnees);
                Flash::success('La catégorie a été mise à jour.');
            } else {
                $donnees['slug'] = slug_unique((string) $_POST['nom'], 'categories');
                db()->run('INSERT INTO categories (nom, slug, description, icone, ordre, actif) VALUES (:nom, :slug, :description, :icone, :ordre, :actif)', $donnees);
                Flash::success('La catégorie a été ajoutée.');
            }
            admin_redirect('categories.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$categorie = $editId > 0 ? db()->one('SELECT * FROM categories WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $categorie !== null || $erreur !== null;

$categories = db()->all(
    'SELECT c.*, (SELECT COUNT(*) FROM plats p WHERE p.categorie_id = c.id) AS nb_plats FROM categories c ORDER BY c.ordre, c.id'
);

$valeurs = static function (string $champ, mixed $defaut = '') use ($categorie): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $categorie[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($categorie): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($categorie[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Catégories';
$admin_actif = 'categories';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $categorie ? 'Modifier une catégorie' : 'Nouvelle catégorie' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('categories.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('categories.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouvelle catégorie</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('categories.php')) ?>" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($categorie['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" required maxlength="100" value="<?= e($valeurs('nom')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="icone">Icône</label>
                        <select id="icone" name="icone">
                            <?php foreach ($icones as $i): ?>
                                <option value="<?= e($i) ?>" <?= (string) $valeurs('icone', 'plat') === $i ? 'selected' : '' ?>><?= e($i) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-champ adm-champ--large">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" maxlength="255" value="<?= e($valeurs('description')) ?>">
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="ordre">Ordre d'affichage</label>
                        <input type="number" id="ordre" name="ordre" min="0" value="<?= e($valeurs('ordre', 0)) ?>">
                    </div>
                    <div class="adm-champ">
                        <label>État</label>
                        <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Active (visible sur le site)</label>
                    </div>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $categorie ? 'Enregistrer' : 'Ajouter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete"><h2>Catégories (<?= count($categories) ?>)</h2></div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($categories): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Ordre</th><th>Nom</th><th>Description</th><th class="num">Plats</th><th>État</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td><?= (int) $c['ordre'] ?></td>
                                <td><strong><?= e($c['nom']) ?></strong><br><small style="color:var(--brun-doux)"><?= e($c['slug']) ?></small></td>
                                <td><?= e((string) $c['description']) ?></td>
                                <td class="num"><?= (int) $c['nb_plats'] ?></td>
                                <td>
                                    <?php if ((int) $c['actif'] === 1): ?>
                                        <span class="adm-badge adm-badge--succes">Active</span>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge--neutre">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="adm-table__actions">
                                        <a href="<?= e(admin_url('categories.php?edit=' . (int) $c['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                        <form method="post" action="<?= e(admin_url('categories.php')) ?>" data-confirm="Supprimer la catégorie « <?= e($c['nom']) ?> » ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
            <div class="adm-vide"><strong>Aucune catégorie</strong>Ajoutez une catégorie pour organiser vos plats.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';