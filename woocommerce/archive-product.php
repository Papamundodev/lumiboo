<?php

use Theme_base\Base;

get_header('shop');
$object = get_queried_object();
?>

<?php if (is_shop()) : ?>
    <?php get_template_part('partials/woocommerce/shop', 'shop'); ?>
<?php endif; ?>

<?php woocommerce_output_all_notices(); ?>

<?php if ($object instanceof WP_Term) : ?>
    <?php $theme_sorting_cats = [
        'has_no_parent' => Base::cat_has_no_parent_category($object),
        'middle' => Base::cat_middle_category($object),
        'child' => Base::cat_child_category($object),
    ]; ?>

    <?php if ($theme_sorting_cats['has_no_parent']) : ?>
        <?php get_template_part('partials/woocommerce/parent-category-product', 'parent-category-product'); ?>
    <?php elseif ($theme_sorting_cats['middle'] || $theme_sorting_cats['child']) : ?>
        <?php get_template_part('partials/woocommerce/child-category-product', 'child-category-product'); ?>
    <?php endif; ?>
<?php endif; ?>

<div class="container">
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
</div>

<?php get_footer('shop'); ?>