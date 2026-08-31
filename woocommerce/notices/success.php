<?php

/**
 * Show messages
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/notices/success.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! $notices) {
	return;
}

?>

<?php foreach ($notices as $notice) : ?>
	<div id="woocommerce-message-success" class="woocommerce-message" <?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
																		?> role="alert">
		<?php echo wc_kses_notice($notice['notice']); ?>
	</div>
<?php endforeach; ?>
