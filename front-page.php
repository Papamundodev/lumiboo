<?php

use Theme_base\Base;

get_header();
$object = get_queried_object();
$theme_template_name = basename(__FILE__, ".php");
?>
<main id="main-<?= $theme_template_name ?>" class="main">
    <div class="container">

        <div class="design-system">
            <div class="design-system-buttons">
                <h2 class="section-title">Buttons</h2>
                <div class="design-system-buttons-container">
                    <div>
                        <div class="button-background-primary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-secondary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-tertiary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                    <div>
                        <div class="button-background-quaternary">
                            <a href="#" class="btn">Click me</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <?php
        $products_query = new WP_Query(array(
            'post_type' => 'product',
            'posts_per_page' => 10,
        )); ?>
        <?php if (is_array($products_query->posts) && count($products_query->posts) > 0): ?>
            <section aria-labelledby="section-products-title" id="section-products-front-page" class="section-products slider " data-slider-products data-count="<?= count($products_query->posts); ?>">
                <h2 id="section-products-title" class="section-title"><?= __('Carrousel de produits', 'theme_base'); ?></h2>
                <div class="slider-wrapper products-wrapper" data-slider-wrapper-products>
                    <?php if (is_array($products_query->posts) && count($products_query->posts) > 0): ?>
                        <?php $i = 0;
                        foreach ($products_query->posts as $post) : ?>
                            <?php $p = wc_get_product($post->ID); ?>
                            <?php if (! ($p instanceof \WC_Product)) {
                                continue;
                            } ?>
                            <article class="product-preview reveal-item slide">
                                <h3><?= $p->get_name(); ?></h3>
                                <p><?= $p->get_price_html(); ?></p>
                                <div class="content-wysiwyg">
                                    <?= wpautop($p->get_description()); ?>
                                </div>
                                <div class="button-background-primary">
                                    <a href="<?= $p->get_permalink(); ?>" class="btn"><?= __('Voir le produit', 'theme_base'); ?></a>
                                </div>
                            </article>
                        <?php $i++;
                        endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($products_query->posts) > 1) : ?>
                    <div class="wrapper-button-container">
                        <div class="slide-button-container">
                            <button class="slide-button-prev slide-button btn" data-slider-prev-products aria-label="<?php echo esc_attr__('Produits précédent', 'theme_base'); ?>" title="<?php echo esc_attr__('Produits précédent', 'theme_base'); ?>">
                                <?php echo file_get_contents(get_template_directory() . '/resources/images/arrow-left.svg'); ?>
                            </button>
                        </div>
                        <div class="slide-button-container">
                            <button class="slide-button-next slide-button btn" data-slider-next-products aria-label="<?php echo esc_attr__('Produits suivant', 'theme_base'); ?>" title="<?php echo esc_attr__('Produits suivant', 'theme_base'); ?>">
                                <?php echo file_get_contents(get_template_directory() . '/resources/images/arrow-right.svg'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php
        /**
         * La section "banner" (logos partenaires) est fournie par le mu-plugin `banner`.
         * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
         */
        do_action('theme_banner_render');
        ?>

        <?php $section_valeurs = get_field('section_valeurs', $object->ID); ?>
        <?php if ($section_valeurs && isset($section_valeurs['title']) && $section_valeurs['valeurs']) : ?>
            <section aria-labelledby="section-valeurs-title" class="section-valeurs">
                <div class="">
                    <?php
                    if ($section_valeurs['title']) : ?>
                        <h2 id="section-valeurs-title" class="section-title"><?= $section_valeurs['title']; ?></h2>
                    <?php endif; ?>
                    <?php if ($section_valeurs['valeurs']) : ?>
                        <div class="valeurs-wrapper">
                            <?php foreach ($section_valeurs['valeurs'] as $valeur) : ?>
                                <div class="valeur">
                                    <div class="img-container">
                                        <img src="<?= $valeur['image']['sizes']['formation_card'] ?? $valeur['image']['sizes']['medium'] ?? $valeur['image']['url']; ?>" alt="<?= $valeur['text']; ?>">
                                    </div>
                                    <div class="content">
                                        <div class="content-wysiwyg"><?= wpautop($valeur['text']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <?php
    /**
     * La section "chiffres clés" est fournie par le mu-plugin `odometer`.
     * Sans cet appel, elle serait rendue automatiquement dans wp_footer().
     */
    do_action('theme_odometer_render');
    ?>
</main>
<?php get_footer(); ?>