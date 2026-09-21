<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireSuperadmin();

$roles = ['superadmin' => 'Super administrateur', 'admin' => 'Administrateur'];
$erreur = null;

$nbSuperadminsActifs = static fn (?int $exclure = null): int => (int) db()->value(
    "SELECT COUNT(*) FROM admins WHERE role = 'superadmin' AND actif = 1" . ($exclure !== null ? ' AND id <> ?' : ''),
    $exclure !== null ? [$exclure] : []
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');
    $id = admin_entier($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $cible = db()->one('SELECT * FROM admins WHERE id = ?', [$id]);
        if ($cible === null) {
            Flash::error('Compte introuvable.');
        } elseif ($id === Auth::id()) {
            Flash::error('Vous ne pouvez pas supprimer votre propre compte.');
        } elseif ($cible['role'] === 'superadmin' && (int) $cible['actif'] === 1 && $nbSuperadminsActifs($id) === 0) {
            Flash::error('Impossible de supprimer le dernier super administrateur actif.');
        } else {
            db()->run('DELETE FROM admins WHERE id = ?', [$id]);
            Flash::success('Le compte a été supprimé.');
        }
        admin_redirect('comptes.php');
    }

    if ($action === 'save') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
        $role = in_array((string) ($_POST['role'] ?? ''), array_keys($roles), true) ? (string) $_POST['role'] : 'admin';
        $actif = isset($_POST['actif']) ? 1 : 0;

        $v = (new Validation())->make(
            ['username' => $username, 'email' => $email, 'mot_de_passe' => $motDePasse],
            [
                'username'     => ['label' => 'identifiant', 'rules' => 'required|max:50'],
                'email'        => ['label' => 'email', 'rules' => 'email'],
                'mot_de_passe' => ['label' => 'mot de passe', 'rules' => 'min:8'],
            ]
        );

        if ($v->fails()) {
            $erreur = $v->first();
        } elseif ($id === 0 && $motDePasse === '') {
            $erreur = 'Un mot de passe est requis pour créer un compte.';
        } elseif ((int) db()->value('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?', [$username, $id]) > 0) {
            $erreur = 'Cet identifiant est déjà utilisé.';
        } elseif ($email !== '' && (int) db()->value('SELECT COUNT(*) FROM admins WHERE email = ? AND id <> ?', [$email, $id]) > 0) {
            $erreur = 'Cet email est déjà utilisé.';
        } elseif ($id > 0 && ($role !== 'superadmin' || $actif === 0)) {
            $cible = db()->one('SELECT * FROM admins WHERE id = ?', [$id]);
            if ($cible !== null && $cible['role'] === 'superadmin' && (int) $cible['actif'] === 1 && $nbSuperadminsActifs($id) === 0) {
                $erreur = 'Impossible de désactiver ou rétrograder le dernier super administrateur actif.';
            }
        }

        if ($erreur === null) {
            $donnees = [
                'username' => $username,
                'email'    => $email !== '' ? $email : null,
                'role'     => $role,
                'actif'    => $actif,
            ];

            if ($id > 0) {
                $donnees['id'] = $id;
                db()->run('UPDATE admins SET username=:username, email=:email, role=:role, actif=:actif WHERE id=:id', $donnees);
                if ($motDePasse !== '') {
                    Auth::changerMotDePasse($id, $motDePasse);
                }
                Flash::success('Le compte a été mis à jour.');
            } else {
                $donnees['password_hash'] = password_hash($motDePasse, PASSWORD_DEFAULT);
                db()->run(
                    'INSERT INTO admins (username, email, password_hash, role, actif) VALUES (:username, :email, :password_hash, :role, :actif)',
                    $donnees
                );
                Flash::success('Le compte a été créé.');
            }
            admin_redirect('comptes.php');
        }
    }
}

$editId = admin_entier($_GET['edit'] ?? 0);
$nouveau = isset($_GET['nouveau']);
$compte = $editId > 0 ? db()->one('SELECT * FROM admins WHERE id = ?', [$editId]) : null;
$afficherFormulaire = $nouveau || $compte !== null || $erreur !== null;

$admins = db()->all('SELECT * FROM admins ORDER BY role, username');

$valeurs = static function (string $champ, mixed $defaut = '') use ($compte): mixed {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST[$champ] ?? $defaut;
    }
    return $compte[$champ] ?? $defaut;
};
$coche = static function (string $champ, int $defaut = 1) use ($compte): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$champ]);
    }
    return (int) ($compte[$champ] ?? $defaut) === 1;
};

$admin_titre = 'Comptes administrateurs';
$admin_actif = 'comptes';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2><?= $compte ? 'Modifier le compte' : 'Nouveau compte' ?></h2>
        <div class="adm-actions">
            <?php if ($afficherFormulaire): ?>
                <a href="<?= e(admin_url('comptes.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Annuler</a>
            <?php else: ?>
                <a href="<?= e(admin_url('comptes.php?nouveau=1')) ?>" class="adm-btn adm-btn--or adm-btn--sm"><?= icone('plus') ?> Nouveau compte</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($afficherFormulaire): ?>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>
            <form method="post" action="<?= e(admin_url('comptes.php')) ?>" class="adm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($compte['id'] ?? 0) ?>">

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="username">Identifiant *</label>
                        <input type="text" id="username" name="username" required maxlength="50" autocomplete="off" value="<?= e($valeurs('username')) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="190" autocomplete="off" value="<?= e($valeurs('email')) ?>">
                    </div>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="role">Rôle</label>
                        <select id="role" name="role">
                            <?php foreach ($roles as $cle => $label): ?>
                                <option value="<?= e($cle) ?>" <?= (string) $valeurs('role', 'admin') === $cle ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Le super administrateur peut gérer les comptes ; l'administrateur gère le contenu et les commandes.</small>
                    </div>
                    <div class="adm-champ">
                        <label for="mot_de_passe"><?= $compte ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe *' ?></label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" <?= $compte ? '' : 'required' ?> minlength="8" autocomplete="new-password">
                        <small>8 caractères minimum.</small>
                    </div>
                </div>

                <label class="adm-case"><input type="checkbox" name="actif" <?= $coche('actif') ? 'checked' : '' ?>> Compte actif (autorisé à se connecter)</label>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or"><?= $compte ? 'Enregistrer' : 'Créer le compte' ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete"><h2>Comptes (<?= count($admins) ?>)</h2></div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <div class="adm-table__wrap">
            <table class="adm-table">
                <thead>
                    <tr><th>Identifiant</th><th>Email</th><th>Rôle</th><th>État</th><th>Créé le</th><th class="num">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                        <tr>
                            <td><strong><?= e($a['username']) ?></strong><?= (int) $a['id'] === Auth::id() ? ' <span class="adm-badge adm-badge--or">Vous</span>' : '' ?></td>
                            <td><?= e((string) $a['email']) ?></td>
                            <td>
                                <?php if ($a['role'] === 'superadmin'): ?>
                                    <span class="adm-badge adm-badge--or">Super admin</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge--neutre">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $a['actif'] === 1): ?>
                                    <span class="adm-badge adm-badge--succes">Actif</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge--erreur">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('d/m/Y', strtotime((string) $a['created_at']))) ?></td>
                            <td>
                                <div class="adm-table__actions">
                                    <a href="<?= e(admin_url('comptes.php?edit=' . (int) $a['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Modifier</a>
                                    <?php if ((int) $a['id'] !== Auth::id()): ?>
                                        <form method="post" action="<?= e(admin_url('comptes.php')) ?>" data-confirm="Supprimer le compte « <?= e($a['username']) ?> » ?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                            <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';