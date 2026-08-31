<?php
global $wp_query;
$theme_template_name = basename(__FILE__, ".php");
?>
<main id="main-<?= $theme_template_name ?>" role="main">

    <?php woocommerce_breadcrumb(); ?>

    <section id="section-products-shop" aria-labelledby="section-products-shop-title" class="section-products">
        <div class="container">
            <div class="section-content">
                <h1 id="section-products-shop-title" class="section-title"><?= get_the_title(wc_get_page_id('shop')); ?></h1>

                <div class="section-products-sort">
                    <?php woocommerce_catalog_ordering(); ?>
                </div>

                <?php if (is_array($wp_query->posts) && count($wp_query->posts) > 0) : ?>
                    <div class="products-grid">
                        <?php foreach ($wp_query->posts as $post) : setup_postdata($post); ?>
                            <?php $p = wc_get_product($post->ID); ?>
                            <?php if (! ($p instanceof \WC_Product)) {
                                continue;
                            } ?>
                            <div class="product-preview">
                                <?php get_template_part('partials/woocommerce/products/product-preview', null, ['product' => $p, 'post' => $post]); ?>
                            </div>
                        <?php endforeach;
                        wp_reset_postdata(); ?>
                    </div>
                <?php endif; ?>

                <?php get_template_part('pagination', null, ['id' => 'section-products-shop']); ?>
            </div>
        </div>
    </section>
</main>
