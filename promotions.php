<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$page = [
    'titre' => 'Promotions',
    'actif' => 'promotions',
    'description' => 'L\'offre du moment chez KOO-KIN.',
    'fil' => 'Promotions',
];

require BASE_PATH . 'includes/front/header.php';
require BASE_PATH . 'includes/front/entete-page.php';
?>

<section class="section">
    <div class="conteneur">
        <div class="titre-section centre animer">
            <span class="eyebrow">KOO-KIN</span>
            <h2>Cette section arrive très bientôt</h2>
            <div class="separateur centre"></div>
            <p class="texte-doux">Le contenu complet de cette page sera finalisé dans la prochaine étape de développement.</p>
        </div>
    </div>
</section>

<?php require BASE_PATH . 'includes/front/footer.php';