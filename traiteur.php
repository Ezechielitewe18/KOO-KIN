<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Validation;
use KooKin\Core\Flash;
use KooKin\Core\ClientAuth;

ClientAuth::requireLogin();
$cl = ClientAuth::client();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['traiteur'])) {
    csrf_check();

    $v = (new Validation())->make($_POST, [
        'nom'            => ['label' => 'nom', 'rules' => 'required|max:150'],
        'telephone'      => ['label' => 'téléphone', 'rules' => 'required|phone'],
        'type_evenement' => ['label' => 'type d\'événement', 'rules' => 'required|max:100'],
        'date_evenement' => ['label' => 'date', 'rules' => 'required|date:Y-m-d'],
        'nb_personnes'   => ['label' => 'nombre de personnes', 'rules' => 'int|min_value:1'],
        'budget'         => ['label' => 'budget', 'rules' => 'max:50'],
        'message'        => ['label' => 'message', 'rules' => 'max:2000'],
    ]);

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('traiteur.php');
    }

    $d = $v->data();
    if (strtotime($d['date_evenement']) < strtotime('today')) {
        keep_old($_POST);
        Flash::error('La date de l\'événement ne peut pas être dans le passé.');
        redirect('traiteur.php');
    }

    $ok = db()->run(
        'INSERT INTO demandes_traiteur (nom, telephone, type_evenement, date_evenement, nb_personnes, budget, message, statut)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $d['nom'], $d['telephone'], $d['type_evenement'], $d['date_evenement'],
            $d['nb_personnes'] !== '' ? (int) $d['nb_personnes'] : null,
            trim((string) ($d['budget'] ?? '')), trim((string) ($d['message'] ?? '')), 'nouvelle',
        ]
    );

    if ($ok) {
        Flash::success('Votre demande traiteur a bien été envoyée. Notre équipe vous contacte rapidement.');
        redirect('traiteur.php?merci=1');
    }
    Flash::error('Une erreur est survenue. Veuillez réessayer.');
    redirect('traiteur.php');
}

$page = [
    'titre' => 'Service traiteur — KOO-KIN',
    'actif' => 'traiteur',
    'description' => 'Service traiteur KOO-KIN pour mariages, anniversaires, cérémonies et événements d\'entreprise à Kinshasa.',
    'fil' => 'Traiteur',
];

require BASE_PATH . 'includes/front/header.php';

if (isset($_GET['merci'])): ?>
<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Traiteur</p>
        <h1>Demande envoyée</h1>
    </div>
</section>
<section class="section">
    <div class="conteneur">
        <div class="carte-form animer" style="max-width:640px;margin-inline:auto;text-align:center">
            <?php foreach (flash_success() as $m): ?><div class="alerte alerte--succes"><?= e($m) ?></div><?php endforeach; ?>
            <span style="font-size:2.8rem;color:var(--succes);display:inline-block"><?= icone('verifier') ?></span>
            <h2>Merci pour votre confiance</h2>
            <p class="texte-doux">Nous étudions votre demande et revenons vers vous pour construire ensemble un menu adapté à votre événement.</p>
            <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1.2rem">
                <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Voir le menu</a>
                <a href="<?= e(url('contact.php')) ?>" class="btn btn--contour">Nous contacter</a>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Traiteur</p>
        <h1>Service traiteur</h1>
        <p><?= e((string) param('traiteur_texte', 'KOO-KIN vous accompagne pour vos événements avec un service traiteur adapté à vos besoins.')) ?></p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <div class="grille grille--3" style="margin-bottom:2.6rem">
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('traiteur') ?></span>
                <h3>Événements familiaux</h3>
                <p class="texte-doux">Mariages, anniversaires, baptêmes, deuils et fêtes de famille.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('menu') ?></span>
                <h3>Menus sur mesure</h3>
                <p class="texte-doux">Nous composons un menu adapté à votre nombre d'invités et à votre budget.</p>
            </div>
            <div class="carte-form animer">
                <span class="ico-info"><?= icone('livraison') ?></span>
                <h3>Sur votre lieu</h3>
                <p class="texte-doux">Livraison et service sur le lieu de votre événement, à Kinshasa.</p>
            </div>
        </div>

        <div class="grille grille--2" style="align-items:start">
            <div class="carte-form animer">
                <h2 style="font-size:1.4rem">Demander un devis</h2>
                <div class="separateur"></div>
                <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>
                <form method="post" action="<?= e(url('traiteur.php')) ?>" class="form">
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
                            <label for="type_evenement">Type d'événement *</label>
                            <input type="text" id="type_evenement" name="type_evenement" value="<?= e(old('type_evenement')) ?>" required maxlength="100" placeholder="Mariage, anniversaire, réunion...">
                        </div>
                        <div class="champ">
                            <label for="date_evenement">Date *</label>
                            <input type="date" id="date_evenement" name="date_evenement" value="<?= e(old('date_evenement', date('Y-m-d', strtotime('+7 day')))) ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="nb_personnes">Nombre de personnes</label>
                            <input type="number" id="nb_personnes" name="nb_personnes" value="<?= e(old('nb_personnes', 50)) ?>" min="1" max="5000">
                        </div>
                        <div class="champ">
                            <label for="budget">Budget estimé (optionnel)</label>
                            <input type="text" id="budget" name="budget" value="<?= e(old('budget')) ?>" maxlength="50" placeholder="Ex. 500 000 CDF">
                        </div>
                    </div>
                    <div class="champ champ--large">
                        <label for="message">Détails de votre événement</label>
                        <textarea id="message" name="message" maxlength="2000" style="min-height:110px" placeholder="Lieu, heure, plats souhaités, contraintes..."><?= e(old('message')) ?></textarea>
                    </div>
                    <button type="submit" name="traiteur" value="1" class="btn btn--or btn--bloc">Envoyer ma demande</button>
                </form>
            </div>

            <div class="carte-form animer">
                <h2 style="font-size:1.3rem">Comment ça se passe</h2>
                <div class="separateur"></div>
                <ol class="liste-etapes">
                    <li><strong>Votre demande</strong><span>Remplissez le formulaire avec les informations clés de votre événement.</span></li>
                    <li><strong>Échange</strong><span>Nous vous appelons pour préciser le menu, les quantités et le budget.</span></li>
                    <li><strong>Devis</strong><span>Vous recevez une proposition claire et détaillée.</span></li>
                    <li><strong>Le jour J</strong><span>Nous préparons et livrons vos plats à l'heure convenue.</span></li>
                </ol>
                <div class="menu-note" style="margin-top:1.2rem">
                    Besoin d'une réponse rapide ? Appelez-nous au <?= e((string) param('telephone', '+243 994 266 536')) ?>.
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif;

require BASE_PATH . 'includes/front/footer.php';