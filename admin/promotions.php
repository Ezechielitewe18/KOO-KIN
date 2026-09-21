<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$plats = db()->all('SELECT id, nom FROM plats ORDER BY nom');
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = admin_entier($_POST['id'] ?? 0);
        $promo = db()->one('SELECT photo FROM promotions WHERE id = ?', [$id]);
        if ($promo) {
            admin_supprimer_image($promo['photo']);
            db()->run('DELETE FROM promotions WHERE id = ?', [$id]);
            Flash::success('La promotion a été supprimée.');
        }
        admin_redirect('promotions.php');
    }

    if ($action === 'toggle') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('UPDATE promotions SET actif = 1 - actif WHERE id = ?', [$id]);
        Flash::success('La promotion a été mise à jour.');
        admin_redirect('promotions.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $v = (new Validation())->make($_POST, [
            'nom'         => ['label' => 'nom', 'rules' => 'required|max:150'],
            'prix_normal' => ['label' => 'prix normal', 'rules' => 'required|numeric'],
            'prix_promo'  => ['label' => 'prix promotionnel', 'rules' => 'required|numeric'],
            'date_debut'  => ['label' => 'date de début', 'rules' => 'date'],
            'date_fin'    => ['label' => 'date de fin', 'rules' => 'date'],
        ]);

        $prixNormal = admin_entier($_POST['prix_normal'] ?? 0);
        $prixPromo = admin_entier($_POST['prix_promo'] ?? 0);
        if ($erreur === null && !$v->fails() && $prixPromo >= $prixNormal) {
            $erreur = 'Le prix promotionnel doit être inférieur au prix normal.';
        }
        if ($erreur === null && !$v->fails() && $prixPromo < 1) {
            $erreur = 'Le prix promotionnel doit être supérieur à zéro.';
        }

        if ($v->fails()) {
            $erreur = $v->first();
        }

        $photo = null;
        if ($erreur === null) {
            try {
                $photo = admin_upload('photo', 'promotions');
            } catch (Throwable $ex) {
                $erreur = $ex->getMessage();
            }
        }

        if ($erreur === null) {
            $platId = admin_entier($_POST['plat_id'] ?? 0) ?: null;
            $donnees = [
                'plat_id'     => $platId,
                'nom'         => trim((string) $_POST['nom']),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'prix_normal' => $prixNormal,
                'prix_promo'  => $prixPromo,
                'date_debut'  => trim((string) ($_POST['date_debut'] ?? '')) === '' ? null : (string) $_POST['date_debut'],
                'date_fin'    => trim((string) ($_POST['date_fin'] ?? '')) === '' ? null : (string) $_POST['date_fin'],
                'actif'       => isset($_POST['actif']) ? 1 : 0,
            ];

            if ($id > 0) {
                $ancien = db()->one('SELECT photo FROM promotions WHERE id = ?', [$id]);
                $donnees['id'] = $id;
                $sets = 'plat_id=:plat_id, nom=:nom, description=:description, prix_normal=:prix_normal, prix_promo=:prix_promo, date_debut=:date_debut, date_fin=:date_fin, actif=:actif';
                if ($photo !== null) {
                    $sets .= ', photo=:photo';
                    $donnees['photo'] = $photo;
                }
                db()->run("UPDATE promotions SET {$sets} WHERE id=:id", $donnees);
                if ($photo !== null && $ancien) {
                    admin_supprimer_image($ancien['photo']);
                }
                Flash::success('La promotion a été mise à jour.');
            } else {
                $donnees['photo'] = $photo;
                db()->run(
                    'INSERT INTO promotions (plat_id, nom, description, prix_normal, prix_promo, photo, date_debut, date_fin, actif)
                     VALUES (:plat_id, :nom, :description, :prix_normal, :prix_promo, :photo, :date_debut, :date_fin, :actif)',
                    $donnees
                );
                Flash::success('La promotion a été ajoutée.');
            }
            admin_redirect('promotions.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$promo = $editId > 0 ? db()->one('SELECT * FROM promotions WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $promo !== null || $erreur !== null;

$promotions = db()->all('SELECT p.*, pl.nom AS plat_nom FROM promotions p LEFT JOIN plats pl ON pl.id = p.plat_id ORDER BY p.id DESC');

$valeurs = static function (string $champ, mixed $defaut = '') use ($promo): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $promo[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($promo): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($promo[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Promotions';
$admin_actif = 'promotions';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $promo ? 'Modifier la promotion' : 'Nouvelle promotion' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('promotions.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('promotions.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouvelle promotion</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('promotions.php')) ?>" enctype="multipart/form-data" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($promo['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="nom">Nom de l'offre *</label>
                        <input type="text" id="nom" name="nom" required maxlength="150" value="<?= e($valeurs('nom')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="plat_id">Plat associé (facultatif)</label>
                        <select id="plat_id" name="plat_id">
                            <option value="">— Aucun —</option>
                            <?php foreach ($plats as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= (string) $valeurs('plat_id') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Permet au client d'ajouter directement le plat au panier.</small>
                    </div>
                </div>

                <div class="adm-form__ligne--3 adm-form__ligne">
                    <div class="adm-champ">
                        <label for="prix_normal">Prix normal (CDF) *</label>
                        <input type="number" id="prix_normal" name="prix_normal" required min="0" step="100" value="<?= e($valeurs('prix_normal')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="prix_promo">Prix promotionnel (CDF) *</label>
                        <input type="number" id="prix_promo" name="prix_promo" required min="0" step="100" value="<?= e($valeurs('prix_promo')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="photo">Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/*" data-aprecu="apercu-promo">
                    </div>
                </div>

                <div class="adm-champ adm-champ--large">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" maxlength="2000"><?= e($valeurs('description')) ?></textarea>
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

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label>Aperçu</label>
                        <img id="apercu-promo" class="adm-apercu" src="<?= e(plat_photo($promo['photo'] ?? null)) ?>" alt="Aperçu">
                    </div>
                    <div class="adm-champ">
                        <label>État</label>
                        <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Promotion active (visible sur le site)</label>
                    </div>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $promo ? 'Enregistrer' : 'Ajouter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete"><h2>Promotions (<?= count($promotions) ?>)</h2></div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($promotions): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th></th><th>Offre</th><th class="num">Prix</th><th>Période</th><th>État</th><th class="num">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $p): ?>
                            <tr>
                                <td><img class="adm-table__media" src="<?= e(plat_photo($p['photo'] ?? null)) ?>" alt=""></td>
                                <td>
                                    <strong><?= e($p['nom']) ?></strong>
                                    <?php if ($p['plat_nom']): ?><br><small style="color:var(--brun-doux)">Plat : <?= e($p['plat_nom']) ?></small><?php endif; ?>
                                </td>
                                <td class="num"><span class="prix--barre" style="display:block"><?= e(format_prix((int) $p['prix_normal'])) ?></span><strong><?= e(format_prix((int) $p['prix_promo'])) ?></strong></td>
                                <td><?= $p['date_debut'] ? e(date('d/m/Y', strtotime((string) $p['date_debut']))) : '—' ?> → <?= $p['date_fin'] ? e(date('d/m/Y', strtotime((string) $p['date_fin']))) : '—' ?></td>
                                <td>
                                    <?php if ((int) $p['actif'] === 1): ?>
                                        <span class="adm-badge adm-badge--succes">Active</span>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge--neutre">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="adm-table__actions">
                                        <a href="<?= e(admin_url('promotions.php?edit=' . (int) $p['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                        <form method="post" action="<?= e(admin_url('promotions.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn--contour adm-btn--sm"><?= (int) $p['actif'] === 1 ? 'Désactiver' : 'Activer' ?></button>
                                        </form>
                                        <form method="post" action="<?= e(admin_url('promotions.php')) ?>" data-confirm="Supprimer la promotion « <?= e($p['nom']) ?> » ?">
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
            <div class="adm-vide"><strong>Aucune promotion</strong>Créez une offre pour la mettre en avant.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';