<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <meta name="description" content="<?= esc_attr(\Theme_base\Base::get_meta_description()) ?> ">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class("") ?>>
    <?php
    if (function_exists('wp_body_open')) {
        wp_body_open();
    }

    use Theme_base\Base;
    ?>

    <header class="main-header">
        <div class="header-container container">

            <a class="logo-container" href="<?= home_url(); ?>">
                <img src="<?= get_template_directory_uri(); ?>/resources/images/logo.png" alt="Logo">
            </a>

            <?php get_template_part('partials/header/navbar-desktop', 'header'); ?>
            <?php get_template_part('partials/header/navbar-mobile', 'header'); ?>



            <div class="burger-container">
                <button title="<?= __('Ouvrir le menu', 'theme_base'); ?>" class="burger-container btn" popovertarget="navmenu-header-mobile" id="theme-navbar-toggler">
                    <div id="burger-menu" class="burger-menu">
                        <span class="custom-burger"></span>
                        <span class="custom-burger"></span>
                        <span class="custom-burger"></span>
                        <span class="custom-burger"></span>
                    </div>
                </button>
            </div>
        </div>
    </header>


    <aside class="drawer">
        <div class="wrapper-tool-kit">
            <div class="cart-container icon-container">
                <?php
                $cart_count = 0;
                if (function_exists('WC') && WC()->cart) {
                    $cart_count = (int) WC()->cart->get_cart_contents_count();
                }
                ?>
                <?php if ($cart_count > 0) : ?>
                    <span class="cart-count" aria-label="<?= esc_attr(sprintf(_n('%d article dans le panier', '%d articles dans le panier', $cart_count, 'theme_base'), $cart_count)); ?>">
                        <?= esc_html($cart_count); ?>
                    </span>
                <?php endif; ?>
                <a href="<?= esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart')); ?>" class="btn" title="<?= __('Voir le panier', 'theme_base'); ?>">
                    <?= Base::inline_svg('cart.svg'); ?>
                </a>
            </div>
        </div>
    </aside>