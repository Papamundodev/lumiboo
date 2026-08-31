<?php

/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/notices/error.php.
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

<ul id="woocommerce-message-error" class="woocommerce-message" role="alert">
	<?php foreach ($notices as $notice) : ?>
		<li<?php echo wc_get_notice_data_attr($notice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
			?>>
			<?php echo wc_kses_notice($notice['notice']); ?>
		</li>
	<?php endforeach; ?>
</ul>
