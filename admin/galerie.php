<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$sections = [
    'plats'       => 'Plats',
    'restaurant'  => 'Restaurant',
    'preparation' => 'Préparation',
    'evenements'  => 'Événements',
    'traiteur'    => 'Traiteur',
];

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = admin_entier($_POST['id'] ?? 0);
        $photo = db()->one('SELECT image FROM galerie WHERE id = ?', [$id]);
        if ($photo) {
            admin_supprimer_image($photo['image']);
            db()->run('DELETE FROM galerie WHERE id = ?', [$id]);
            Flash::success('La photo a été supprimée.');
        }
        admin_redirect('galerie.php');
    }

    if ($action === 'toggle') {
        $id = admin_entier($_POST['id'] ?? 0);
        db()->run('UPDATE galerie SET actif = 1 - actif WHERE id = ?', [$id]);
        Flash::success('La photo a été mise à jour.');
        admin_redirect('galerie.php');
    }

    if ($action === 'save') {
        $id = admin_entier($_POST['id'] ?? 0);
        $titre = trim((string) ($_POST['titre'] ?? ''));
        $section = in_array((string) ($_POST['section'] ?? ''), array_keys($sections), true) ? (string) $_POST['section'] : 'plats';

        if (mb_strlen($titre) > 150) {
            $erreur = 'Le titre ne doit pas dépasser 150 caractères.';
        }

        $image = null;
        try {
            $image = admin_upload('image', 'galerie');
        } catch (Throwable $ex) {
            $erreur = $ex->getMessage();
        }

        if ($erreur === null && $id === 0 && $image === null) {
            $erreur = 'Veuillez choisir une image à ajouter.';
        }

        if ($erreur === null) {
            $donnees = [
                'titre'   => $titre,
                'section' => $section,
                'ordre'   => admin_entier($_POST['ordre'] ?? 0),
                'actif'   => isset($_POST['actif']) ? 1 : 0,
            ];

            if ($id > 0) {
                $ancien = db()->one('SELECT image FROM galerie WHERE id = ?', [$id]);
                $donnees['id'] = $id;
                $sets = 'titre=:titre, section=:section, ordre=:ordre, actif=:actif';
                if ($image !== null) {
                    $sets .= ', image=:image';
                    $donnees['image'] = $image;
                }
                db()->run("UPDATE galerie SET {$sets} WHERE id=:id", $donnees);
                if ($image !== null && $ancien) {
                    admin_supprimer_image($ancien['image']);
                }
                Flash::success('La photo a été mise à jour.');
            } else {
                $donnees['image'] = $image;
                db()->run('INSERT INTO galerie (titre, image, section, ordre, actif) VALUES (:titre, :image, :section, :ordre, :actif)', $donnees);
                Flash::success('La photo a été ajoutée.');
            }
            admin_redirect('galerie.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$photo = $editId > 0 ? db()->one('SELECT * FROM galerie WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $photo !== null || $erreur !== null;

$filtre = (string) ($_GET['section'] ?? '');
$sql = 'SELECT * FROM galerie';
$params = [];
if (in_array($filtre, array_keys($sections), true)) {
    $sql .= ' WHERE section = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY ordre, id DESC';
$photos = db()->all($sql, $params);

$valeurs = static function (string $champ, mixed $defaut = '') use ($photo): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $photo[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($photo): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($photo[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Galerie';
$admin_actif = 'galerie';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $photo ? 'Modifier la photo' : 'Ajouter une photo' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('galerie.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('galerie.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Ajouter une photo</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('galerie.php')) ?>" enctype="multipart/form-data" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($photo['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="titre">Titre (facultatif)</label>
                        <input type="text" id="titre" name="titre" maxlength="150" value="<?= e($valeurs('titre')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="section">Section</label>
                        <select id="section" name="section">
                            <?php foreach ($sections as $cle => $label): ?>
                                <option value="<?= e($cle) ?>" <?= (string) $valeurs('section', 'plats') === $cle ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="image">Image <?= $photo ? '' : '*' ?> (jpg, png, webp — 4 Mo max)</label>
                        <input type="file" id="image" name="image" accept="image/*" data-aprecu="apercu-photo">
                        <?php if ($photo): ?><small>Laissez vide pour conserver l'image actuelle.</small><?php endif; ?>
                    </div>
                    <div class="adm-champ">
                        <label>Aperçu</label>
                        <img id="apercu-photo" class="adm-apercu" src="<?= e(plat_photo($photo['image'] ?? null)) ?>" alt="Aperçu">
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="ordre">Ordre d'affichage</label>
                        <input type="number" id="ordre" name="ordre" min="0" value="<?= e($valeurs('ordre', 0)) ?>">
                    </div>
                    <div class="adm-champ">
                        <label>État</label>
                        <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Visible dans la galerie</label>
                    </div>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $photo ? 'Enregistrer' : 'Ajouter' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Photos (<?= count($photos) ?>)</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('galerie.php')) ?>" class="adm-filtre <?= $filtre === '' ? 'actif' : '' ?>">Toutes</a>
            <?php foreach ($sections as $cle => $label): ?>
                <a href="<?= e(admin_url('galerie.php?section=' . $cle)) ?>" class="adm-filtre <?= $filtre === $cle ? 'actif' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="adm-carte__corps">
        <?php if ($photos): ?>
            <div class="grille" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem">
                <?php foreach ($photos as $p): ?>
                    <div style="border:1px solid var(--ligne);border-radius:12px;overflow:hidden;background:#fff">
                        <img src="<?= e(plat_photo($p['image'])) ?>" alt="" style="width:100%;height:130px;object-fit:cover">
                        <div style="padding:.6rem">
                            <strong style="font-size:.88rem"><?= e($p['titre'] ?: 'Sans titre') ?></strong>
                            <div style="margin:.4rem 0">
                                <span class="adm-badge adm-badge--or"><?= e($sections[$p['section']] ?? $p['section']) ?></span>
                                <?php if ((int) $p['actif'] !== 1): ?><span class="adm-badge adm-badge--neutre">Masquée</span><?php endif; ?>
                            </div>
                            <div class="adm-table__actions" style="justify-content:flex-start">
                                <a href="<?= e(admin_url('galerie.php?edit=' . (int) $p['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                <form method="post" action="<?= e(admin_url('galerie.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="adm-btn adm-btn--contour adm-btn--sm"><?= (int) $p['actif'] === 1 ? 'Masquer' : 'Afficher' ?></button>
                                </form>
                                <form method="post" action="<?= e(admin_url('galerie.php')) ?>" data-confirm="Supprimer cette photo ?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="adm-vide"><strong>Aucune photo</strong>Ajoutez des photos pour illustrer le restaurant et vos plats.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';