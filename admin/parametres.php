<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$zonesTexte = ['site_description', 'livraison_texte', 'horaires_texte', 'histoire', 'traiteur_texte', 'footer_texte'];
$zonesLongues = ['map_embed'];

$parametres = db()->all('SELECT * FROM parametres_site ORDER BY id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $valeurs = $_POST['param'] ?? [];
    if (!is_array($valeurs)) {
        $valeurs = [];
    }

    foreach ($parametres as $p) {
        $cle = (string) $p['cle'];
        if (!array_key_exists($cle, $valeurs)) {
            continue;
        }
        $valeur = trim((string) $valeurs[$cle]);
        db()->run('UPDATE parametres_site SET valeur = ? WHERE cle = ?', [$valeur, $cle]);
    }

    Flash::success('Les paramètres ont été enregistrés.');
    admin_redirect('parametres.php');
}

$admin_titre = 'Paramètres du site';
$admin_actif = 'parametres';

require __DIR__ . '/includes/header.php';
?>

<form method="post" action="<?= e(admin_url('parametres.php')) ?>" class="adm-form">
    <?= csrf_field() ?>

    <div class="adm-carte">
        <div class="adm-carte__entete">
            <h2>Informations du site</h2>
            <div class="adm-actions">
                <button type="submit" class="adm-btn adm-btn--or adm-btn--sm">Enregistrer</button>
            </div>
        </div>
        <div class="adm-carte__corps">
            <div class="adm-form">
                <?php foreach ($parametres as $p): $cle = (string) $p['cle']; ?>
                    <div class="adm-champ adm-champ--large">
                        <label for="p-<?= e($cle) ?>">
                            <?= e($p['description'] ?: $cle) ?>
                            <small><?= e($cle) ?></small>
                        </label>
                        <?php if (in_array($cle, $zonesLongues, true)): ?>
                            <textarea id="p-<?= e($cle) ?>" name="param[<?= e($cle) ?>]" rows="4"><?= e((string) $p['valeur']) ?></textarea>
                        <?php elseif (in_array($cle, $zonesTexte, true)): ?>
                            <textarea id="p-<?= e($cle) ?>" name="param[<?= e($cle) ?>]" rows="3"><?= e((string) $p['valeur']) ?></textarea>
                        <?php else: ?>
                            <input type="text" id="p-<?= e($cle) ?>" name="param[<?= e($cle) ?>]" value="<?= e((string) $p['valeur']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn--or">Enregistrer les paramètres</button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php';