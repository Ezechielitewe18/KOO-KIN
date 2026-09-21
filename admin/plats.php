<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$categories = db()->all('SELECT id, nom FROM categories ORDER BY ordre, id');

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = admin_entier($_POST['id'] ?? 0);
        $plat = db()->one('SELECT photo FROM plats WHERE id = ?', [$id]);
        if ($plat) {
            admin_supprimer_image($plat['photo']);
            db()->run('DELETE FROM plats WHERE id = ?', [$id]);
            Flash::success('Le plat a été supprimé.');
        }
        admin_redirect('plats.php');
    }

    if ($action === 'toggle') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('UPDATE plats SET disponible = 1 - disponible WHERE id = ?', [$id]);
        Flash::success('Disponibilité mise à jour.');
        admin_redirect('plats.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $v = (new Validation())->make($_POST, [
            'nom'          => ['label' => 'nom', 'rules' => 'required|max:150'],
            'categorie_id' => ['label' => 'catégorie', 'rules' => 'required|int'],
            'prix'         => ['label' => 'prix', 'rules' => 'required|numeric'],
            'description'  => ['label' => 'description', 'rules' => 'max:2000'],
            'ordre'        => ['label' => 'ordre', 'rules' => 'int'],
        ]);

        $categorieId = admin_entier($_POST['categorie_id'] ?? 0);
        if (!$v->fails() && db()->value('SELECT COUNT(*) FROM categories WHERE id = ?', [$categorieId]) === 0) {
            $erreur = 'La catégorie sélectionnée est invalide.';
        }

        if ($v->fails()) {
            $erreur = $v->first();
        }

        $photo = null;
        if ($erreur === null) {
            try {
                $photo = admin_upload('photo', 'plats');
            } catch (Throwable $ex) {
                $erreur = $ex->getMessage();
            }
        }

        if ($erreur === null) {
            $donnees = [
                'categorie_id' => $categorieId,
                'nom'          => trim((string) $_POST['nom']),
                'description'  => trim((string) ($_POST['description'] ?? '')),
                'prix'         => admin_entier($_POST['prix'] ?? 0),
                'disponible'   => isset($_POST['disponible']) ? 1 : 0,
                'populaire'    => isset($_POST['populaire']) ? 1 : 0,
                'vegan'        => isset($_POST['vegan']) ? 1 : 0,
                'epice'        => isset($_POST['epice']) ? 1 : 0,
                'ordre'        => admin_entier($_POST['ordre'] ?? 0),
            ];

            if ($id > 0) {
                $ancien = db()->one('SELECT photo FROM plats WHERE id = ?', [$id]);
                $donnees['slug'] = slug_unique((string) $_POST['nom'], 'plats', $id);
                $sets = 'categorie_id=:categorie_id, nom=:nom, slug=:slug, description=:description, prix=:prix, disponible=:disponible, populaire=:populaire, vegan=:vegan, `épicé`=:epice, ordre=:ordre';
                $params = $donnees;
                $params['id'] = $id;
                if ($photo !== null) {
                    $sets .= ', photo=:photo';
                    $params['photo'] = $photo;
                }
                db()->run("UPDATE plats SET {$sets} WHERE id = :id", $params);
                if ($photo !== null && $ancien) {
                    admin_supprimer_image($ancien['photo']);
                }
                Flash::success('Le plat a été mis à jour.');
            } else {
                $donnees['slug'] = slug_unique((string) $_POST['nom'], 'plats');
                $donnees['photo'] = $photo;
                db()->run(
                    'INSERT INTO plats (categorie_id, nom, slug, description, prix, photo, disponible, populaire, vegan, `épicé`, ordre)
                     VALUES (:categorie_id, :nom, :slug, :description, :prix, :photo, :disponible, :populaire, :vegan, :epice, :ordre)',
                    $donnees
                );
                Flash::success('Le plat a été ajouté.');
            }
            admin_redirect('plats.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$plat = $editId > 0 ? db()->one('SELECT * FROM plats WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $plat !== null || $erreur !== null;

$categorieFiltre = admin_entier($_GET['cat'] ?? 0);
if ($categorieFiltre > 0) {
    $plats = db()->all(
        'SELECT p.*, c.nom AS categorie_nom FROM plats p JOIN categories c ON c.id = p.categorie_id WHERE p.categorie_id = ? ORDER BY c.ordre, p.ordre, p.id',
        [$categorieFiltre]
    );
} else {
    $plats = db()->all('SELECT p.*, c.nom AS categorie_nom FROM plats p JOIN categories c ON c.id = p.categorie_id ORDER BY c.ordre, p.ordre, p.id');
}

$valeurs = static function (string $champ, mixed $defaut = '') use ($plat): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $plat[$champ] ?? $defaut;
};

$coche = static function (string $champ, int $defaut = 0) use ($plat): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($plat[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Plats';
$admin_actif = 'plats';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $plat ? 'Modifier un plat' : 'Nouveau plat' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('plats.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('plats.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouveau plat</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('plats.php')) ?>" enctype="multipart/form-data" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($plat['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="nom">Nom du plat *</label>
                        <input type="text" id="nom" name="nom" required maxlength="150" value="<?= e($valeurs('nom')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="categorie_id">Catégorie *</label>
                        <select id="categorie_id" name="categorie_id" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (string) $valeurs('categorie_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="prix">Prix (CDF) *</label>
                        <input type="number" id="prix" name="prix" required min="0" step="100" value="<?= e($valeurs('prix')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="ordre">Ordre d'affichage</label>
                        <input type="number" id="ordre" name="ordre" min="0" value="<?= e($valeurs('ordre', 0)) ?>">
                    </div>
                </div>

                <div class="adm-champ adm-champ--large">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" maxlength="2000"><?= e($valeurs('description')) ?></textarea>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="photo">Photo (jpg, png, webp — 4 Mo max)</label>
                        <input type="file" id="photo" name="photo" accept="image/*" data-aprecu="apercu-plat">
                        <small>Laissez vide pour conserver la photo actuelle.</small>
                    </div>
                    <div class="adm-champ">
                        <label>Aperçu</label>
                        <img id="apercu-plat" class="adm-apercu" src="<?= e(plat_photo($plat['photo'] ?? null)) ?>" alt="Aperçu">
                    </div>
                </div>

                <div class="adm-actions">
                    <label class="adm-case"><input type="checkbox" name="disponible" <?= $coche('disponible', 1) ? 'checked' : '' ?>> Disponible</label>
                    <label class="adm-case"><input type="checkbox" name="populaire" <?= $coche('populaire') ? 'checked' : '' ?>> Populaire</label>
                    <label class="adm-case"><input type="checkbox" name="vegan" <?= $coche('vegan') ? 'checked' : '' ?>> Végétarien</label>
                    <label class="adm-case"><input type="checkbox" name="epice" <?= $coche('epice') ? 'checked' : '' ?>> Épicé</label>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $plat ? 'Enregistrer les modifications' : 'Ajouter le plat' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Tous les plats (<?= count($plats) ?>)</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('plats.php')) ?>" class="adm-filtre <?= $categorieFiltre === 0 ? 'actif' : '' ?>">Tout</a>
            <?php foreach ($categories as $c): ?>
                <a href="<?= e(admin_url('plats.php?cat=' . (int) $c['id'])) ?>" class="adm-filtre <?= $categorieFiltre === (int) $c['id'] ? 'actif' : '' ?>"><?= e($c['nom']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($plats): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th></th><th>Plat</th><th>Catégorie</th><th class="num">Prix</th><th>État</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plats as $p): ?>
                            <tr>
                                <td><img class="adm-table__media" src="<?= e(plat_photo($p['photo'])) ?>" alt=""></td>
                                <td>
                                    <strong><?= e($p['nom']) ?></strong>
                                    <?php if ($p['description']): ?><br><small style="color:var(--brun-doux)"><?= e(mb_strimwidth($p['description'], 0, 70, '…')) ?></small><?php endif; ?>
                                </td>
                                <td><?= e($p['categorie_nom']) ?></td>
                                <td class="num"><?= e(format_prix((int) $p['prix'])) ?></td>
                                <td>
                                    <?php if ((int) $p['disponible'] === 1): ?>
                                        <span class="adm-badge adm-badge--succes">Disponible</span>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge--erreur">Indisponible</span>
                                    <?php endif; ?>
                                    <?php if ((int) $p['populaire'] === 1): ?><span class="adm-badge adm-badge--or">Populaire</span><?php endif; ?>
                                    <?php if ((int) $p['vegan'] === 1): ?><span class="adm-badge adm-badge--info">Végé</span><?php endif; ?>
                                    <?php if ((int) $p['épicé'] === 1): ?><span class="adm-badge adm-badge--erreur">Épicé</span><?php endif; ?>
                                </td>
                                <td>
                                    <div class="adm-table__actions">
                                        <a href="<?= e(admin_url('plats.php?edit=' . (int) $p['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                        <form method="post" action="<?= e(admin_url('plats.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn--contour adm-btn--sm"><?= (int) $p['disponible'] === 1 ? 'Rendre indispo.' : 'Rendre dispo.' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(admin_url('plats.php')) ?>" data-confirm="Supprimer définitivement « <?= e($p['nom']) ?> » ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
            <div class="adm-vide"><strong>Aucun plat</strong>Ajoutez votre premier plat pour commencer.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';