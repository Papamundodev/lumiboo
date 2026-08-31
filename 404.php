<?php

use Theme_base\Base;

get_header();
$theme_template_name = basename(__FILE__, ".php");
?>

<main id="main-<?= $theme_template_name ?>" class="main" role="main">
    <section aria-labelledby="section-404-title" class="section-404">
        <div class="container">
            <h1 id="section-404-title"><?= __('404', 'theme_base'); ?></h1>
            <p><?= __('Page non trouvée', 'theme_base'); ?></p>

            <div class="button-wrapper">
                <div class="button-container button-background-primary">
                    <a href="<?= esc_url(home_url('/')); ?>" class="btn">
                        <?= __('Retour à la page d\'accueil', 'theme_base'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
