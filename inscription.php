<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\ClientAuth;
use KooKin\Core\Validation;
use KooKin\Core\Flash;

if (ClientAuth::check()) {
    redirect('mon-compte.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscription'])) {
    csrf_check();

    $rules = [
        'nom'         => ['label' => 'nom complet', 'rules' => 'required|max:150'],
        'telephone'   => ['label' => 'téléphone', 'rules' => 'required|phone'],
        'mot_de_passe'=> ['label' => 'mot de passe', 'rules' => 'required|min:8'],
        'confirmation'=> ['label' => 'confirmation du mot de passe', 'rules' => 'required'],
    ];

    if (trim((string) ($_POST['email'] ?? '')) !== '') {
        $rules['email'] = ['label' => 'email', 'rules' => 'email'];
    }

    $v = (new Validation())->make($_POST, $rules);

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('inscription.php');
    }

    $d = $v->data();

    if ((string) $d['mot_de_passe'] !== (string) $d['confirmation']) {
        keep_old($_POST);
        Flash::error('La confirmation du mot de passe ne correspond pas.');
        redirect('inscription.php');
    }

    [$ok, $erreur] = ClientAuth::register(
        (string) $d['nom'],
        (string) $d['telephone'],
        trim((string) ($d['email'] ?? '')),
        (string) $d['mot_de_passe']
    );

    if (!$ok) {
        keep_old($_POST);
        Flash::error($erreur ?? 'Inscription impossible. Réessayez.');
        redirect('inscription.php');
    }

    Flash::success('Votre compte client a été créé. Bienvenue !');
    redirect(ClientAuth::retour());
}

$page = [
    'titre' => 'Inscription — KOO-KIN',
    'actif' => 'inscription',
    'description' => 'Créez votre compte client KOO-KIN pour commander en quelques secondes.',
    'fil' => 'Inscription',
];

require BASE_PATH . 'includes/front/header.php';
?>

<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Inscription</p>
        <h1>Créer mon compte</h1>
        <p>Vos informations sont utilisées uniquement pour vos commandes et réservations.</p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>

        <div class="carte-form animer" style="max-width:480px;margin-inline:auto">
            <form method="post" action="<?= e(url('inscription.php')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="champ">
                    <label for="nom">Nom complet *</label>
                    <input type="text" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="150" autocomplete="name">
                </div>
                <div class="champ">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= e(old('telephone')) ?>" required placeholder="+243 ..." autocomplete="tel">
                </div>
                <div class="champ">
                    <label for="email">Email (optionnel)</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email">
                </div>
                <div class="champ">
                    <label for="mot_de_passe">Mot de passe *</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password">
                </div>
                <div class="champ">
                    <label for="confirmation">Confirmer le mot de passe *</label>
                    <input type="password" id="confirmation" name="confirmation" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" name="inscription" value="1" class="btn btn--or btn--bloc">Créer mon compte</button>
            </form>
            <p class="texte-centre" style="margin-top:1.2rem">
                Déjà inscrit ?
                <a href="<?= e(url('connexion.php')) ?>">Connectez-vous</a>.
            </p>
        </div>
    </div>
</section>

<?php
require BASE_PATH . 'includes/front/footer.php';