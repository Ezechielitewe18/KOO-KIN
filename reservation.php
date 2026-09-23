<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Validation;
use KooKin\Core\Flash;
use KooKin\Core\ClientAuth;

ClientAuth::requireLogin();
$cl = ClientAuth::client();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation'])) {
    csrf_check();

    $v = (new Validation())->make($_POST, [
        'nom'             => ['label' => 'nom', 'rules' => 'required|max:150'],
        'telephone'       => ['label' => 'téléphone', 'rules' => 'required|phone'],
        'nb_personnes'    => ['label' => 'nombre de personnes', 'rules' => 'required|int|min_value:1'],
        'date_reservation'=> ['label' => 'date', 'rules' => 'required|date:Y-m-d'],
        'heure_reservation'=> ['label' => 'heure', 'rules' => 'required|time'],
        'message'         => ['label' => 'message', 'rules' => 'max:500'],
    ]);

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('reservation.php');
    }

    $d = $v->data();
    if (strtotime($d['date_reservation']) < strtotime('today')) {
        keep_old($_POST);
        Flash::error('La date de réservation ne peut pas être dans le passé.');
        redirect('reservation.php');
    }

    $ok = db()->run(
        'INSERT INTO reservations (nom, telephone, nb_personnes, date_reservation, heure_reservation, message, statut)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [
            $d['nom'], $d['telephone'], (int) $d['nb_personnes'],
            $d['date_reservation'], $d['heure_reservation'],
            trim((string) ($d['message'] ?? '')), 'en_attente',
        ]
    );

    if ($ok) {
        Flash::success('Votre demande de réservation a bien été enregistrée. KOO-KIN vous confirme par téléphone.');
        redirect('reservation.php?merci=1');
    }
    Flash::error('Une erreur est survenue. Veuillez réessayer.');
    redirect('reservation.php');
}

$page = [
    'titre' => 'Réserver une table — KOO-KIN',
    'actif' => 'reservation',
    'description' => 'Réservez votre table au restaurant KOO-KIN à Kintambo, Kinshasa. Confirmation rapide par téléphone.',
    'fil' => 'Réserver',
];

require BASE_PATH . 'includes/front/header.php';

if (isset($_GET['merci'])): ?>
<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Réserver</p>
        <h1>Réservation enregistrée</h1>
    </div>
</section>
<section class="section">
    <div class="conteneur">
        <div class="carte-form animer" style="max-width:620px;margin-inline:auto;text-align:center">
            <?php foreach (flash_success() as $m): ?><div class="alerte alerte--succes"><?= e($m) ?></div><?php endforeach; ?>
            <span style="font-size:2.8rem;color:var(--succes);display:inline-block"><?= icone('verifier') ?></span>
            <h2>Merci pour votre demande !</h2>
            <p class="texte-doux">Notre équipe vous appellera au numéro communiqué pour confirmer votre table.</p>
            <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1.2rem">
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Découvrir le menu</a>
                <a href="<?= e(url('contact.php')) ?>" class="btn btn--contour">Nous contacter</a>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Réserver</p>
        <h1>Réserver une table</h1>
        <p>Indiquez vos préférences, nous vous confirmons votre table par téléphone.</p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <div class="grille grille--2" style="align-items:start">
            <div class="carte-form animer">
                <h2 style="font-size:1.4rem">Formulaire de réservation</h2>
                <div class="separateur"></div>
                <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
                <form method="post" action="<?= e(url('reservation.php')) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= e(old('nom', (string) ($cl['nom'] ?? ''))) ?>" required maxlength="150" autocomplete="name">
                        </div>
                        <div class="champ">
                            <label for="telephone">Téléphone *</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= e(old('telephone', (string) ($cl['telephone'] ?? ''))) ?>" required placeholder="+243 ..." autocomplete="tel">
                        </div>
                    </div>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="nb_personnes">Nombre de personnes *</label>
                            <input type="number" id="nb_personnes" name="nb_personnes" value="<?= e(old('nb_personnes', 2)) ?>" min="1" max="50" required>
                        </div>
                        <div class="champ">
                            <label for="date_reservation">Date *</label>
                            <input type="date" id="date_reservation" name="date_reservation" value="<?= e(old('date_reservation', date('Y-m-d', strtotime('+1 day')))) ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="heure_reservation">Heure *</label>
                            <input type="time" id="heure_reservation" name="heure_reservation" value="<?= e(old('heure_reservation', '19:30')) ?>" required>
                        </div>
                        <div class="champ">
                            <label for="message">Message (optionnel)</label>
                            <input type="text" id="message" name="message" value="<?= e(old('message')) ?>" maxlength="500" placeholder="Table près de la fenêtre, occasion...">
                        </div>
                    </div>
                    <button type="submit" name="reservation" value="1" class="btn btn--or btn--bloc">Envoyer ma demande</button>
                </form>
            </div>

            <div class="animer">
                <aside class="carte-form">
                    <h2 style="font-size:1.3rem">Nos horaires</h2>
                    <div class="separateur"></div>
                    <table class="horaires-table">
                        <?php $auj = date('N');
                        foreach (db()->all('SELECT * FROM horaires ORDER BY id') as $h): ?>
                            <tr class="<?= (int) $h['id'] === $auj ? 'aujourdhui' : '' ?>">
                                <td><?= e($h['jour']) ?></td>
                                <td>
                                    <?php if ((int) $h['ferme'] === 1): ?>
                                        Fermé
                                    <?php else: ?>
                                        <?= e(substr($h['ouverture'], 0, 5)) ?> — <?= e(substr($h['fermeture'], 0, 5)) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <div class="menu-note">
                        <?= e((string) param('repere', 'Kintambo, Kinshasa — à proximité du rond-point. ')) ?>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
<?php endif;

require BASE_PATH . 'includes/front/footer.php';