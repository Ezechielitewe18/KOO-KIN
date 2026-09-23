<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\ClientAuth;
use KooKin\Core\Validation;
use KooKin\Core\Flash;

if (ClientAuth::check()) {
    redirect('mon-compte.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    csrf_check();

    $v = (new Validation())->make($_POST, [
        'telephone' => ['label' => 'téléphone', 'rules' => 'required|phone'],
        'mot_de_passe' => ['label' => 'mot de passe', 'rules' => 'required'],
    ]);

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('connexion.php');
    }

    $d = $v->data();

    if (ClientAuth::attempt((string) $d['telephone'], (string) $d['mot_de_passe'])) {
        Flash::success('Bon retour parmi nous !');
        redirect(ClientAuth::retour());
    }

    keep_old($_POST);
    Flash::error('Numéro de téléphone ou mot de passe incorrect.');
    redirect('connexion.php');
}

$page = [
    'titre' => 'Connexion — KOO-KIN',
    'actif' => 'connexion',
    'description' => 'Connectez-vous à votre espace client KOO-KIN pour commander et suivre vos commandes.',
    'fil' => 'Connexion',
];

require BASE_PATH . 'includes/front/header.php';
?>

<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Connexion</p>
        <h1>Connexion</h1>
        <p>Connectez-vous pour commander et retrouver votre historique.</p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
        <?php foreach (flash_success() as $m): ?><div class="alerte alerte--succes"><?= e($m) ?></div><?php endforeach; ?>

        <div class="carte-form animer" style="max-width:480px;margin-inline:auto">
            <form method="post" action="<?= e(url('connexion.php')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="champ">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= e(old('telephone')) ?>" required placeholder="+243 ..." autocomplete="tel">
                </div>
                <div class="champ">
                    <label for="mot_de_passe">Mot de passe *</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
                </div>
                <button type="submit" name="connexion" value="1" class="btn btn--or btn--bloc">Se connecter</button>
            </form>
            <p class="texte-centre" style="margin-top:1.2rem">
                Pas encore de compte ?
                <a href="<?= e(url('inscription.php')) ?>">Créez-en un en quelques secondes</a>.
            </p>
        </div>
    </div>
</section>

<?php
require BASE_PATH . 'includes/front/footer.php';