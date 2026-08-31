<?php

use Theme_base\Base;

$products = $args['products'] ?? [];
$title = $args['title'] ?? __('Nos produits du moment', 'theme_base');

if (! is_array($products) || count($products) === 0) {
    return;
}
?>

<section aria-labelledby="section-products-title" id="section-products-slider" class="section-products slider" data-slider-products data-count="<?= count($products); ?>">
    <h2 id="section-products-title" class="section-title"><?= $title; ?></h2>
    <div class="slider-wrapper products-wrapper container" data-slider-wrapper-products>
        <?php foreach ($products as $post) : ?>
            <?php $p = wc_get_product($post->ID); ?>
            <?php if (! ($p instanceof \WC_Product)) {
                continue;
            } ?>
            <article class="product-preview reveal-item slide">
                <?php get_template_part('partials/woocommerce/products/product-preview', null, ['product' => $p, 'post' => $post]); ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (count($products) > 1) : ?>
        <div class="wrapper-button-container">
            <div class="slide-button-container">
                <button class="slide-button-prev slide-button btn" data-slider-prev-products aria-label="<?= esc_attr__('Produits précédent', 'theme_base'); ?>" title="<?= esc_attr__('Produits précédent', 'theme_base'); ?>">
                    <?= Base::inline_svg('arrow-left.svg'); ?>
                </button>
            </div>
            <div class="slide-button-container">
                <button class="slide-button-next slide-button btn" data-slider-next-products aria-label="<?= esc_attr__('Produits suivant', 'theme_base'); ?>" title="<?= esc_attr__('Produits suivant', 'theme_base'); ?>">
                    <?= Base::inline_svg('arrow-right.svg'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</section>
