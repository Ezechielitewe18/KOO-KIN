<?php

declare(strict_types=1);

$titre = $page['titre'] ?? 'KOO-KIN';
$description = $page['description'] ?? '';
$fil = $page['fil'] ?? 'Accueil';
?>
<section class="entete-page">
    <div class="conteneur animer visible">
        <p class="fil"><a href="<?= e(url('/')) ?>">Accueil</a> <span>/</span> <?= e($fil) ?></p>
        <h1><?= e($titre) ?></h1>
        <?php if ($description !== ''): ?>
            <p><?= e($description) ?></p>
        <?php endif; ?>
    </div>
</section>