<?php

use Theme_base\Account;
use Theme_base\Base;
use Theme_base\BookingAddonSync;
use Theme_base\Checkout;
use Theme_base\Family;
use Theme_base\Form;
use Theme_base\Gamme;
use Theme_base\Impressions;
use Theme_base\OrderBookings;
use Theme_base\Personalisation;
use Theme_base\Studio;
use Theme_base\Woocommerce;

/**
 * Theme constants — change THEME_HANDLE once when cloning this theme for a new site.
 */
define('THEME_HANDLE', 'lumiboo');
define('THEME_VERSION', '0.1.0');
define('THEME_PATH', get_template_directory());
define('THEME_URI', get_template_directory_uri());
define('THEME_HMR_HOST', 'http://localhost:5173');
define('THEME_ASSETS_PATH', THEME_PATH . '/dist');
define('THEME_ASSETS_URI', THEME_URI . '/dist');
define('THEME_RESOURCES_URI', THEME_URI . '/resources');

/**
 * Initialize Vite asset handling
 */
require_once get_template_directory() . '/vendor/autoload.php';

$vite = new \Theme_base\Vite(
    THEME_HMR_HOST,
    THEME_ASSETS_PATH,
    THEME_ASSETS_URI,
    THEME_VERSION,
    THEME_HANDLE
);
$vite->enqueue();

$base = new Base('theme_base', 'theme_base');
$base->themeSupports();
$base->registerOptionsPages();
$base->registerMenus();
$base->allowSVGUploads();
$base->addSVGSupport();
$base->enableSvgInAcfImageFields();
$base->link_category_to_product();
$base->change_breadcrumb_delimiter();
$base->sidebar_widgets_trustindex_init();
$base->sidebar_widgets_trustindex_slider_init();

// NOTE: ne pas nommer cette variable `$woocommerce` — c'est le nom de la
// globale WP utilisée par WooCommerce (et par YITH, cf. plugin-fw/includes/
// class-yit-assets.php ~L225 : `$woocommerce->plugin_url()`). L'écraser
// provoque un fatal error côté admin.
$theme_wc = new Woocommerce();
$theme_wc->loadWooCommerceStylesOnlyWhenNeeded();
$theme_wc->loadWcBlocksCssOnlyWhenNeeded();
$theme_wc->deferOrderAttributionOnNonCheckout();
$theme_wc->fixExpressCheckoutOnClassicCheckout();
$theme_wc->fixPpcpRecaptchaOnClassicCheckout();
$theme_wc->setLoopShopPerPage(9);

/**
 * Parcours de réservation : facturation seule au checkout.
 *
 * Les champs du checkout sont pilotés depuis YITH Checkout Manager ; la
 * disposition du wireframe est en CSS. Aucun template WooCommerce n'est
 * surchargé pour cet écran.
 */
$checkout = new Checkout();
$checkout->register();

// Compte client auto-créé au checkout (chunk 4) : registration required
// + mot de passe généré, mail natif « Nouveau compte » avec lien de
// définition, filet de sécurité `ensureCustomerAccount` en priorité 10
// avant Studio/Impressions, carte Lumiboo sur la page de remerciement.
Account::register();
Personalisation::register();

// Une machine par commande (G02) : quantité 1, un ajout remplace le précédent.
// Décommenter la ligne suivante pour réactiver la règle.
$checkout->enforceSingleProductPerOrder();

// Parcours sans page panier (R01) : add-to-cart → checkout, /cart/ fermée.
// Décommenter la ligne suivante pour réactiver l'absence de page panier.
$checkout->disableCartPage();

// Taxonomie `gamme` (premium / studio) sur produit, réservation et
// ressource. La réservation neuve hérite de la ressource en priorité 5,
// avant les snapshots Studio / Impressions. Fiche produit : template
// `woocommerce/single-product-{gamme}.php` s'il existe, sinon natif.
Gamme::register();

// Fige l'option Studio (+150 CHF) sur la réservation à sa création YITH
// (checkout et back-office). Contexte ACF : yith_booking_{id}.
Studio::register();

// Fige le choix d'impressions Premium (400 / 800 / illimité) sur la
// réservation Premium à sa création. Sans add-on, l'ACF n'est pas écrit.
Impressions::register();

// Sens inverse : quand l'admin change `lumiboo_prints` ou
// `lumiboo_has_studio_option` sur la réservation depuis la commande, ré-écrit
// l'add-on WAPO sur l'item et ajuste le total du même montant.
BookingAddonSync::register();

// Contexte ACF `yith_booking_{id}` → post de la réservation.
// La localisation Premium / Studio est la règle native `post_taxonomy`.
Family::register();

// Métabox admin « Lumiboo — Réservations » sur la commande (HPOS + hérité).
// Liste chaque booking YITH lié : titre, dates, produit, ressource +
// formulaire ACF inline via `acf_form()` ciblant `yith_booking_{id}`. Le
// toggle `lumiboo_locked_by_admin` est remonté en tête. Aucune persistance
// custom : l'écriture passe par `acf_form_head()` sur `admin_init`
// priorité 1. Voir `classes/OrderBookings.php` pour le détail du contour
// « form-in-scope » (`<template>` + JS de déplacement).
OrderBookings::register();
Form::register();

/**
 * FAQ — fournie par le mu-plugin `faq` (mu-plugins/faq/).
 *
 * Le plugin est autonome : CPT `faq`, taxonomie `faq_category`, groupe de
 * champs ACF, JS/CSS et rendu. Le thème appelle uniquement
 * `do_action('theme_faq_render')` dans footer.php pour choisir l'emplacement.
 *
 * Filtres disponibles : faq_plugin_post_type_args, faq_plugin_taxonomy_args,
 * faq_plugin_taxonomy_objects, faq_plugin_should_render,
 * faq_plugin_query_args, faq_plugin_section_title.
 */


/**
 * Odometer — fournie par le mu-plugin `odometer` (mu-plugins/odometer/).
 *
 * Le plugin est autonome : répéteur ACF `section_chiffres`, librairie odometer
 * embarquée, JS/CSS et rendu. Le thème appelle uniquement
 * `do_action('theme_odometer_render')` dans front-page.php pour choisir
 * l'emplacement.
 *
 * L'enregistrement du groupe ACF est commenté dans odometer/odometer.php
 * (groupe déjà importé en base sur ce site) — à décommenter sur un nouveau
 * site, comme pour la FAQ.
 *
 * Filtres disponibles : odometer_plugin_should_render,
 * odometer_plugin_field_name, odometer_plugin_items,
 * odometer_plugin_section_title, odometer_plugin_wrapper_class,
 * odometer_plugin_duration.
 */



// Fiche produit bookable : deux blocs WYSIWYG ACF (booking_intro + option_description)
// autour des add-ons YITH Booking / WAPO. Voir Woocommerce::renderBookingProductAcfBlocks().
$theme_wc->renderBookingProductAcfBlocks();
