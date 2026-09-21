<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

use KooKin\Core\Auth;
use KooKin\Core\Flash;

Auth::requireLogin();

$origines = ['contact' => 'Formulaire de contact', 'chatbox' => 'Chatbox'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = admin_entier($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        db()->run('DELETE FROM messages WHERE id = ?', [$id]);
        Flash::success('Le message a été supprimé.');
        admin_redirect('messages.php');
    }

    if ($action === 'toggle' && $id > 0) {
        db()->run('UPDATE messages SET lu = 1 - lu WHERE id = ?', [$id]);
        admin_redirect('messages.php?id=' . $id);
    }
}

$idDetail = admin_entier($_GET['id'] ?? 0);
$message = null;
if ($idDetail > 0) {
    $message = db()->one('SELECT * FROM messages WHERE id = ?', [$idDetail]);
    if ($message && (int) $message['lu'] === 0) {
        db()->run('UPDATE messages SET lu = 1 WHERE id = ?', [$idDetail]);
        $message['lu'] = 1;
    }
}

$filtre = (string) ($_GET['filtre'] ?? '');
$sql = 'SELECT * FROM messages';
$params = [];
if ($filtre === 'non_lus') {
    $sql .= ' WHERE lu = 0';
} elseif (isset($origines[$filtre])) {
    $sql .= ' WHERE origine = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY lu ASC, id DESC';

$messages = $message ? [] : db()->all($sql, $params);
$nbNonLus = (int) db()->value('SELECT COUNT(*) FROM messages WHERE lu = 0');

$admin_titre = $message ? 'Message' : 'Messages';
$admin_actif = 'messages';

require __DIR__ . '/includes/header.php';

if ($message): ?>
    <div class="adm-detail">
        <div class="adm-carte">
            <div class="adm-carte__entete">
                <h2><?= e((string) ($message['sujet'] ?: 'Message')) ?></h2>
                <div class="adm-actions">
                    <a href="<?= e(admin_url('messages.php')) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Retour à la liste</a>
                </div>
            </div>
            <div class="adm-carte__corps">
                <p style="white-space:pre-line;margin-top:0"><?= e((string) $message['message']) ?></p>
            </div>
        </div>

        <div class="adm-carte">
            <div class="adm-carte__entete"><h2>Expéditeur</h2></div>
            <div class="adm-carte__corps">
                <ul class="adm-info">
                    <li><span>Nom</span><span><?= e((string) ($message['nom'] ?: 'Anonyme')) ?></span></li>
                    <li><span>Téléphone</span><span><?= $message['telephone'] ? '<a href="tel:' . e($message['telephone']) . '">' . e($message['telephone']) . '</a>' : '—' ?></span></li>
                    <li><span>Origine</span><span><?= e($origines[$message['origine']] ?? $message['origine']) ?></span></li>
                    <li><span>Reçu le</span><span><?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></span></li>
                </ul>
                <div class="adm-actions" style="margin-top:1rem">
                    <?php if ($message['telephone']): $tel = preg_replace('/[^0-9]/', '', (string) $message['telephone']); ?>
                        <a href="https://wa.me/<?= e($tel) ?>" target="_blank" rel="noopener" class="adm-btn adm-btn--contour adm-btn--sm"><?= icone('whatsapp') ?> WhatsApp</a>
                        <a href="tel:<?= e($message['telephone']) ?>" class="adm-btn adm-btn--contour adm-btn--sm"><?= icone('telephone') ?> Appeler</a>
                    <?php endif; ?>
                    <form method="post" action="<?= e(admin_url('messages.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                        <button type="submit" class="adm-btn adm-btn--contour adm-btn--sm">Marquer comme non lu</button>
                    </form>
                    <form method="post" action="<?= e(admin_url('messages.php')) ?>" data-confirm="Supprimer ce message ?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                        <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="adm-carte">
        <div class="adm-carte__entete">
            <h2>Messages reçus</h2>
            <div class="adm-actions">
                <a href="<?= e(admin_url('messages.php')) ?>" class="adm-filtre <?= $filtre === '' ? 'actif' : '' ?>">Tous</a>
                <a href="<?= e(admin_url('messages.php?filtre=non_lus')) ?>" class="adm-filtre <?= $filtre === 'non_lus' ? 'actif' : '' ?>">Non lus (<?= $nbNonLus ?>)</a>
                <?php foreach ($origines as $cle => $label): ?>
                    <a href="<?= e(admin_url('messages.php?filtre=' . $cle)) ?>" class="adm-filtre <?= $filtre === $cle ? 'actif' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="adm-carte__corps adm-carte__corps--serre">
            <?php if ($messages): ?>
                <div class="adm-table__wrap">
                    <table class="adm-table">
                        <thead><tr><th></th><th>Nom</th><th>Sujet</th><th>Origine</th><th>Reçu le</th><th class="num">Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td><?php if ((int) $m['lu'] === 0): ?><span class="adm-badge adm-badge--or">Nouveau</span><?php endif; ?></td>
                                    <td>
                                        <strong><?= e((string) ($m['nom'] ?: 'Anonyme')) ?></strong>
                                        <?php if ($m['telephone']): ?><br><small style="color:var(--brun-doux)"><?= e($m['telephone']) ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= e(admin_url('messages.php?id=' . (int) $m['id'])) ?>" style="color:var(--or-fonce);font-weight:500<?= (int) $m['lu'] === 0 ? '' : ';font-weight:400' ?>"><?= e((string) ($m['sujet'] ?: 'Sans sujet')) ?></a>
                                        <br><small style="color:var(--brun-doux)"><?= e(mb_strimwidth((string) $m['message'], 0, 70, '…')) ?></small>
                                    </td>
                                    <td><?= e($origines[$m['origine']] ?? $m['origine']) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime((string) $m['created_at']))) ?></td>
                                    <td>
                                        <div class="adm-table__actions">
                                            <a href="<?= e(admin_url('messages.php?id=' . (int) $m['id'])) ?>" class="adm-btn adm-btn--contour adm-btn--sm">Voir</a>
                                            <form method="post" action="<?= e(admin_url('messages.php')) ?>" data-confirm="Supprimer ce message ?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                                <button type="submit" class="adm-btn adm-btn--danger adm-btn--sm">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="adm-vide"><strong>Aucun message</strong>Les messages du formulaire et de la chatbox s'afficheront ici.</div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php';