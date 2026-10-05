<?php

namespace Theme_base;

/**
 * Renders the "Lumiboo — Réservations" metabox on the order edit screen and
 * saves each embedded booking form when Woo's own "Update" button is clicked.
 *
 * There is no separate "Enregistrer cette réservation" button anymore: each
 * booking's ACF fields are rendered inline (no nested `<form>`) with per-
 * booking input names (`acf_booking_{id}[...]`) so N booking blocks don't
 * collide on the shared `$_POST['acf']` key. `woocommerce_process_shop_order_meta`
 * then swaps each block's values into `$_POST['acf']` and hands them off to
 * `acf_save_post()`, which fires `acf/save_post` → `BookingAddonSync::sync()`.
 */
class OrderBookings
{

    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetabox'], 30, 2);

        add_action('add_meta_boxes_yith_booking', [self::class, 'hideBookingFieldGroups'], 100);

        // Woo has already verified its own nonce (`woocommerce_meta_nonce`) by
        // the time this fires, on both HPOS (Edit.php) and legacy paths
        // (class-wc-admin-meta-boxes.php).
        add_action('woocommerce_process_shop_order_meta', [self::class, 'saveBookings'], 20);
    }

    /**
     * Iterate every `acf_booking_{id}` block posted with the order and save
     * each one through ACF's normal save pipeline. This is what triggers the
     * `acf/save_post` action that `BookingAddonSync::sync` listens on.
     */
    public static function saveBookings($order_id): void
    {
        if (! function_exists('acf_save_post')) {
            return;
        }

        if (! current_user_can('edit_shop_orders') && ! current_user_can('manage_woocommerce')) {
            return;
        }

        $backup = $_POST['acf'] ?? null;
        $handled = false;

        foreach ($_POST as $key => $values) {
            if (! is_array($values) || ! str_starts_with((string) $key, 'acf_booking_')) {
                continue;
            }

            $booking_id = (int) substr((string) $key, strlen('acf_booking_'));

            if ($booking_id <= 0) {
                continue;
            }

            // ACF's save pipeline reads `$_POST['acf']` — swap in this booking's slice.
            $_POST['acf'] = $values;
            acf_save_post('yith_booking_' . $booking_id);
            $handled = true;
        }

        if (! $handled) {
            return;
        }

        if ($backup === null) {
            unset($_POST['acf']);
        } else {
            $_POST['acf'] = $backup;
        }
    }

    public static function hideBookingFieldGroups(): void
    {
        remove_meta_box('acf-group_lumiboo_booking_premium', 'yith_booking', 'normal');
        remove_meta_box('acf-group_lumiboo_booking_studio', 'yith_booking', 'normal');
    }

    public static function addMetabox($screen_or_post_type, $post_or_order = null): void
    {
        $order = self::asOrder($post_or_order);
        if (! $order instanceof \WC_Order) {

            if (
                $screen_or_post_type !== 'shop_order'
                && $screen_or_post_type !== self::hposScreenId()
            ) {
                return;
            }
        }

        $screens = array_unique(array_filter([
            self::hposScreenId(),
            'shop_order',
        ]));

        foreach ($screens as $screen) {
            add_meta_box(
                'lumiboo_order_bookings',
                __('Lumiboo — Réservations', 'theme_base'),
                [self::class, 'renderMetabox'],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public static function renderMetabox($post_or_order): void
    {
        $order = self::asOrder($post_or_order);

        if (! $order instanceof \WC_Order) {
            echo '<p>' . esc_html__('Commande introuvable.', 'theme_base') . '</p>';
            return;
        }

        $bookings = self::bookingsFor($order);

        if (! $bookings) {
            echo '<p>' . esc_html__('Aucune réservation liée à cette commande.', 'theme_base') . '</p>';
            return;
        }

        if (function_exists('acf_enqueue_scripts')) {
            acf_enqueue_scripts(['uploader' => true]);
        }

        echo '<div class="lumiboo-order-bookings">';

        foreach ($bookings as $booking) {
            self::renderBookingBlock($booking);
        }

        echo '</div>';

        self::printInlineStyles();
    }

    private static function renderBookingBlock(\YITH_WCBK_Booking $booking): void
    {
        $booking_id = (int) $booking->get_id();
        $family     = Family::forBooking($booking);

        $product_id   = (int) $booking->get_product_id();
        $product      = $product_id > 0 ? wc_get_product($product_id) : null;
        $product_name = $product instanceof \WC_Product ? $product->get_name() : '—';

        $resource_names = [];
        foreach ((array) $booking->get_resource_ids() as $rid) {
            $post = get_post((int) $rid);
            if ($post instanceof \WP_Post) {
                $resource_names[] = $post->post_title;
            }
        }
        $resource_label = $resource_names ? implode(', ', $resource_names) : '—';

        $from = (int) $booking->get_from();
        $to   = (int) $booking->get_to();
        $fmt  = trim((string) get_option('date_format') . ' ' . (string) get_option('time_format'));
        $from_str = $from > 0 ? wp_date($fmt, $from) : '—';
        $to_str   = $to   > 0 ? wp_date($fmt, $to)   : '—';

        $booking_title = get_the_title($booking_id);
        if ($booking_title === '') {
            $booking_title = sprintf(
                /* translators: %d: booking ID. */
                __('Réservation #%d', 'theme_base'),
                $booking_id
            );
        }

        $family_label = $family === 'studio'
            ? __('Studio', 'theme_base')
            : ($family === 'premium'
                ? __('Premium', 'theme_base')
                : __('Famille inconnue', 'theme_base'));

        $edit_booking_url = admin_url('post.php?post=' . $booking_id . '&action=edit');

?>
        <section class="lumiboo-booking-block" data-booking-id="<?php echo esc_attr((string) $booking_id); ?>">
            <header class="lumiboo-booking-block__header">
                <div class="lumiboo-booking-block__title">
                    <h3><?php echo esc_html($booking_title); ?></h3>
                    <span class="lumiboo-booking-block__family lumiboo-booking-block__family--<?php echo esc_attr($family ?? 'unknown'); ?>">
                        <?php echo esc_html($family_label); ?>
                    </span>
                </div>
                <a class="button button-secondary" href="<?php echo esc_url($edit_booking_url); ?>" target="_blank" rel="noopener">
                    <?php esc_html_e('Ouvrir la fiche booking', 'theme_base'); ?>
                </a>
            </header>

            <dl class="lumiboo-booking-block__meta">
                <div>
                    <dt><?php esc_html_e('Produit', 'theme_base'); ?></dt>
                    <dd><?php echo esc_html($product_name); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Ressource', 'theme_base'); ?></dt>
                    <dd><?php echo esc_html($resource_label); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Dates', 'theme_base'); ?></dt>
                    <dd><?php echo esc_html($from_str); ?> → <?php echo esc_html($to_str); ?></dd>
                </div>
            </dl>

            <?php self::renderAcfSlot($booking_id, $family); ?>
        </section>
<?php
    }

    /**
     * Render this booking's ACF field group inline, with per-booking input
     * names (`acf_booking_{id}[field_key]`) so multiple booking blocks on
     * the same page never fight over `$_POST['acf']`.
     */
    private static function renderAcfSlot(int $booking_id, ?string $family): void
    {
        if (! function_exists('acf_get_fields') || ! function_exists('acf_render_fields')) {
            echo '<p>' . esc_html__('ACF PRO est requis pour éditer la personnalisation en ligne. Utilisez le lien « Ouvrir la fiche booking » ci-dessus.', 'theme_base') . '</p>';
            return;
        }

        if ($family === null) {
            echo '<p class="notice notice-warning inline"><span>';
            esc_html_e('Ressource inconnue : impossible de déterminer la famille (Premium ou Studio). Ouvrez la fiche booking pour corriger la ressource.', 'theme_base');
            echo '</span></p>';
            return;
        }

        $group_key        = 'group_lumiboo_booking_' . $family;
        $locked_field_key = 'field_lumiboo_locked_by_admin_' . $family;

        $fields = (array) acf_get_fields($group_key);

        if (! $fields) {
            echo '<p>' . esc_html__('Aucun champ ACF trouvé pour cette famille.', 'theme_base') . '</p>';
            return;
        }

        // Bubble the "Verrouillé par l'administration" toggle to the top.
        usort($fields, static function ($a, $b) use ($locked_field_key) {
            $a_locked = (string) ($a['key'] ?? '') === $locked_field_key;
            $b_locked = (string) ($b['key'] ?? '') === $locked_field_key;
            if ($a_locked === $b_locked) {
                return 0;
            }
            return $a_locked ? -1 : 1;
        });

        // Rewrite input names from `acf[...]` to `acf_booking_{id}[...]`.
        acf_prefix_fields($fields, 'acf_booking_' . $booking_id);

        // ACF's JS needs the form data (post_id, screen) to init conditional
        // logic and value loading. `acf-form` class scopes ACF's DOM lookups
        // to this block even though there is no real <form>.
        echo '<div class="acf-form lumiboo-booking-block__slot" data-lumiboo-booking="' . esc_attr((string) $booking_id) . '">';

        acf_form_data([
            'screen'  => 'acf_form',
            'post_id' => 'yith_booking_' . $booking_id,
        ]);

        echo '<div class="acf-fields">';
        acf_render_fields($fields, 'yith_booking_' . $booking_id, 'div', 'label');
        echo '</div>';

        echo '</div>';
    }

    private static function printInlineStyles(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
?>
        <style>
            .lumiboo-order-bookings {
                display: flex;
                flex-direction: column;
                gap: 1.5em;
            }

            .lumiboo-booking-block {
                padding: 1em 1.25em;
                border: 1px solid #dcdcde;
                background: #fbfbfc;
                border-radius: 4px;
            }

            .lumiboo-booking-block__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1em;
                margin-bottom: .75em;
            }

            .lumiboo-booking-block__title {
                display: flex;
                align-items: baseline;
                gap: .5em;
                flex-wrap: wrap;
            }

            .lumiboo-booking-block__title h3 {
                margin: 0;
                font-size: 15px;
            }

            .lumiboo-booking-block__family {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .04em;
                padding: 2px 8px;
                border-radius: 999px;
                background: #eef0f5;
                color: #1d2327;
            }

            .lumiboo-booking-block__family--studio {
                background: #e6f4ea;
                color: #14532d;
            }

            .lumiboo-booking-block__family--premium {
                background: #e6efff;
                color: #1e3a8a;
            }

            .lumiboo-booking-block__family--unknown {
                background: #fef3c7;
                color: #78350f;
            }

            .lumiboo-booking-block__meta {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: .5em 1em;
                margin: 0 0 1em;
                padding: .5em .75em;
                background: #fff;
                border: 1px solid #eaeaef;
                border-radius: 3px;
            }

            .lumiboo-booking-block__meta div {
                margin: 0;
            }

            .lumiboo-booking-block__meta dt {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #50575e;
                margin: 0;
            }

            .lumiboo-booking-block__meta dd {
                margin: 2px 0 0;
                font-weight: 500;
                color: #1d2327;
            }

            .lumiboo-booking-block>.notice.inline {
                display: block;
                margin: 0 0 1em;
            }

            .lumiboo-booking-block__slot .acf-field[data-name="lumiboo_locked_by_admin"] {
                padding: .75em 1em;
                margin: 0 0 1em;
                background: #fffbea;
                border: 1px solid #f0c674;
                border-radius: 3px;
            }

            .lumiboo-booking-block__slot .acf-field[data-name="lumiboo_locked_by_admin"] .acf-label label {
                font-weight: 700;
                color: #78350f;
            }
        </style>
<?php
    }

    private static function asOrder($post_or_order): ?\WC_Order
    {
        if ($post_or_order instanceof \WC_Order) {
            return $post_or_order;
        }
        if ($post_or_order instanceof \WP_Post) {
            $maybe = function_exists('wc_get_order') ? wc_get_order($post_or_order->ID) : null;
            return $maybe instanceof \WC_Order ? $maybe : null;
        }
        return null;
    }

    private static function bookingsFor(\WC_Order $order): array
    {
        if (! function_exists('yith_wcbk')) {
            return [];
        }

        $yith = yith_wcbk();

        if (! isset($yith->booking_helper) || ! method_exists($yith->booking_helper, 'get_bookings_by_order')) {
            return [];
        }

        $bookings = (array) $yith->booking_helper->get_bookings_by_order($order->get_id());
        $out = [];

        foreach ($bookings as $booking) {
            if ($booking instanceof \YITH_WCBK_Booking) {
                $out[] = $booking;
            }
        }

        return $out;
    }

    private static function hposScreenId(): string
    {
        if (function_exists('wc_get_page_screen_id')) {
            $screen = (string) wc_get_page_screen_id('shop-order');
            if ($screen !== '') {
                return $screen;
            }
        }
        return 'woocommerce_page_wc-orders';
    }
}
