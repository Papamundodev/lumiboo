<?php

/**
 * Single Product title
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/title.php.
 *
 * @see        https://woocommerce.com/document/template-structure/
 * @package    WooCommerce\Templates
 * @version    1.6.4
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

the_title('<h1 class="product_title entry-title">', '</h1>');
