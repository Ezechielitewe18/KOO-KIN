<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;
use KooKin\Core\Validation;

Auth::requireLogin();

$admin = db()->one('SELECT * FROM admins WHERE id = ?', [Auth::id()]);
if ($admin === null) {
    Auth::logout();
    admin_redirect('login.php');
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $actuel = (string) ($_POST['actuel'] ?? '');
    $nouveau = (string) ($_POST['nouveau'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    $v = (new Validation())->make(
        ['username' => $username, 'email' => $email, 'nouveau' => $nouveau],
        [
            'username' => ['label' => 'identifiant', 'rules' => 'required|max:50'],
            'email'    => ['label' => 'email', 'rules' => 'email'],
            'nouveau'  => ['label' => 'nouveau mot de passe', 'rules' => 'min:8'],
        ]
    );

    if ($actuel === '' || !password_verify($actuel, $admin['password_hash'])) {
        $erreur = 'Votre mot de passe actuel est incorrect.';
    } elseif ($v->fails()) {
        $erreur = $v->first();
    } elseif ($nouveau !== '' && $nouveau !== $confirmation) {
        $erreur = 'La confirmation ne correspond pas au nouveau mot de passe.';
    } elseif ((int) db()->value('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?', [$username, (int) $admin['id']]) > 0) {
        $erreur = 'Cet identifiant est déjà utilisé.';
    } elseif ($email !== '' && (int) db()->value('SELECT COUNT(*) FROM admins WHERE email = ? AND id <> ?', [$email, (int) $admin['id']]) > 0) {
        $erreur = 'Cet email est déjà utilisé.';
    } else {
        db()->run(
            'UPDATE admins SET username = ?, email = ? WHERE id = ?',
            [$username, $email !== '' ? $email : null, (int) $admin['id']]
        );

        if ($nouveau !== '') {
            Auth::changerMotDePasse((int) $admin['id'], $nouveau);
        }

        Auth::verifier();
        Flash::success($nouveau !== '' ? 'Vos informations et votre mot de passe ont été mis à jour.' : 'Vos informations ont été mises à jour.');
        admin_redirect('compte.php');
    }
}

$admin_titre = 'Mon compte';
$admin_actif = 'compte';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-detail">
    <div class="adm-carte">
        <div class="adm-carte__entete"><h2>Mes informations</h2></div>
        <div class="adm-carte__corps">
            <?php if ($erreur !== null): ?><div class="adm-alerte adm-alerte--erreur"><?= e($erreur) ?></div><?php endif; ?>

            <form method="post" action="<?= e(admin_url('compte.php')) ?>" class="adm-form">
                <?= csrf_field() ?>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="username">Identifiant *</label>
                        <input type="text" id="username" name="username" required maxlength="50" autocomplete="username" value="<?= e((string) $admin['username']) ?>">
                    </div>
                    <div class="adm-champ">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="190" autocomplete="email" value="<?= e((string) $admin['email']) ?>">
                    </div>
                </div>

                <div class="adm-champ adm-champ--large">
                    <label for="actuel">Mot de passe actuel *</label>
                    <input type="password" id="actuel" name="actuel" required autocomplete="current-password">
                    <small>Requis pour enregistrer toute modification.</small>
                </div>

                <div class="adm-form__ligne">
                    <div class="adm-champ">
                        <label for="nouveau">Nouveau mot de passe</label>
                        <input type="password" id="nouveau" name="nouveau" minlength="8" autocomplete="new-password">
                        <small>8 caractères minimum. Laissez vide pour ne pas changer.</small>
                    </div>
                    <div class="adm-champ">
                        <label for="confirmation">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirmation" name="confirmation" minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="adm-actions">
                    <button type="submit" class="adm-btn adm-btn--or">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="adm-carte">
        <div class="adm-carte__entete"><h2>Sécurité du compte</h2></div>
        <div class="adm-carte__corps">
            <ul class="adm-info">
                <li><span>Rôle</span><span><?= e((string) $admin['role']) ?></span></li>
                <li><span>Compte créé le</span><span><?= e(date('d/m/Y', strtotime((string) $admin['created_at']))) ?></span></li>
                <li><span>Statut</span><span><?= (!empty($admin['bloque_jusqua']) && strtotime((string) $admin['bloque_jusqua']) > time()) ? 'Temporairement bloqué' : 'Actif' ?></span></li>
            </ul>
            <p class="texte-doux" style="font-size:.85rem;margin-bottom:0">Pour votre sécurité, choisissez un mot de passe unique et évitez de le partager. Vous serez déconnecté automatiquement après une longue période d'inactivité.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';