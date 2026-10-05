<?php

namespace Theme_base;

class Checkout
{
    public function register(): void
    {

        add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
        add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
    }

    public function enforceSingleProductPerOrder(): void
    {
        add_filter('woocommerce_is_sold_individually', [$this, 'forceSingleQuantity'], 10, 2);
        add_action('woocommerce_add_to_cart', [$this, 'keepLastSelection'], 20);
    }

    public function forceSingleQuantity($sold_individually, $product = null): bool
    {
        return true;
    }

    public function keepLastSelection(string $cart_item_key): void
    {
        if (! function_exists('WC') || ! WC()->cart) {
            return;
        }

        foreach (array_keys(WC()->cart->get_cart()) as $key) {
            if ($key !== $cart_item_key) {
                WC()->cart->remove_cart_item($key);
            }
        }
    }

    public function disableCartPage(): void
    {
        add_filter('lumiboo_cart_page_disabled', '__return_true');
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirectToCheckout']);
        add_action('template_redirect', [$this, 'closeCartPage']);
        add_filter('wc_add_to_cart_message_html', '__return_empty_string');
    }

    public static function cartPageIsDisabled(): bool
    {
        return (bool) apply_filters('lumiboo_cart_page_disabled', false);
    }

    public function redirectToCheckout($url): string
    {
        return wc_get_checkout_url();
    }

    public function closeCartPage(): void
    {
        if (! function_exists('is_cart') || ! is_cart() || is_ajax()) {
            return;
        }

        $empty = ! WC()->cart || WC()->cart->is_empty();

        wp_safe_redirect($empty ? $this->productsUrl() : wc_get_checkout_url());
        exit;
    }

    public function productsUrl(): string
    {
        $shop = wc_get_page_permalink('shop');

        return (string) apply_filters('lumiboo_products_url', $shop ?: home_url('/'));
    }
}
