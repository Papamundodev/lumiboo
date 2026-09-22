<?php

namespace Theme_base;

/**
 * Détection et snapshot du choix d'impressions Premium (400 / 800 /
 * illimité).
 *
 * SEUL fichier autorisé à lire l'add-on WAPO des impressions
 * (`addon_id == 1` par défaut). Tout le reste passe par le champ ACF
 * `lumiboo_prints` de la commande.
 *
 * Rôle :
 *
 * - au checkout, lire la sélection WAPO d'impressions et figer le
 *   choix sur la commande via ACF (snapshot G20) ;
 * - pour les commandes créées manuellement (hors checkout) ou pour un
 *   client Premium qui n'a rien sélectionné (tarif de base 400
 *   impressions, G05), l'admin coche/décoche `lumiboo_prints` dans le
 *   back-office et cette classe n'écrit rien.
 *
 * L'identifiant d'add-on WAPO à surveiller est filtrable via
 * `lumiboo_impressions_wapo_addon_ids` : si Lumiboo régénère l'add-on,
 * il suffit de mettre à jour ce filtre — aucun autre code ne référence
 * WAPO.
 */
class Impressions
{
    /**
     * Identifiants d'add-ons WAPO considérés comme « choix
     * d'impressions Premium » (un seul aujourd'hui : id 1).
     *
     * @var array<int, int>
     */
    private const DEFAULT_ADDON_IDS = [1];

    public static function register(): void
    {
        // Fire once, à la validation du panier. Une correction manuelle
        // par l'admin dans le champ ACF n'est jamais écrasée : ce hook
        // ne se déclenche plus après la création de la commande.
        add_action('woocommerce_checkout_order_processed', [self::class, 'snapshot'], 20, 1);
    }

    /**
     * Écrit le choix d'impressions sur la commande si l'add-on WAPO a
     * été sélectionné. Sinon, ne fait rien : l'ACF reste à sa valeur
     * courante et l'admin peut la fixer à la main.
     */
    public static function snapshot(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order) {
            return;
        }

        $tier = self::detectTier($order);

        if ($tier === null) {
            return;
        }

        update_field('lumiboo_prints', $tier, 'woo_order_' . $order->get_id());
    }

    /**
     * Retourne la tranche d'impressions détectée dans les add-ons WAPO
     * de la commande, ou `null` si aucun add-on d'impressions n'a été
     * sélectionné.
     *
     * Valeurs possibles : `'400'`, `'800'`, `'unlimited'`.
     */
    public static function detectTier(\WC_Order $order): ?string
    {
        $addon_ids = array_map('intval', (array) apply_filters(
            'lumiboo_impressions_wapo_addon_ids',
            self::DEFAULT_ADDON_IDS
        ));

        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $meta = $item->get_meta('_ywapo_meta_data');

            if (! is_array($meta)) {
                continue;
            }

            foreach ($meta as $group) {
                if (! is_array($group)) {
                    continue;
                }

                foreach ($group as $addon) {
                    if (! is_array($addon)) {
                        continue;
                    }

                    $id = (int) ($addon['addon_id'] ?? 0);

                    if ($id === 0 || ! in_array($id, $addon_ids, true)) {
                        continue;
                    }

                    $tier = self::valueToTier((string) ($addon['addon_value'] ?? ''));

                    if ($tier !== null) {
                        return $tier;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Traduit le libellé français de l'add-on WAPO en clé stable ACF.
     *
     * Utilise des sous-chaînes plutôt que des libellés exacts pour
     * survivre à un changement de wording (« Jusqu'à 400 impressions »
     * → « 400 impressions ») ou d'apostrophe (droite/typographique).
     */
    private static function valueToTier(string $value): ?string
    {
        $needle = mb_strtolower($value);

        // Ordre significatif : chercher les nombres avant « illimit »
        // au cas où un futur libellé combinerait les deux.
        if (str_contains($needle, '800')) {
            return '800';
        }

        if (str_contains($needle, '400')) {
            return '400';
        }

        if (str_contains($needle, 'illimit')) {
            return 'unlimited';
        }

        return null;
    }
}
