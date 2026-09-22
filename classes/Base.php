<?php

namespace Theme_base;

class Base
{
    private string $theme_name;
    private string $theme_slug;

    public function __construct(string $theme_name, string $theme_slug)
    {
        $this->theme_name = $theme_name;
        $this->theme_slug = $theme_slug;
    }

    public function themeSupports(): void
    {
        add_action('after_setup_theme', function () {
            // Menus
            add_theme_support('menus');
            add_theme_support('post-thumbnails');
            add_theme_support('title-tag');
            add_post_type_support('page', 'excerpt');
            // Enables post and comment RSS feed links to head
            add_theme_support('automatic-feed-links');
            // I18N
            load_theme_textdomain('theme_base', get_template_directory() . '/languages');
            // Activer le lazy loading natif
            add_theme_support('lazy-loading-images');
            // Ajouter des tailles d'images optimisées
            add_image_size('logo', 60, 60, true);
            add_image_size('post-preview-image', 400, 350, true);

            // WooCommerce
            add_theme_support('woocommerce');
        }, 99);
    }

    /**
     * Register ACF options pages (Footer + Theme).
     * Requires ACF PRO. Values are stored in wp_options (post_id = 'options').
     */
    public function registerOptionsPages(): void
    {
        add_action('acf/init', function () {
            if (! function_exists('acf_add_options_page')) {
                return;
            }

            acf_add_options_page([
                'page_title'      => __('Footer', 'theme_base'),
                'menu_title'      => __('Footer', 'theme_base'),
                'menu_slug'       => 'footer',
                'capability'      => 'edit_posts',
                'redirect'        => false,
                'autoload'        => false,
                'update_button'   => __('Mise à jour', 'theme_base'),
                'updated_message' => __('Options mises à jour', 'theme_base'),
            ]);

            acf_add_options_page([
                'page_title'      => __('Theme', 'theme_base'),
                'menu_title'      => __('Theme', 'theme_base'),
                'menu_slug'       => 'theme',
                'capability'      => 'edit_posts',
                'redirect'        => false,
                'autoload'        => false,
                'update_button'   => __('Mise à jour', 'theme_base'),
                'updated_message' => __('Options mises à jour', 'theme_base'),
            ]);

            acf_add_options_page([
                'page_title'      => __('Pictogrammes', 'theme_base'),
                'menu_title'      => __('Pictogrammes', 'theme_base'),
                'menu_slug'       => 'pictograms',
                'capability'      => 'edit_posts',
                'redirect'        => false,
                'autoload'        => false,
                'update_button'   => __('Mise à jour', 'theme_base'),
                'updated_message' => __('Options mises à jour', 'theme_base'),
            ]);

            acf_add_options_page([
                'page_title'      => __('Lumiboo', 'theme_base'),
                'menu_title'      => __('Lumiboo', 'theme_base'),
                'menu_slug'       => 'lumiboo-settings',
                'capability'      => 'manage_options',
                'redirect'        => false,
                'autoload'        => false,
                'update_button'   => __('Mise à jour', 'theme_base'),
                'updated_message' => __('Options mises à jour', 'theme_base'),
            ]);
        });
    }

    /**
     * Share WordPress post categories ("category" taxonomy) with WooCommerce products.
     * This makes the "Categories" metabox available on the product edit screen
     * and lets posts/products reference the same term tree.
     *
     * @param bool $rename_admin_labels Rename admin labels to disambiguate
     *                                  "Post categories" vs "Product categories".
     */
    public function link_category_to_product(bool $rename_admin_labels = true): void
    {
        add_action('init', function () use ($rename_admin_labels) {
            register_taxonomy_for_object_type('category', 'product');

            if (! $rename_admin_labels) {
                return;
            }

            $post_cat = get_taxonomy('category');
            if ($post_cat && isset($post_cat->labels)) {
                $post_cat->labels->name          = __('Post categories', 'theme_base');
                $post_cat->labels->singular_name = __('Post category', 'theme_base');
                $post_cat->labels->menu_name     = __('Post categories', 'theme_base');
            }

            $product_cat = get_taxonomy('product_cat');
            if ($product_cat && isset($product_cat->labels)) {
                $product_cat->labels->name          = __('Product categories', 'theme_base');
                $product_cat->labels->singular_name = __('Product category', 'theme_base');
                $product_cat->labels->menu_name     = __('Product categories', 'theme_base');
            }
        }, 100);
    }

    /**
     * Customize the WooCommerce breadcrumb wrapper and separator.
     * Requires two SVGs (looked up via inline_svg()):
     *   - home.svg
     *   - simple-chevron-right.svg
     */
    public function change_breadcrumb_delimiter(): void
    {
        add_filter('woocommerce_breadcrumb_defaults', function ($defaults) {
            $home_svg      = self::inline_svg('home.svg');
            $separator_svg = self::inline_svg('simple-chevron-right.svg');

            $defaults['wrap_before'] = ' <div id="breadcrumbs" class="breadcrumbs-product-cat container"> <div class="svg-container home-icon"> ' . $home_svg . '</div> ';
            $defaults['wrap_after']  = ' </div> ';
            $defaults['delimiter']   = ' <div class="svg-container separator-icon"> ' . $separator_svg . '</div> ';

            return $defaults;
        });
    }

    /**
     * Safely inline an SVG from the theme's resources/images/ folder.
     * Returns an empty string if the file is missing.
     */
    public static function inline_svg(string $filename): string
    {
        $filename = ltrim($filename, '/');
        $path = get_template_directory() . '/resources/images/' . $filename;

        if (is_readable($path)) {
            return (string) file_get_contents($path);
        }

        return '';
    }

    /**
     * Inline an SVG from a media library attachment (ACF image field: ID or array).
     * Returns an empty string if the attachment is missing or not an SVG.
     */
    public static function inline_svg_from_attachment($attachment): string
    {
        $id = is_array($attachment) ? (int) ($attachment['ID'] ?? 0) : (int) $attachment;
        if ($id <= 0) {
            return '';
        }

        $path = get_attached_file($id);
        if (! $path || ! is_readable($path)) {
            return '';
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'svg') {
            return '';
        }

        $svg = (string) file_get_contents($path);

        return wp_kses($svg, self::svg_allowed_tags());
    }

    /**
     * Whitelist of SVG tags/attributes for wp_kses sanitization.
     *
     * @return array<string, array<string, bool>>
     */
    private static function svg_allowed_tags(): array
    {
        return [
            'svg'      => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'class' => true, 'aria-hidden' => true, 'role' => true, 'focusable' => true, 'preserveaspectratio' => true],
            'g'        => ['fill' => true, 'stroke' => true, 'transform' => true, 'class' => true, 'clip-path' => true, 'mask' => true],
            'path'     => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'transform' => true, 'class' => true],
            'circle'   => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true],
            'ellipse'  => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'rect'     => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'line'     => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'defs'     => [],
            'clippath' => ['id' => true],
            'title'    => [],
            'desc'     => [],
        ];
    }

    public function registerMenus(): void
    {
        register_nav_menus([
            'header' => __('Header', 'theme_base'),
            'footer' => __('Footer', 'theme_base'),
            'policy' => __('Policy', 'theme_base'),
        ]);
    }

    public function allowSVGUploads(): void
    {
        add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($ext === 'svg' || $ext === 'svgz') {
                $data['ext']  = $ext;
                $data['type'] = 'image/svg+xml';
            }

            return $data;
        }, 10, 4);
    }

    public function addSVGSupport(): void
    {
        add_action('init', function () {

            add_filter('upload_mimes', function ($mimes) {
                $mimes['svg']  = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';

                return $mimes;
            });

            add_filter('mime_types', function ($mimes) {
                $mimes['svg']  = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';

                return $mimes;
            });
        });
    }

    /**
     * Detect if the current ACF validation context targets an SVG file.
     * Handles the three contexts used by ACF: upload, prepare, basic_upload.
     */
    private static function isSvgAttachmentContext(array $attachment): bool
    {
        $ext_matches = static function (string $filename): bool {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return $ext === 'svg' || $ext === 'svgz';
        };

        if (! empty($attachment['tmp_name']) && ! empty($attachment['name'])) {
            return $ext_matches((string) $attachment['name']);
        }

        if (! empty($attachment['id'])) {
            $path = get_attached_file((int) $attachment['id']);
            return $path ? $ext_matches($path) : false;
        }

        $mime = (string) ($attachment['mime'] ?? $attachment['type'] ?? '');
        return $mime === 'image/svg+xml';
    }

    /**
     * Allow SVG files inside ACF image (and gallery) fields.
     * ACF 6.8.7+ hardened image field validation which rejects SVGs even when
     * WordPress core accepts them. We selectively bypass the three ACF checks
     * (`upload`, `prepare`, `basic_upload`) and the field-level `validate_value`
     * only when the underlying file is a real SVG.
     */
    public function enableSvgInAcfImageFields(): void
    {
        add_filter('acf/validate_is_image_attachment', function ($errors, $file, $attachment, $field, $context) {
            if (is_array($errors) && is_array($attachment) && self::isSvgAttachmentContext($attachment)) {
                unset($errors['invalid_image']);
            }
            return $errors;
        }, 20, 5);

        $bypass_svg = function ($valid, $value, $field, $input) {
            if ($valid === true || ! is_numeric($value)) {
                return $valid;
            }

            $path = get_attached_file((int) $value);
            if ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg') {
                return true;
            }

            return $valid;
        };

        add_filter('acf/validate_value/type=image', $bypass_svg, 20, 4);
        add_filter('acf/validate_value/type=gallery', $bypass_svg, 20, 4);
    }


    public static function get_meta_description()
    {
        if (is_category()) {
            return get_queried_object()->description;
        } elseif (is_page() || is_single()) {
            return get_the_excerpt();
        } else {
            return bloginfo('description');
        }
    }

    public static function get_breadcrumbs()
    {
        $links = [];
        $home_link = [
            'url' => home_url(),
            'text' => 'Accueil'
        ];
        array_push($links, $home_link);
        $cats = get_the_terms(get_the_ID(), 'category');
        if (! empty($cats)) {
            $cat_link = [
                'url' => get_category_link($cats[0]->term_id),
                'text' => $cats[0]->name
            ];
            array_push($links, $cat_link);
        }
        $current_page = [
            'url' => get_permalink(),
            'text' => get_the_title()
        ];
        array_push($links, $current_page);
        return $links;
    }

    /**
     * Get nav menu items by location
     *
     * @param string|null $location The menu location id
     */
    public static function  wp_get_menu_array(?string $location = null, $args = []): array
    {
        // Get all locations
        $locations = get_nav_menu_locations();

        if ($location === null || !array_key_exists($location, $locations)) {
            return [];
        }

        // Get object id by location
        $object = wp_get_nav_menu_object($locations[$location]);
        // Get menu items by menu name
        $menu_items = wp_get_nav_menu_items($object->name, array('update_post_term_cache' => false));
        _wp_menu_item_classes_by_context($menu_items);
        // Return menu post objects
        $menu = [];

        foreach ($menu_items as $k => $m) {

            if (empty($m->menu_item_parent)) {
                $menu[$m->ID] = [];
                $menu[$m->ID]['ID'] = intval($m->ID);
                $menu[$m->ID]['title'] = $m->title;
                $menu[$m->ID]['classes'] = $m->classes;
                $menu[$m->ID]['url'] = $m->url;
                $menu[$m->ID]['object_id'] = intval($m->object_id);
                if ($m->type === 'post_type') {
                    $object = get_post($m->object_id);
                } elseif ($m->type === 'taxonomy') {
                    $object = get_term($m->object_id);
                }
                $menu[$m->ID]['object'] = $object;
                $menu[$m->ID]['target'] = $m->target;
                unset($menu_items[$k]);
                $menu[$m->ID]['children'] = self::populate_children($menu_items, $m);
            }
        }
        return $menu;
    }

    /**
     * Populate children
     *
     */

    public static function populate_children(?array $menu_array = null, ?\WP_Post $menu_item = null): array
    {
        if (!$menu_item instanceof \WP_Post) {
            return [];
        }

        $menu_array = $menu_array ?? [];

        $children = [];
        if (!empty($menu_array)) {
            foreach ($menu_array as $k => $m) {
                if ($m->menu_item_parent == $menu_item->ID) {
                    $children[$m->ID] = [];
                    $children[$m->ID]['ID'] = intval($m->ID);
                    $children[$m->ID]['title'] = $m->title;
                    $children[$m->ID]['classes'] = $m->classes;
                    $children[$m->ID]['url'] = $m->url;
                    $children[$m->ID]['parent'] = intval($menu_item->ID);
                    $children[$m->ID]['target'] = $m->target;
                    $children[$m->ID]['object_id'] = intval($m->object_id);
                    // Get the object based on the menu item type
                    if ($m->type === 'post_type') {
                        $children[$m->ID]['object'] = get_post($m->object_id);
                    } elseif ($m->type === 'taxonomy') {
                        $children[$m->ID]['object'] = get_term($m->object_id);
                    }
                    unset($menu_array[$k]);
                    $children[$m->ID]['children'] = self::populate_children($menu_array, $m);
                }
            }
        };
        return $children;
    }

    public static function get_active_class($item): string
    {
        if (in_array('current-menu-item', $item['classes'] ?? [])) {
            return 'active';
        }
        return '';
    }

    /**
     * Check if an object is a WP_Post or WP_Term
     * @param mixed $object The object to check
     * @return string 'post'|'term'|'unknown'
     */
    public static function get_object_type($object): string
    {
        if ($object instanceof \WP_Post) {
            return 'post';
        } elseif ($object instanceof \WP_Term) {
            return 'term';
        }
        return 'unknown';
    }

    /**
     * Return 'active' when a menu item (or any descendant) matches the current page.
     * Works for Custom Link dropdown parents that never receive WP's native
     * current-menu-parent / current-menu-ancestor classes (e.g. product children).
     *
     * @param array $item   Menu item array as produced by wp_get_menu_array()
     * @param mixed $object Current queried object (WP_Post|WP_Term|null)
     */
    public static function get_parent_active_class($item, $object = null): string
    {
        if (! is_array($item)) {
            return '';
        }

        $flags          = ['current-menu-item', 'current-menu-parent', 'current-menu-ancestor'];
        $page_for_posts = (int) get_option('page_for_posts');

        if (function_exists('is_shop') && function_exists('wc_get_page_id') && is_shop()) {
            $shop = get_post(wc_get_page_id('shop'));
            if ($shop) {
                $object = $shop;
            }
        }

        $current_post_id = $object instanceof \WP_Post ? (int) $object->ID : 0;
        $current_term    = $object instanceof \WP_Term ? $object : null;
        $is_single_post  = $object instanceof \WP_Post && $object->post_type === 'post';

        $stack = [$item];
        while ($stack) {
            $node = array_pop($stack);
            if (! is_array($node)) {
                continue;
            }

            if (array_intersect($flags, $node['classes'] ?? [])) {
                return 'active';
            }

            if ($current_post_id && (int) ($node['object_id'] ?? 0) === $current_post_id) {
                return 'active';
            }

            $node_object = $node['object'] ?? null;

            if ($is_single_post && $page_for_posts && $node_object instanceof \WP_Post && $node_object->ID === $page_for_posts) {
                return 'active';
            }

            if ($current_term && $node_object instanceof \WP_Term && $node_object->term_id === (int) $current_term->parent) {
                return 'active';
            }

            foreach ($node['children'] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return '';
    }

    /**
     * Register sidebars and widgetized areas.
     *
     * search
     *
     */
    public function registerWidgets(): void
    {
        add_action('widgets_init', function () {

            //add sidebars and widgets here

        });
    }

    /**
     * @return void
     * add widget for language selector if wpml is active
     */
    public function sidebar_widgets_language_selector_init(): void
    {
        add_action('widgets_init',  function () {
            register_sidebar(array(
                'name'          => 'language_selector_theme_base',
                'id'            => 'language_selector',
                'before_widget' => '<ul class="language-selector">',
                'after_widget'  => '</ul>',
                'before_title'  => '<li>',
                'after_title'   => '</li>',
            ));
        });
    }


    /**
     * Calcule le temps de lecture estimé d'un contenu
     * @param string $content Le contenu du post
     * @return string Le temps de lecture formaté
     */
    public static function get_reading_time(string $content = ''): string
    {
        // Si pas de contenu, utiliser le contenu du post courant
        if (empty($content)) {
            $content = get_the_content();
        }

        // Nettoyer le contenu des balises HTML
        $clean_content = strip_tags($content);

        // Nombre de caractères par minute (vitesse moyenne de lecture)
        $chars_per_minute = 1500;

        // Calculer le temps en minutes
        $chars_count = strlen($clean_content);
        $minutes = ceil($chars_count / $chars_per_minute);

        // Formater le résultat
        if ($minutes <= 1) {
            return __('1 min read', 'theme_base');
        } else {
            return sprintf(__('%d min read', 'theme_base'), $minutes);
        }
    }


    /**
     * Generate pagination for a query
     * @param \WP_Query $query The query to generate pagination for
     * @return array The pagination array
     */
    public static function pagination(?\WP_Query $query = null): array
    {
        $query = $query ?? ($GLOBALS['wp_query'] ?? null);
        if (!$query instanceof \WP_Query) {
            return [];
        }

        $currentPage = max(1, get_query_var('paged', 1));
        $pages = range(1, max(1, (int) $query->max_num_pages));
        return array_map(function ($page) use ($currentPage) {
            return (object) array(
                "isCurrent" => $page == $currentPage,
                "page" => $page,
                "url" => get_pagenum_link($page)
            );
        }, $pages);
    }

    /**
     * Cat_has_no_parent_category
     *
     * check if category has a parent
     * used in category.php
     */

    public static function cat_has_no_parent_category(?\WP_Term $cat = null): bool
    {
        if (!$cat instanceof \WP_Term) {
            return false;
        }

        return $cat->parent === 0;
    }

    /**
     * Cat_middle_category
     *
     * check if category has a parent and a child
     * used in category.php
     */

    public static function cat_middle_category(?\WP_Term $cat = null): bool
    {
        if (!$cat instanceof \WP_Term) {
            return false;
        }

        $children = get_term_children($cat->term_id, $cat->taxonomy);

        return ($cat->parent > 0) && !empty($children);
    }

    /**
     * Cat_child_category
     *
     * check if category has a parent and no child
     * used in category.php
     */

    public static function cat_child_category(?\WP_Term $cat = null): bool
    {
        if (!$cat instanceof \WP_Term) {
            return false;
        }

        $children = get_term_children($cat->term_id, $cat->taxonomy);

        return ($cat->parent > 0) && empty($children);
    }

    public static function get_logic_taxonomy_and_term_id($object, $category = 'category'): array
    {
        $taxonomy        = $category;
        $default_term_id = (int) get_option('default_category');

        $is_product     = $object instanceof \WP_Post && $object->post_type === 'product';
        $is_post        = $object instanceof \WP_Post && $object->post_type === 'post';
        $is_faq         = $object instanceof \WP_Post && $object->post_type === 'faq';
        $is_page        = $object instanceof \WP_Post && $object->post_type === 'page';
        $is_category    = $object instanceof \WP_Term && $object->taxonomy === 'category';
        $is_product_cat = $object instanceof \WP_Term && $object->taxonomy === 'product_cat';

        $get_field = function ($key, $ctx) use ($default_term_id) {
            if (! function_exists('get_field')) {
                return $default_term_id;
            }
            return get_field($key, $ctx) ?: $default_term_id;
        };

        switch (true) {
            case $is_product:
                if ($category === 'product_cat') {
                    $product_cat_id = Base::get_post_primary_term($object, 'product_cat');
                    $product_cat    = get_term($product_cat_id, 'product_cat');
                    $term_id        = $product_cat ? (int) $get_field('product_cat_archive_category', $product_cat) : $default_term_id;
                } else {
                    $term_id = Base::get_post_primary_term($object, 'category');
                }

                break;

            case $is_product_cat:
                $term_id = (int) $get_field('product_cat_archive_category', $object);
                break;

            case $is_post:
            case $is_faq:
                $term_id = Base::get_post_primary_term($object, 'category');
                break;

            case $is_page:
                $term_id = (int) $get_field('page_category', $object->ID);
                break;

            case $is_category:
                $term_id = $object->term_id;
                break;

            default:
                $term_id = $default_term_id;
                break;
        }

        return [
            'taxonomy' => $taxonomy,
            'term_id'  => $term_id,
        ];
    }


    public static function get_post_primary_term($object = null, $taxonomy = 'category'): int
    {
        $term_id = 0;

        if ($object instanceof \WP_Post) {
            $obj_terms = get_the_terms($object->ID, $taxonomy);
            if (!empty($obj_terms) && is_array($obj_terms)) {
                $yoast_get_primary = 'yoast_get_primary_term_id';
                if (function_exists($yoast_get_primary)) {
                    $term_id = $yoast_get_primary($taxonomy, $object->ID) ?: (int) $obj_terms[0]->term_id;
                } else {
                    $term_id = (int) $obj_terms[0]->term_id;
                }
            }
        }

        return (int) $term_id;
    }



    public static function pre_debug($data)
    {
        echo '<pre class="debug" style="color: #17194a; position: fixed; top: 0; left: 0; width: 100%; max-width: 75vi; height: 75vh; display: block; background-color: white; padding: 100px; z-index: 9999; overflow-y: scroll;">';
        print_r($data);
        echo '</pre>';
    }
}
