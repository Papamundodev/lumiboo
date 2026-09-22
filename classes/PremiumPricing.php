<?php

namespace Theme_base;

/**
 * Multiplie le prix des add-ons WAPO par le nombre de Lumiboo réservés.
 *
 * Deux plugins produisent le prix d'une ligne Premium :
 *
 * - YITH Booking multiplie le prix de base par le « person type » Lumiboo
 *   (599 CHF × 2 = 1 198 CHF pour 2 machines) ;
 * - WAPO ajoute UNE fois le prix de l'add-on d'impressions (+100 CHF),
 *   quelle que soit la quantité de Lumiboo choisie.
 *
 * Sans intervention, deux Lumiboo à 800 impressions coûtent
 * 1 198 + 100 = 1 298 CHF au lieu de 1 198 + 200 = 1 398 CHF.
 *
 * Cette classe ajoute la différence manquante (add-on × (persons − 1))
 * une seule fois par ligne de panier, uniquement pour les produits
 * Premium (Studio reste toujours à quantité 1).
 *
 * Portée volontairement restreinte : SEUL ce fichier avec `Impressions`,
 * `Studio` et `Booking` connaît la mécanique interne des plugins tiers.
 */
class PremiumPricing
{
    /**
     * Clés de panier déjà ajustées pendant la requête courante.
     *
     * @var array<string, bool>
     */
    private static array $boosted = [];

    public static function register(): void
    {
        // Priorité 25 : après WAPO (20). Suffisamment tard pour que
        // `yith_wapo_total_options_price` soit renseigné sur la ligne.
        add_action('woocommerce_before_calculate_totals', [self::class, 'recalculate'], 25, 1);
    }

    public static function recalculate(\WC_Cart $cart): void
    {
        if ($cart->is_empty()) {
            return;
        }

        foreach ($cart->get_cart() as $key => $cart_item) {
            if (isset(self::$boosted[$key])) {
                continue;
            }

            $product = $cart_item['data'] ?? null;

            if (! $product instanceof \WC_Product) {
                continue;
            }

            // Studio reste à quantité 1 par règle métier : le calcul WAPO
            // par défaut est déjà correct.
            if (stripos((string) $product->get_name(), 'studio') !== false) {
                continue;
            }

            $persons = self::personsFor($cart_item);

            if ($persons <= 1) {
                continue;
            }

            $addons = (float) ($cart_item['yith_wapo_total_options_price'] ?? 0);

            if ($addons <= 0) {
                continue;
            }

            $extra = $addons * ($persons - 1);
            $product->set_price((float) $product->get_price() + $extra);

            self::$boosted[$key] = true;
        }
    }

    /**
     * Nombre de Lumiboo réservés sur la ligne de panier.
     *
     * YITH Booking le stocke sous `yith_booking_data.persons` quand le
     * produit utilise le mode « people » simple (« Enable people
     * option »). Absent ou 0 vaut 1 par défaut.
     *
     * @param array<string, mixed> $cart_item
     */
    private static function personsFor(array $cart_item): int
    {
        $booking = $cart_item['yith_booking_data'] ?? null;

        if (! is_array($booking)) {
            return 1;
        }

        $persons = (int) ($booking['persons'] ?? 0);

        return max(1, $persons);
    }
}
