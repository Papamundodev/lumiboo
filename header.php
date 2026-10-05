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
    use Theme_base\Checkout;
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

            <?php get_template_part('partials/header/nav-cta', 'header'); ?>

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
                $picto_cart = get_field('picto_cart', "option") ?? null;
                ?>
                <?php
                /**
                 * Sans page panier (R01) : l'icône ouvre le checkout, ou
                 * invite à choisir un produit. Sinon, URL panier WooCommerce.
                 */
                if (Checkout::cartPageIsDisabled()) {
                    $cart_url = wc_get_checkout_url();
                } else {
                    $cart_url = wc_get_cart_url();
                }
                ?>
                <a href="<?= esc_url($cart_url); ?>" class="btn" title="<?= __('Voir le panier', 'theme_base'); ?>">
                    <?= $picto_cart ? Base::inline_svg_from_attachment($picto_cart) : Base::inline_svg('cart.svg'); ?>
                </a>
            </div>
            <!-- account  -->
            <div class="account-container icon-container">
                <?php
                $picto_account = get_field('picto_account', "option") ?? null;
                ?>
                <a href="<?= home_url('/my-account'); ?>" class="btn" title="<?= __('Se connecter', 'theme_base'); ?>">
                    <?= $picto_account ? Base::inline_svg_from_attachment($picto_account) : Base::inline_svg('account.svg'); ?>
                </a>
            </div>
        </div>
    </aside>