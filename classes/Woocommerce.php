<?php

namespace Theme_base;

/**
 * WooCommerce integration.
 *
 * Performance / classic-checkout fixes, plus generic APIs for product
 * attributes and checkout fields (registerProductAttribute / addCheckoutField).
 */
class Woocommerce
{
    /**
     * Display config for product attributes, keyed by slug.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $product_attributes = [];

    /**
     * Custom checkout fields, keyed by field id.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $checkout_fields = [];

    /**
     * Load WooCommerce styles only on shop-related pages
     * to reduce render-blocking on other pages.
     */
    public function loadWooCommerceStylesOnlyWhenNeeded(): void
    {
        add_filter('woocommerce_enqueue_styles', function (array $styles): array {
            if (! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page()) {
                return [];
            }
            return $styles;
        });
    }

    /**
     * Dequeue WC Blocks CSS on pages that don't use WooCommerce blocks.
     */
    public function loadWcBlocksCssOnlyWhenNeeded(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if (! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page()) {
                wp_dequeue_style('wc-blocks-style');
            }
        }, 100);
    }

    /**
     * Dequeue Order Attribution & Sourcebuster on non-checkout/cart pages.
     * These scripts are only needed on cart/checkout to stamp the form
     * with attribution data.
     */
    public function deferOrderAttributionOnNonCheckout(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if (! is_checkout() && ! is_cart()) {
                wp_dequeue_script('wc-order-attribution');
                wp_dequeue_script('sourcebuster-js');
            }
        }, 20);
    }

    /**
     * Fix empty Google Pay / Apple Pay buttons on classic checkout.
     * When has_block is true, WooCommerce Payments express checkout JS expects
     * block-based DOM. Classic checkout uses #wcpay-express-checkout-element,
     * so we force has_block to false.
     */
    public function fixExpressCheckoutOnClassicCheckout(): void
    {
        add_filter('wcpay_express_checkout_js_params', function (array $params): array {
            if (! is_checkout() || is_wc_endpoint_url('order-received')) {
                return $params;
            }
            $params['has_block'] = false;
            return $params;
        }, 10);
    }

    /**
     * Force PPCP reCAPTCHA to use classic checkout mode (form-based).
     * Prevents "Cannot read properties of null (reading '_internalSetExtensionData')"
     * when has_block() is true but the checkout store is not loaded.
     */
    public function fixPpcpRecaptchaOnClassicCheckout(): void
    {
        add_action('wp_enqueue_scripts', function (): void {
            if (! is_checkout() || is_wc_endpoint_url('order-received')) {
                return;
            }
            $inline = 'if(window.ppcpRecaptchaSettings){window.ppcpRecaptchaSettings.isBlocks=false;}';
            wp_add_inline_script('ppcp-recaptcha-handler', $inline, 'before');
        }, 999);
    }

    /**
     * Number of products per shop / category archive page.
     */
    public function setLoopShopPerPage(int $per_page = 9): void
    {
        add_filter('loop_shop_per_page', static function () use ($per_page): int {
            return $per_page;
        }, 20);
    }

    /**
     * Register a product attribute for display, and optionally create the
     * global WooCommerce attribute (pa_{slug}) when it doesn't exist yet.
     *
     * Creation runs on admin_init only and is idempotent. A newly created
     * attribute is queryable on the following request (WC registers pa_*
     * taxonomies on init priority 5).
     *
     * @param array{slug:string, label?:string, icon?:string, prefix?:string, type?:string, create?:bool} $args
     */
    public function registerProductAttribute(array $args): void
    {
        $slug = isset($args['slug']) ? sanitize_title((string) $args['slug']) : '';
        if ($slug === '') {
            return;
        }

        self::$product_attributes[$slug] = wp_parse_args($args, [
            'slug'   => $slug,
            'label'  => '',
            'icon'   => '',
            'prefix' => '',
            'type'   => 'select',
            'create' => false,
        ]);

        if (empty(self::$product_attributes[$slug]['create'])) {
            return;
        }

        add_action('admin_init', static function () use ($slug): void {
            self::maybeCreateAttribute($slug);
        });
    }

    private static function maybeCreateAttribute(string $slug): void
    {
        if (! function_exists('wc_create_attribute') || ! function_exists('wc_attribute_taxonomy_id_by_name')) {
            return;
        }

        if (wc_attribute_taxonomy_id_by_name($slug) > 0) {
            return;
        }

        $config = self::$product_attributes[$slug];

        wc_create_attribute([
            'name'         => $config['label'] !== '' ? $config['label'] : $slug,
            'slug'         => $slug,
            'type'         => $config['type'],
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ]);
    }

    /**
     * Render one registered attribute for a product.
     * Returns '' when the slug is not registered or the product has no value.
     */
    public static function renderAttribute($product, string $slug, bool $echo = true): string
    {
        $config = self::$product_attributes[$slug] ?? null;
        if ($config === null || ! $product instanceof \WC_Product) {
            return '';
        }

        $value = $product->get_attribute($slug);
        if ($value === '') {
            return '';
        }

        $icon = '';
        if (! empty($config['icon'])) {
            $file = pathinfo($config['icon'], PATHINFO_EXTENSION) ? $config['icon'] : $config['icon'] . '.svg';
            $svg  = Base::inline_svg($file);
            $icon = $svg !== '' ? '<div class="svg-container">' . $svg . '</div>' : '';
        }

        $html = sprintf(
            '<div class="product-attribute product-attribute--%1$s">%2$s<p class="product-attribute-value">%3$s%4$s</p></div>',
            esc_attr($slug),
            $icon,
            esc_html((string) $config['prefix']),
            esc_html($value)
        );

        $html = (string) apply_filters('theme_base_product_attribute_html', $html, $slug, $value, $config, $product);

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    /**
     * Add a custom checkout field, saved to order meta and optionally shown
     * in the admin order screen, the customer order view and emails.
     * Callable any number of times; hooks are attached once.
     *
     * Classic checkout only (`woocommerce_checkout_fields`).
     *
     * @param array{id:string, section?:string, type?:string, label?:string, placeholder?:string,
     *              required?:bool, priority?:int, class?:array, meta_key?:string,
     *              show_in?:array, emails?:array} $args
     */
    public function addCheckoutField(array $args): void
    {
        $id = isset($args['id']) ? sanitize_key((string) $args['id']) : '';
        if ($id === '') {
            return;
        }

        self::$checkout_fields[$id] = wp_parse_args($args, [
            'id'          => $id,
            'section'     => 'order',
            'type'        => 'textarea',
            'label'       => '',
            'placeholder' => '',
            'required'    => false,
            'priority'    => 25,
            'class'       => ['form-row-wide'],
            'meta_key'    => '_' . $id,
            'show_in'     => ['admin', 'customer', 'emails'],
            'emails'      => ['customer_completed_order', 'new_order'],
        ]);

        self::registerCheckoutHooks();
    }

    private static function registerCheckoutHooks(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        add_filter('woocommerce_checkout_fields', [self::class, 'injectCheckoutFields']);
        add_action('woocommerce_checkout_create_order', [self::class, 'saveCheckoutFields'], 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [self::class, 'renderAdminCheckoutFields']);
        add_action('woocommerce_order_details_after_order_table', [self::class, 'renderCustomerCheckoutFields']);
        add_action('woocommerce_email_after_order_table', [self::class, 'renderEmailCheckoutFields'], 10, 4);
    }

    public static function injectCheckoutFields(array $fields): array
    {
        foreach (self::$checkout_fields as $field) {
            $fields[$field['section']][$field['id']] = [
                'type'        => $field['type'],
                'label'       => $field['label'],
                'placeholder' => $field['placeholder'],
                'required'    => (bool) $field['required'],
                'class'       => (array) $field['class'],
                'priority'    => (int) $field['priority'],
            ];
        }

        return $fields;
    }

    /**
     * $data comes from WC_Checkout::get_posted_data(): already unslashed and
     * sanitized per field type, so we never touch $_POST here.
     */
    public static function saveCheckoutFields(\WC_Order $order, array $data): void
    {
        foreach (self::$checkout_fields as $field) {
            $value = isset($data[$field['id']]) ? (string) $data[$field['id']] : '';
            if ($value === '') {
                continue;
            }

            $order->update_meta_data($field['meta_key'], $value);
        }
    }

    public static function renderAdminCheckoutFields(\WC_Order $order): void
    {
        foreach (self::$checkout_fields as $field) {
            $value = (string) $order->get_meta($field['meta_key']);
            if ($value === '' || ! in_array('admin', (array) $field['show_in'], true)) {
                continue;
            }

            echo '<p><strong>' . esc_html($field['label']) . '</strong><br>' . nl2br(esc_html($value)) . '</p>';
        }
    }

    public static function renderCustomerCheckoutFields(\WC_Order $order): void
    {
        foreach (self::$checkout_fields as $field) {
            $value = (string) $order->get_meta($field['meta_key']);
            if ($value === '' || ! in_array('customer', (array) $field['show_in'], true)) {
                continue;
            }

            $heading_id = 'order-field-' . $field['id'];
            printf(
                '<section class="woocommerce-order-custom-field" aria-labelledby="%1$s">'
                    . '<h2 id="%1$s" class="woocommerce-order-details__title">%2$s</h2><p>%3$s</p></section>',
                esc_attr($heading_id),
                esc_html($field['label']),
                nl2br(esc_html($value))
            );
        }
    }

    public static function renderEmailCheckoutFields($order, $sent_to_admin, $plain_text, $email): void
    {
        if (! $order instanceof \WC_Order) {
            return;
        }

        foreach (self::$checkout_fields as $field) {
            if (! in_array('emails', (array) $field['show_in'], true)) {
                continue;
            }

            $allowed = (array) $field['emails'];
            if (! empty($allowed) && (! is_object($email) || ! isset($email->id) || ! in_array($email->id, $allowed, true))) {
                continue;
            }

            $value = (string) $order->get_meta($field['meta_key']);
            if ($value === '') {
                continue;
            }

            if ($plain_text) {
                echo "\n" . wp_strip_all_tags($field['label']) . "\n" . wp_strip_all_tags($value) . "\n";
                continue;
            }

            echo '<h2>' . esc_html($field['label']) . '</h2><p>' . nl2br(esc_html($value)) . '</p>';
        }
    }

    /**
     * Default title for the related-products slider.
     * Override per call via $args['title'], or globally via the filter.
     */
    public static function relatedProductsTitle(array $args = []): string
    {
        $title = $args['title'] ?? __('Our other products', 'theme_base');

        return (string) apply_filters('theme_base_related_products_title', $title, $args);
    }

    /**
     * Fiche produit bookable : deux blocs WYSIWYG ACF autour des add-ons.
     *
     *   1. `booking_intro`       → après les dates, dans la section message
     *      Hook : `yith_wcbk_booking_form_message` (priorité 20, après le
     *      message natif à 10). Rendu à l'intérieur de `.yith-wcbk-booking-form`.
     *
     *   2. `options_description` → après les options WAPO, avant le total.
     *      Hook : `yith_wapo_after_addons`. Rendu à l'intérieur de
     *      `#yith-wapo-container`, juste avant `#wapo-total-price-table`.
     *
     * Dépendances : YITH Booking + YITH WAPO + ACF. Chaque handler check ses
     * dépendances et return silencieusement si un composant manque.
     */
    public function renderBookingProductAcfBlocks(): void
    {
        add_action('yith_wcbk_booking_form_message', [self::class, 'renderBookingIntro'], 20);
        add_action('yith_wapo_after_addons', [self::class, 'renderOptionsDescription']);
    }

    public static function renderBookingIntro(): void
    {
        if (! function_exists('is_product') || ! is_product() || ! function_exists('get_field')) {
            return;
        }

        $intro = get_field('booking_intro');
        if (! $intro) {
            return;
        }

        echo '<div class="lumiboo-booking-intro">' . wp_kses_post($intro) . '</div>';
    }

    public static function renderOptionsDescription(): void
    {
        if (! function_exists('get_field')) {
            return;
        }

        global $product;
        $product_id = $product instanceof \WC_Product ? $product->get_id() : get_the_ID();

        $desc = get_field('option_description', $product_id);
        if (! $desc) {
            return;
        }

        echo '<div class="lumiboo-options-desc">' . wp_kses_post($desc) . '</div>';
    }
}
