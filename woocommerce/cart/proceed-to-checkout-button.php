<?php

/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/proceed-to-checkout-button.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>

<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="checkout-button btn button-background-primary alt wc-forward<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>">
	<span class="button-text"><?php esc_html_e('Proceed to checkout', 'woocommerce'); ?></span>
</a>
