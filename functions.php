<?php

use Theme_base\Account;
use Theme_base\Base;
use Theme_base\Checkout;
use Theme_base\Impressions;
use Theme_base\Personalisation;
use Theme_base\PremiumPricing;
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

// Fige l'achat de l'option Studio (+150 CHF) sur la commande au checkout.
// Les commandes manuelles se règlent dans le champ ACF de la commande.
Studio::register();

// Fige le choix d'impressions Premium (400 / 800 / illimité) au checkout.
// Sans add-on sélectionné, l'ACF reste vide : l'admin fixe la valeur pour
// une commande manuelle ou pour un client resté sur le tarif de base 400.
Impressions::register();

// Multiplie les add-ons WAPO Premium par le nombre de Lumiboo choisi
// (YITH Booking « person types »). Sans cette classe, deux Lumiboo à 800
// impressions ne facturent qu'une fois le supplément d'impressions.
PremiumPricing::register();



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
