<?php

namespace Theme_base;

/**
 * Parcours de réservation : fiche produit → checkout → confirmation.
 *
 * Le panier transporte la configuration choisie sur la fiche jusqu'à la
 * commande. Deux règles du parcours sont optionnelles et s'activent depuis
 * functions.php : une machine par commande (G02, enforceSingleProductPerOrder)
 * et l'absence de page panier (R01, disableCartPage).
 *
 * Tout passe par des filtres WooCommerce, sans surcharge de template : la
 * disposition en deux colonnes du wireframe est obtenue en CSS, et les champs
 * de facturation sont pilotés depuis YITH Checkout Manager.
 */
class Checkout
{
    public function register(): void
    {
        // Les renseignements de livraison sont saisis sur la fiche produit,
        // avant le checkout (G13) : celui-ci ne recueille que la facturation.
        add_filter('woocommerce_cart_needs_shipping_address', '__return_false');
        add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
    }

    /* ---------------------------------------------------------------------
     * Une commande, une machine
     * ------------------------------------------------------------------ */

    /**
     * Une commande = une machine (G02) :
     *   - quantité verrouillée à 1 sur tous les produits
     *   - un nouvel add-to-cart remplace la sélection précédente
     *
     * Ne pas appeler cette méthode = comportement WooCommerce standard
     * (plusieurs produits, quantités libres).
     */
    public function enforceSingleProductPerOrder(): void
    {
        add_filter('woocommerce_is_sold_individually', [$this, 'forceSingleQuantity'], 10, 2);
        add_action('woocommerce_add_to_cart', [$this, 'keepLastSelection'], 20);
    }

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

    /**
     * Supprime la page panier du parcours (R01) :
     *   - add-to-cart → redirection directe vers le checkout
     *   - /cart/ ferme et renvoie vers checkout ou boutique
     *   - message « ajouté au panier » masqué
     *   - icône panier du header pointe vers le checkout
     *
     * Ne pas appeler cette méthode = comportement WooCommerce standard.
     */
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
}
