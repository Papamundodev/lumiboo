<?php

/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

use Theme_base\Base;
use Theme_base\Woocommerce;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop'); ?>

<?php woocommerce_output_all_notices(); ?>

<?php while (have_posts()) : ?>
    <?php the_post(); ?>

    <?php
    $theme_template_name = basename(__FILE__, ".php");
    $product_id = get_the_ID();
    $product    = wc_get_product($product_id);

    $product_title = get_the_title();
    $product_price = $product ? $product->get_price_html() : '';

    $terms = get_the_terms($product_id, 'product_cat');
    $terms = (! $terms || is_wp_error($terms)) ? [] : $terms;

    $category_id = Base::get_post_primary_term(get_post($product_id), 'product_cat');
    $category = $category_id ? get_term($category_id, 'product_cat') : null;

    $category_image = ($category && ! is_wp_error($category)) ? get_field('image_products', $category) : null;
    $category_image_url = $category_image['sizes']['large'] ?? $category_image['url'] ?? wc_placeholder_img_src('woocommerce_single');
    ?>

    <main id="main-<?= $theme_template_name ?>" role="main">

        <section aria-labelledby="product-title" class="section-product-header img-side-section container">
            <div class="container-small">
                <div class="section-content-img">
                    <div class="section-content">
                        <h1 id="product-title"><?= $product_title; ?></h1>
                        <div class="section-text">
                            <?php if ($terms) : ?>
                                <div class="product-category terms-container">
                                    <?php foreach ($terms as $term) : ?>
                                        <a href="<?= esc_url(get_term_link($term)); ?>" class="btn <?= (int) $term->parent === 0 ? 'main-category' : 'child-category'; ?>">
                                            <span class="product-category"><?= esc_html($term->name); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-meta">
                                <div class="product-rating">
                                    <?php woocommerce_template_single_rating(); ?>
                                </div>
                                <?php Woocommerce::renderAttribute($product, 'duree'); ?>
                            </div>

                            <div class="product-price">
                                <p><?= $product_price; ?></p>
                            </div>

                            <div class="product-add-to-cart" data-qty-buttons>
                                <?php woocommerce_template_single_add_to_cart(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="section-img">
                        <div class="img-container">
                            <img src="<?= esc_url($category_image_url); ?>" alt="<?= esc_attr($category && ! is_wp_error($category) ? $category->name : $product_title); ?>" fetchpriority="high" loading="eager">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php woocommerce_breadcrumb(); ?>


        <?php if ($product->get_description()) : ?>
            <div class="section-content container">
                <h2 class="section-title"><?= __('Description', 'theme_base'); ?></h2>
                <div class="content-wysiwyg">
                    <?= $product->get_description(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="section-add-to-cart">
            <div class="button-wrapper">
                <div class="button-container button-background-primary button-background-animation">
                    <a href="<?= esc_url($product->add_to_cart_url()); ?>"
                        class="btn add_to_cart_button"
                        data-product_id="<?= esc_attr($product->get_id()); ?>"
                        data-quantity="1"
                        aria-label="<?= esc_attr($product->add_to_cart_description()); ?>">
                        <?= esc_html($product->add_to_cart_text()); ?>
                    </a>
                    <span class="hover-bg"></span>
                </div>
            </div>
        </div>

        <?php
        if (comments_open() && get_comments_number()) {
            comments_template();
        }
        ?>

    </main>

<?php endwhile; // end of the loop. 
?>

<?php
get_footer('shop');

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
