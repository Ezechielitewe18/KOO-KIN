<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;

Auth::requireLogin();

$stmt = db();

$stats = [
    'commandes'         => (int) $stmt->value('SELECT COUNT(*) FROM commandes'),
    'commandes_nouv'    => (int) $stmt->value("SELECT COUNT(*) FROM commandes WHERE statut = 'nouvelle'"),
    'ca'                => (float) $stmt->value('SELECT COALESCE(SUM(total), 0) FROM commandes'),
    'reservations'      => (int) $stmt->value('SELECT COUNT(*) FROM reservations'),
    'reservations_att'  => (int) $stmt->value("SELECT COUNT(*) FROM reservations WHERE statut = 'en_attente'"),
    'messages_non_lus'  => (int) $stmt->value('SELECT COUNT(*) FROM messages WHERE lu = 0'),
    'plats'             => (int) $stmt->value('SELECT COUNT(*) FROM plats'),
    'plats_dispo'       => (int) $stmt->value('SELECT COUNT(*) FROM plats WHERE disponible = 1'),
    'traiteur_nouv'     => (int) $stmt->value("SELECT COUNT(*) FROM demandes_traiteur WHERE statut = 'nouvelle'"),
    'galerie'           => (int) $stmt->value('SELECT COUNT(*) FROM galerie'),
];

$dernieresCommandes = $stmt->all('SELECT id, code, client_nom, total, statut, type, created_at FROM commandes ORDER BY id DESC LIMIT 6');
$dernieresReservations = $stmt->all('SELECT id, nom, nb_personnes, date_reservation, heure_reservation, statut FROM reservations ORDER BY id DESC LIMIT 6');

$statutsCmd = [
    'nouvelle'       => ['Nouvelle', 'or'],
    'confirmee'      => ['Confirmée', 'info'],
    'en_preparation' => ['En préparation', 'info'],
    'prete'          => ['Prête', 'info'],
    'en_livraison'   => ['En livraison', 'info'],
    'livree'         => ['Livrée', 'succes'],
    'annulee'        => ['Annulée', 'erreur'],
];
$statutsResa = [
    'en_attente' => ['En attente', 'or'],
    'confirmee'  => ['Confirmée', 'succes'],
    'annulee'    => ['Annulée', 'erreur'],
    'terminee'   => ['Terminée', 'neutre'],
];

$admin_titre = 'Tableau de bord';
$admin_actif = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="adm-stats">
    <div class="adm-stat adm-stat--or">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['commandes']) ?></div>
        <div class="adm-stat__label">Commandes<?= $stats['commandes_nouv'] > 0 ? ' · ' . $stats['commandes_nouv'] . ' nouvelle(s)' : '' ?></div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= e(format_prix($stats['ca'])) ?></div>
        <div class="adm-stat__label">Chiffre d'affaires</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['reservations_att']) ?></div>
        <div class="adm-stat__label">Réservations en attente</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['messages_non_lus']) ?></div>
        <div class="adm-stat__label">Messages non lus</div>
    </div>
</div>

<div class="adm-stats" style="grid-template-columns:repeat(4,1fr)">
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['plats']) ?></div>
        <div class="adm-stat__label">Plats · <?= admin_nombre($stats['plats_dispo']) ?> disponibles</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['traiteur_nouv']) ?></div>
        <div class="adm-stat__label">Demandes traiteur</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['galerie']) ?></div>
        <div class="adm-stat__label">Photos en galerie</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat__valeur"><?= admin_nombre($stats['reservations']) ?></div>
        <div class="adm-stat__label">Réservations au total</div>
    </div>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Dernières commandes</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('commandes.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Tout voir</a>
        </div>
    </div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($dernieresCommandes): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Code</th><th>Client</th><th>Type</th><th>Date</th><th class="num">Total</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresCommandes as $c): $s = $statutsCmd[$c['statut']] ?? [$c['statut'], 'neutre']; ?>
                            <tr>
                                <td><a href="<?= e(admin_url('commandes.php?id=' . (int) $c['id'])) ?>" style="color:var(--or-fonce);font-weight:500"><?= e($c['code']) ?></a></td>
                                <td><?= e($c['client_nom']) ?></td>
                                <td><?= e(str_replace('_', ' ', (string) $c['type'])) ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime((string) $c['created_at']))) ?></td>
                                <td class="num"><?= e(format_prix((int) $c['total'])) ?></td>
                                <td><span class="adm-badge adm-badge--<?= e($s[1]) ?>"><?= e($s[0]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="adm-vide"><strong>Aucune commande</strong>Les nouvelles commandes apparaîtront ici.</div>
        <?php endif; ?>
    </div>
</div>

<div class="adm-carte">
    <div class="adm-carte__entete">
        <h2>Dernières réservations</h2>
        <div class="adm-actions">
            <a href="<?= e(admin_url('reservations.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Tout voir</a>
        </div>
    </div>
    <div class="adm-carte__corps adm-carte__corps--serre">
        <?php if ($dernieresReservations): ?>
            <div class="adm-table__wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>Nom</th><th>Personnes</th><th>Date</th><th>Heure</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresReservations as $r): $s = $statutsResa[$r['statut']] ?? [$r['statut'], 'neutre']; ?>
                            <tr>
                                <td><?= e($r['nom']) ?></td>
                                <td><?= (int) $r['nb_personnes'] ?></td>
                                <td><?= e(date('d/m/Y', strtotime((string) $r['date_reservation']))) ?></td>
                                <td><?= e(substr((string) $r['heure_reservation'], 0, 5)) ?></td>
                                <td><span class="adm-badge adm-badge--<?= e($s[1]) ?>"><?= e($s[0]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="adm-vide"><strong>Aucune réservation</strong>Les demandes de table apparaîtront ici.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php';