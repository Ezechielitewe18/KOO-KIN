<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Validation;
use KooKin\Core\Flash;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact'])) {
    csrf_check();

    $v = (new Validation())->make($_POST, [
        'nom'       => ['label' => 'nom', 'rules' => 'required|max:150'],
        'telephone' => ['label' => 'téléphone', 'rules' => 'max:30'],
        'sujet'     => ['label' => 'sujet', 'rules' => 'max:150'],
        'message'   => ['label' => 'message', 'rules' => 'required|max:2000'],
        'email'     => ['label' => 'email', 'rules' => 'email|max:190'],
    ]);

    if ($v->fails()) {
        keep_old($_POST);
        Flash::error($v->first());
        redirect('contact.php');
    }

    $d = $v->data();
    $ok = db()->run(
        'INSERT INTO messages (nom, telephone, sujet, message, origine) VALUES (?, ?, ?, ?, ?)',
        [
            $d['nom'], trim((string) ($d['telephone'] ?? '')),
            trim((string) ($d['sujet'] ?? '')), $d['message'], 'contact',
        ]
    );

    if ($ok) {
        Flash::success('Votre message a bien été envoyé. Nous vous répondons au plus vite.');
        redirect('contact.php?merci=1');
    }
    Flash::error('Une erreur est survenue. Veuillez réessayer.');
    redirect('contact.php');
}

$telephone = (string) param('telephone', '+243 8xx xxx xxx');
$whatsapp  = (string) param('whatsapp', '2438xxxxxxx');
$email     = (string) param('email', 'contact@koo-kin.cd');

$page = [
    'titre' => 'Contact — KOO-KIN',
    'actif' => 'contact',
    'description' => 'Contactez KOO-KIN à Kintambo, Kinshasa : téléphone, WhatsApp, email, adresse et horaires.',
    'fil' => 'Contact',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <?php if (isset($_GET['merci'])): ?>
            <div class="alerte alerte--succes animer"><?php foreach (flash_success() as $m): ?><?= e($m) ?><?php endforeach; ?></div>
        <?php endif; ?>
        <?php foreach (flash_error() as $m): ?><div class="alerte alerte--erreur"><?= e($m) ?></div><?php endforeach; ?>

        <div class="grille grille--2" style="align-items:start">
            <div class="carte-form animer">
                <h2 style="font-size:1.4rem">Écrivez-nous</h2>
                <div class="separateur"></div>
                <form method="post" action="<?= e(url('contact.php')) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= e(old('nom')) ?>" required maxlength="150" autocomplete="name">
                        </div>
                        <div class="champ">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= e(old('telephone')) ?>" maxlength="30" placeholder="+243 ..." autocomplete="tel">
                        </div>
                    </div>
                    <div class="form__ligne">
                        <div class="champ">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" maxlength="190" placeholder="vous@exemple.com" autocomplete="email">
                        </div>
                        <div class="champ">
                            <label for="sujet">Sujet</label>
                            <input type="text" id="sujet" name="sujet" value="<?= e(old('sujet')) ?>" maxlength="150" placeholder="Réservation, traiteur, suggestion...">
                        </div>
                    </div>
                    <div class="champ champ--large">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" maxlength="2000" style="min-height:140px" required placeholder="Écrivez votre message..."><?= e(old('message')) ?></textarea>
                    </div>
                    <button type="submit" name="contact" value="1" class="btn btn--or btn--bloc">Envoyer le message</button>
                </form>
            </div>

            <div class="animer">
                <div class="carte-form">
                    <h2 style="font-size:1.3rem">Nos coordonnées</h2>
                    <div class="separateur"></div>
                    <ul class="info-contact">
                        <li>
                            <span class="ico"><?= icone('telephone') ?></span>
                            <div><strong>Adresse</strong><br><span class="texte-doux"><?= e((string) param('adresse', '')) ?></span></div>
                        </li>
                        <li>
                            <span class="ico"><?= icone('rechercher') ?></span>
                            <div><strong>Repère</strong><br><span class="texte-doux"><?= e((string) param('repere', '')) ?></span></div>
                        </li>
                        <li>
                            <span class="ico"><?= icone('telephone') ?></span>
                            <div><strong>Téléphone</strong><br><a href="tel:<?= e(preg_replace('/\s+/', '', $telephone)) ?>"><?= e($telephone) ?></a></div>
                        </li>
                        <li>
                            <span class="ico"><?= icone('whatsapp') ?></span>
                            <div><strong>WhatsApp</strong><br><a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener">Discuter avec nous</a></div>
                        </li>
                        <li>
                            <span class="ico"><?= icone('chat') ?></span>
                            <div><strong>Email</strong><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></div>
                        </li>
                    </ul>
                    <div class="menu-note" style="margin-top:1.4rem">
                        Besoin d'une réponse immédiate ? Utilisez la chatbox en bas de l'écran ou notre WhatsApp.
                    </div>
                </div>

                <div class="carte-form" style="margin-top:1.6rem">
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
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--creme2" style="padding-top:0">
    <div class="conteneur">
        <div class="carte-map animer">
            <iframe
                title="Carte KOO-KIN"
                loading="lazy"
                src="https://www.openstreetmap.org/export/embed.html?bbox=15.232372%2C-4.344208%2C15.298878%2C-4.318201&layer=mapnik&marker=-4.3312%2C15.2656"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';