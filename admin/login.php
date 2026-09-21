<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

if (Auth::check()) {
    admin_redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    csrf_check();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        Flash::error('Veuillez renseigner votre identifiant et votre mot de passe.');
    } elseif (Auth::bloque($username)) {
        Flash::error('Compte temporairement bloqué après plusieurs tentatives échouées. Réessayez dans quelques minutes.');
    } elseif (Auth::attempt($username, $password)) {
        $connecte = Auth::user();
        Flash::success('Bienvenue, ' . (string) ($connecte['username'] ?? $username) . '.');
        admin_redirect('index.php');
    } else {
        Flash::error('Identifiant ou mot de passe incorrect.');
    }
    admin_redirect('login.php');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion — Administration KOO-KIN</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/logo.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="login-page">
    <div class="login-carte">
        <div class="login-carte__logo">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="KOO-KIN">
        </div>
        <h1>Administration</h1>
        <p class="sous">Espace réservé à l'équipe KOO-KIN</p>

        <?php foreach (flash_error() as $m): ?><div class="adm-alerte adm-alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
        <?php foreach (flash_info() as $m): ?><div class="adm-alerte adm-alerte--info"><?= e($m) ?></div><?php endforeach; ?>
        <?php foreach (flash_success() as $m): ?><div class="adm-alerte adm-alerte--succes"><?= e($m) ?></div><?php endforeach; ?>

        <form method="post" action="<?= e(admin_url('login.php')) ?>" class="adm-form">
            <?= csrf_field() ?>
            <div class="adm-champ">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus value="<?= e(old('username')) ?>">
            </div>
            <div class="adm-champ">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" name="login" value="1" class="adm-btn adm-btn--or adm-btn--bloc">Se connecter</button>
        </form>

        <p class="login-note">Accès sécurisé — KOO-KIN, Kintambo · Kinshasa</p>
    </div>
</div>
</body>
</html>
