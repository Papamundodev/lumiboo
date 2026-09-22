<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/wrapper-start.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$template_slug = 'shop';

if (is_singular('product')) {
    $template_slug = 'single-product';
} elseif (is_product_taxonomy()) {
    $term = get_queried_object();
    $template_slug = $term instanceof WP_Term ? 'taxonomy-' . $term->taxonomy : 'archive-product';
} elseif (is_shop()) {
    $template_slug = 'archive-product';
} elseif (function_exists('is_cart') && is_cart()) {
    $template_slug = 'cart';
} elseif (function_exists('is_checkout') && is_checkout()) {
    $template_slug = 'checkout';
} elseif (function_exists('is_account_page') && is_account_page()) {
    $template_slug = 'my-account';
}

echo '<main id="main-' . esc_attr($template_slug) . '" class="main" role="main">';
