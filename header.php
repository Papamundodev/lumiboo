<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
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

            <?php
            $logo = get_field('logo', 'option');
            ?>
            <?php if ($logo) : ?>
                <a class="logo-container" href="<?= home_url(); ?>">
                    <img src="<?= $logo['url']; ?>" alt="<?= $logo['alt'] ?? 'Logo'; ?>">
                </a>
            <?php endif; ?>

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
                <?php
                /**
                 * Aucune page panier dans le parcours : l'icône ouvre le
                 * checkout de la sélection en cours, ou invite à choisir un
                 * produit lorsqu'aucune sélection n'existe (R01).
                 */
                $cart_url = $cart_count > 0
                    ? wc_get_checkout_url()
                    : (new Theme_base\Checkout())->productsUrl();
                ?>
                <a href="<?= esc_url($cart_url); ?>" class="btn" title="<?= esc_attr($cart_count > 0 ? __('Voir ma réservation', 'theme_base') : __('Choisir un photobooth', 'theme_base')); ?>">
                    <?= Base::inline_svg('cart.svg'); ?>
                </a>
            </div>
        </div>
    </aside>