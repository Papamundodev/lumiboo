<?php

namespace Theme_base;

class Account
{

    private const PASSWORD_PENDING = '_lumiboo_password_pending';

    public static function register(): void
    {

        add_filter('woocommerce_checkout_registration_required', '__return_true');
        add_filter('woocommerce_checkout_registration_enabled', '__return_true');
        add_filter(
            'option_woocommerce_registration_generate_password',
            [self::class, 'forceGeneratedPassword']
        );

        add_action(
            'woocommerce_checkout_order_processed',
            [self::class, 'ensureCustomerAccount'],
            10,
            3
        );

        add_action('woocommerce_created_customer', [self::class, 'flagPasswordPending'], 10, 3);
        add_action('after_password_reset', [self::class, 'clearPasswordPending']);

        add_action('woocommerce_thankyou', [self::class, 'renderOrderReceived'], 5);

        add_filter('woocommerce_account_menu_items', [self::class, 'filterMenuItems']);
    }

    public static function forceGeneratedPassword($value): string
    {
        return 'yes';
    }

    public static function ensureCustomerAccount($order_id, $posted_data = [], $order = null): void
    {
        $order = $order instanceof \WC_Order ? $order : wc_get_order($order_id);

        if (! $order instanceof \WC_Order || $order->get_user_id() > 0) {
            return;
        }

        $email = $order->get_billing_email();

        if (! is_email($email) || email_exists($email)) {
            return;
        }

        $customer_id = wc_create_new_customer($email, '', '', [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        ]);

        if (is_wp_error($customer_id)) {
            return;
        }

        $order->set_customer_id($customer_id);
        $order->save();

        $customer = new \WC_Customer($customer_id);
        $customer->set_props($order->get_address('billing'));
        $customer->save();
    }

    public static function flagPasswordPending($customer_id, $new_customer_data = [], $password_generated = false): void
    {
        if ($password_generated) {
            update_user_meta((int) $customer_id, self::PASSWORD_PENDING, '1');
        }
    }

    public static function clearPasswordPending($user): void
    {
        if ($user instanceof \WP_User) {
            delete_user_meta($user->ID, self::PASSWORD_PENDING);
        }
    }

    public static function renderOrderReceived($order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order || $order->has_status('failed')) {
            return;
        }

        $owner_id = (int) $order->get_user_id();

        get_template_part('partials/account/order-received', null, [
            'order'            => $order,
            'is_owner'         => is_user_logged_in() && $owner_id > 0 && $owner_id === get_current_user_id(),
            'has_account'      => $owner_id > 0,
            'password_pending' => $owner_id > 0
                && get_user_meta($owner_id, self::PASSWORD_PENDING, true) === '1',
        ]);
    }

    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('lumiboo_account_' . $key, 'option');

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    public static function filterMenuItems(array $items): array
    {
        if (isset($items['dashboard'])) {
            $items['dashboard'] = self::text('menu_dashboard', 'Mon espace Lumiboo');
        }

        return $items;
    }

    public static function shippingLabel(array $dossier): string
    {
        switch ((string) ($dossier['shipping_mode'] ?? '')) {
            case 'pickup':
                return self::text('shipping_pickup', 'Retrait');
            case 'delivery':
                return self::text('shipping_delivery', 'Livraison clé en main');
            case 'dhl':
                return self::text('shipping_dhl', 'DHL');
            default:
                return '';
        }
    }
}
