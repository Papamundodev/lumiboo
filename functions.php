<?php

use Theme_base\Base;
use Theme_base\Woocommerce;

/**
 * Theme constants — change THEME_HANDLE once when cloning this theme for a new site.
 */
define('THEME_HANDLE', 'drone');
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

$woocommerce = new Woocommerce();
$woocommerce->loadWooCommerceStylesOnlyWhenNeeded();
$woocommerce->loadWcBlocksCssOnlyWhenNeeded();
$woocommerce->deferOrderAttributionOnNonCheckout();
$woocommerce->fixExpressCheckoutOnClassicCheckout();
$woocommerce->fixPpcpRecaptchaOnClassicCheckout();
$woocommerce->setLoopShopPerPage(9);

/**
 * Product attributes / checkout fields — DORMANT PAR DÉFAUT.
 *
 * Décommenter les 2 blocs pour activer un attribut affiché (cartes + PDP)
 * et/ou un champ checkout persisté en meta commande.
 *
 * 1. registerProductAttribute  (affichage + création optionnelle pa_{slug})
 * 2. addCheckoutField          (classic checkout → meta → admin / customer / emails)
 */
// $woocommerce->registerProductAttribute([
//     'slug'   => 'duree',
//     'label'  => __('Duration', 'theme_base'),
//     'icon'   => 'time',
//     'prefix' => __('Duration: ', 'theme_base'),
//     'type'   => 'select',
//     'create' => false,
// ]);

// $woocommerce->addCheckoutField([
//     'id'          => 'training_objectives',
//     'label'       => __('Expected objectives', 'theme_base'),
//     'placeholder' => __('Tell us what you expect from this course', 'theme_base'),
//     'required'    => true,
//     'show_in'     => ['admin', 'customer', 'emails'],
// ]);


/**
 * Search — configuration du mu-plugin `search`.
 *
 * Le plugin (mu-plugins/search/) est autonome : il enregistre lui-même l'AJAX,
 * enqueue son JS/CSS, et rend le bouton (via `theme_toolkit_render`) + le
 * popover (via `wp_footer`). Le thème pilote uniquement :
 *   - la liste des post_types recherchés
 *   - la stratégie (MODE_DEFAULT | MODE_OPTIMIZED)
 */
add_filter('search_plugin_post_types', function (array $types): array {
    if (class_exists(\PrenomsSync\Setup::class)) {
        $types[] = \PrenomsSync\Setup::CPT_SLUG;
    }
    return $types;
});

add_filter('search_plugin_mode', function (): string {
    return \SearchPlugin\Search::MODE_OPTIMIZED;
});


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


/**
 * Google Reviews — fournie par le mu-plugin `google-reviews`.
 *
 * Le plugin est autonome : page d'options ACF `Avis Google`, import Places
 * API à la demande, rendu. Le thème appelle uniquement
 * `do_action('theme_google_reviews_render')` dans front-page.php.
 *
 * L'enregistrement du groupe ACF est commenté dans
 * google-reviews/google-reviews.php (groupe déjà importé en base sur ce
 * site) — à décommenter sur un nouveau site, comme pour odometer.
 *
 * Filtres disponibles : google_reviews_plugin_should_render,
 * google_reviews_plugin_config, google_reviews_plugin_display,
 * google_reviews_plugin_data, google_reviews_plugin_reviews.
 */



function pre_debug($data)
{
    echo '<pre class="debug" style="color: #17194a; position: fixed; top: 0; left: 0; width: 100%; max-width: 75vi; height: 75vh; display: block; background-color: white; padding: 100px; z-index: 9999; overflow-y: scroll;">';
    print_r($data);
    echo '</pre>';
}
