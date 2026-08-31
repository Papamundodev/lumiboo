<?php

/**
 * Empty cart page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-empty.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 *
 * OVERRIDE: theme_base theme
 */

defined('ABSPATH') || exit;

/*
 * @hooked wc_empty_cart_message - 10
 */
do_action('woocommerce_cart_is_empty');

if (wc_get_page_id('shop') > 0) : ?>
	<p class="return-to-shop">
		<a class="button wc-backward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
			<?php
			/**
			 * Filter "Return To Shop" text.
			 *
			 * @since 4.6.0
			 * @param string $default_text Default text.
			 */
			echo esc_html(apply_filters('woocommerce_return_to_shop_text', __('Return to shop', 'woocommerce')));
			?>
		</a>
	</p>
<?php endif; ?>

<?php
$products_query = new WP_Query(array(
	'post_type' => 'product',
	'posts_per_page' => 10,
	'orderby' => 'date',
	'order' => 'DESC',
));
wp_reset_postdata();

get_template_part('partials/woocommerce/products/sliders/products', null, [
	'products' => $products_query->posts,
	'title' => __('Nos derniers produits', 'theme_base'),
]);
?>
