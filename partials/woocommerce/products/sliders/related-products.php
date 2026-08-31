<?php

use Theme_base\Base;
use Theme_base\Woocommerce;

$related_products = $args['related_products'] ?? [];
$title = Woocommerce::relatedProductsTitle(is_array($args) ? $args : []);

if (! is_array($related_products) || count($related_products) === 0) {
    return;
}
?>

<div class="container-right">
    <section aria-labelledby="section-related-products-title" class="section-related-products slider" data-slider-related-products data-count="<?= count($related_products); ?>">
        <h2 id="section-related-products-title" class="section-title"><?= $title; ?></h2>
        <div class="slider-wrapper products-wrapper" data-slider-wrapper-related-products>
            <?php foreach ($related_products as $product) : ?>
                <?php $p = wc_get_product($product->ID); ?>
                <?php if (! ($p instanceof \WC_Product)) {
                    continue;
                } ?>
                <article class="product-preview reveal-item slide related">
                    <?php get_template_part('partials/woocommerce/products/product-preview', null, ['product' => $p, 'post' => $product, 'related' => true]); ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($related_products) > 2) : ?>
            <div class="wrapper-button-container">
                <div class="slide-button-container">
                    <button class="slide-button-prev slide-button btn" data-slider-prev-related-products aria-label="<?= esc_attr__('Produits similaires précédent', 'theme_base'); ?>" title="<?= esc_attr__('Produits similaires précédent', 'theme_base'); ?>">
                        <?= Base::inline_svg('arrow-left.svg'); ?>
                    </button>
                </div>
                <div class="slide-button-container">
                    <button class="slide-button-next slide-button btn" data-slider-next-related-products aria-label="<?= esc_attr__('Produits similaires suivant', 'theme_base'); ?>" title="<?= esc_attr__('Produits similaires suivant', 'theme_base'); ?>">
                        <?= Base::inline_svg('arrow-right.svg'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
