<?php

/**
 * Product Loop Start
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.0
 */

if (! defined('ABSPATH')) {
	exit;
}
?>
<ul class="products products-grid columns-<?= esc_attr(wc_get_loop_prop('columns')); ?>">
