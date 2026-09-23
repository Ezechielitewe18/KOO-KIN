<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\ClientAuth;

ClientAuth::requireLogin();

$client = ClientAuth::client();

$commandes = db()->all(
    'SELECT * FROM commandes WHERE client_id = ? ORDER BY id DESC LIMIT 20',
    [(int) $client['id']]
);
$reservations = db()->all(
    'SELECT * FROM reservations WHERE telephone = ? ORDER BY date_reservation DESC, heure_reservation DESC LIMIT 10',
    [(string) $client['telephone']]
);
$demandes = db()->all(
    'SELECT * FROM demandes_traiteur WHERE telephone = ? ORDER BY id DESC LIMIT 10',
    [(string) $client['telephone']]
);

$statutsCommande = [
    'nouvelle'       => 'Nouvelle',
    'confirmee'      => 'Confirmée',
    'en_preparation' => 'En préparation',
    'prete'          => 'Prête',
    'en_livraison'   => 'En livraison',
    'livree'         => 'Livrée',
    'annulee'        => 'Annulée',
];
$statutsReservation = [
    'en_attente' => 'En attente',
    'confirmee'  => 'Confirmée',
    'annulee'    => 'Annulée',
    'terminee'   => 'Terminée',
];
$statutsTraiteur = [
    'nouvelle'  => 'Nouvelle',
    'contacte'  => 'Contactée',
    'traitee'   => 'Traitée',
    'annulee'   => 'Annulée',
];

$page = [
    'titre' => 'Mon compte — KOO-KIN',
    'actif' => 'mon-compte',
    'description' => 'Votre espace client KOO-KIN : commandes, réservations et demandes traiteur.',
    'fil' => 'Mon compte',
];

require BASE_PATH . 'includes/front/header.php';
?>

<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> Mon compte</p>
        <h1>Bonjour <?= e((string) ($client['nom'] ?? '')) ?></h1>
        <p>Téléphone : <?= e((string) $client['telephone']) ?> — retrouvez ici vos commandes et réservations.</p>
        <p style="margin-top:.6rem">
            <a href="<?= e(url('menu.php')) ?>" class="btn btn--or">Commander maintenant</a>
            <a href="<?= e(url('deconnexion.php')) ?>" class="btn btn--contour">Se déconnecter</a>
        </p>
    </div>
</section>

<section class="section">
    <div class="conteneur">
        <?php foreach (flash_success() as $m): ?><div class="alerte alerte--succes"><?= e($m) ?></div><?php endforeach; ?>

        <h2 style="font-size:1.5rem">Mes commandes</h2>
        <div class="separateur"></div>

        <?php if ($commandes === []): ?>
            <p class="texte-doux">Aucune commande pour le moment. Votre première commande vous attend !</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="tableau">
                    <thead>
                        <tr><th>Code</th><th>Date</th><th>Type</th><th class="num">Total</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $c): ?>
                            <tr>
                                <td><strong><?= e($c['code']) ?></strong></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $c['created_at']))) ?></td>
                                <td><?= e(strtoupper(str_replace('_', ' ', $c['type']))) ?></td>
                                <td class="num"><?= e(format_prix((int) $c['total'])) ?></td>
                                <td><span class="badge badge--info"><?= e($statutsCommande[$c['statut']] ?? $c['statut']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 style="font-size:1.5rem;margin-top:2rem">Mes réservations</h2>
        <div class="separateur"></div>

        <?php if ($reservations === []): ?>
            <p class="texte-doux">Aucune réservation pour le moment.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="tableau">
                    <thead>
                        <tr><th>Date</th><th>Heure</th><th>Personnes</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td><?= e(date('d/m/Y', strtotime((string) $r['date_reservation']))) ?></td>
                                <td><?= e(substr((string) $r['heure_reservation'], 0, 5)) ?></td>
                                <td><?= (int) $r['nb_personnes'] ?></td>
                                <td><span class="badge badge--info"><?= e($statutsReservation[$r['statut']] ?? $r['statut']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 style="font-size:1.5rem;margin-top:2rem">Mes demandes traiteur</h2>
        <div class="separateur"></div>

        <?php if ($demandes === []): ?>
            <p class="texte-doux">Aucune demande traiteur pour le moment.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table class="tableau">
                    <thead>
                        <tr><th>Événement</th><th>Date</th><th>Personnes</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $dt): ?>
                            <tr>
                                <td><?= e((string) ($dt['type_evenement'] ?? '—')) ?></td>
                                <td><?= $dt['date_evenement'] !== null && $dt['date_evenement'] !== '' ? e(date('d/m/Y', strtotime((string) $dt['date_evenement']))) : '—' ?></td>
                                <td><?= (int) $dt['nb_personnes'] ?></td>
                                <td><span class="badge badge--info"><?= e($statutsTraiteur[$dt['statut']] ?? $dt['statut']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
require BASE_PATH . 'includes/front/footer.php';