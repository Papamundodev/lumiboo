<?php

namespace Theme_base;

/**
 * Parcours de réservation : fiche produit → checkout → confirmation.
 *
 * Une commande de location = un produit en quantité un = une réservation
 * (règle G02). Le panier reste le mécanisme qui transporte la configuration
 * choisie sur la fiche jusqu'à la commande, mais il n'est jamais présenté au
 * client : aucune page panier intermédiaire n'existe dans le parcours dessiné
 * (recommandation R01).
 *
 * Tout passe par des filtres WooCommerce, sans surcharge de template : la
 * disposition en deux colonnes du wireframe est obtenue en CSS, et les champs
 * de facturation sont pilotés depuis YITH Checkout Manager.
 */
class Checkout
{
    public function register(): void
    {
        // Une seule machine par commande (G02).
        add_filter('woocommerce_is_sold_individually', [$this, 'forceSingleQuantity'], 10, 2);
        add_action('woocommerce_add_to_cart', [$this, 'keepLastSelection'], 20);

        // Le panier n'a pas d'écran : la sélection mène directement au checkout.
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirectToCheckout']);
        add_action('template_redirect', [$this, 'closeCartPage']);

        // Les renseignements de livraison sont saisis sur la fiche produit,
        // avant le checkout (G13) : celui-ci ne recueille que la facturation.
        add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
        add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

        // La case newsletter accompagne les CGV dans le bloc de validation.
        add_action('woocommerce_review_order_before_submit', [$this, 'renderNewsletterField'], 20);
        add_action('woocommerce_checkout_create_order', [$this, 'saveNewsletterChoice'], 10, 2);
    }

    /* ---------------------------------------------------------------------
     * Une commande, une machine
     * ------------------------------------------------------------------ */

    /**
     * Verrouille la quantité à un, sans dépendre du réglage enregistré sur
     * chaque produit : la règle appartient au parcours, pas au catalogue.
     *
     * @param bool $sold_individually
     * @return bool
     */
    public function forceSingleQuantity($sold_individually, $product = null): bool
    {
        return true;
    }

    /**
     * Ne conserve que la sélection qui vient d'être configurée.
     *
     * Revenir sur une fiche, corriger un champ ou configurer l'autre produit
     * ne doit jamais aboutir à une commande à deux machines. La confirmation
     * du remplacement se joue sur la fiche produit, avant l'ajout.
     */
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

    /* ---------------------------------------------------------------------
     * Absence de page panier
     * ------------------------------------------------------------------ */

    public function redirectToCheckout($url): string
    {
        return wc_get_checkout_url();
    }

    /**
     * La page panier reste installée — WooCommerce s'en sert en interne —
     * mais elle n'est plus un écran du parcours : une sélection en cours
     * ouvre le checkout, une absence de sélection renvoie aux produits.
     */
    public function closeCartPage(): void
    {
        if (! function_exists('is_cart') || ! is_cart() || is_ajax()) {
            return;
        }

        $empty = ! WC()->cart || WC()->cart->is_empty();

        wp_safe_redirect($empty ? $this->productsUrl() : wc_get_checkout_url());
        exit;
    }

    /**
     * Destination proposée lorsqu'aucune sélection n'existe.
     */
    public function productsUrl(): string
    {
        $shop = wc_get_page_permalink('shop');

        return (string) apply_filters('lumiboo_products_url', $shop ?: home_url('/'));
    }

    /* ---------------------------------------------------------------------
     * Newsletter
     * ------------------------------------------------------------------ */

    /**
     * Case facultative, décochée par défaut, distincte de l'acceptation des
     * conditions générales (recommandation R24).
     *
     * Le raccordement Brevo est reporté : cocher la case enregistre une
     * intention sur la commande, elle ne vaut pas inscription effectuée.
     */
    public function renderNewsletterField(): void
    {
        woocommerce_form_field('lumiboo_newsletter', [
            'type'          => 'checkbox',
            'class'         => ['form-row', 'lumiboo-newsletter'],
            'label'         => (string) apply_filters(
                'lumiboo_newsletter_label',
                __('Je m’abonne à la newsletter', 'theme_base')
            ),
            'required'      => false,
            'checked_value' => 'yes',
        ], WC()->checkout()->get_value('lumiboo_newsletter'));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveNewsletterChoice(\WC_Order $order, array $data): void
    {
        $subscribed = ! empty($_POST['lumiboo_newsletter']); // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $order->update_meta_data('_lumiboo_newsletter', $subscribed ? 'yes' : 'no');
    }
}
