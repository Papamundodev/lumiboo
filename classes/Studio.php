<?php

namespace Theme_base;

/**
 * Détection de l'option payante Studio (+150 CHF).
 *
 * SEUL fichier autorisé à lire les données internes YITH WAPO
 * (`_ywapo_meta_data`). Tout le reste passe par le champ ACF
 * `lumiboo_has_studio_option` de la commande.
 *
 * Rôle :
 *
 * - au checkout, lire la sélection WAPO et figer le choix sur la
 *   commande via ACF (snapshot G20 : la commande porte ce qu'elle a
 *   réellement acheté, indépendamment des évolutions futures du
 *   produit) ;
 * - pour les commandes créées manuellement (hors checkout), l'admin
 *   coche/décoche `lumiboo_has_studio_option` dans le back-office et
 *   cette classe n'intervient pas.
 *
 * L'identifiant d'add-on WAPO à surveiller est filtrable via
 * `lumiboo_studio_wapo_addon_ids` : si Lumiboo régénère l'option, il
 * suffit de mettre à jour ce filtre — aucun autre code ne référence
 * WAPO.
 */
class Studio
{
    /**
     * Identifiants d'add-ons WAPO considérés comme « option Studio ».
     * Un seul add-on aujourd'hui (id 2 : « Option préférée des
     * entreprises (+150 CHF) »).
     *
     * @var array<int, int>
     */
    private const DEFAULT_ADDON_IDS = [2];

    public static function register(): void
    {
        // Fire once, à la validation du panier. Une écriture ultérieure
        // par l'admin dans la case ACF n'est jamais écrasée : ce hook
        // n'est plus déclenché après la création de la commande.
        add_action('woocommerce_checkout_order_processed', [self::class, 'snapshot'], 20, 1);
    }

    /**
     * Fige l'état d'achat de l'option sur la commande.
     */
    public static function snapshot(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order) {
            return;
        }

        update_field(
            'lumiboo_has_studio_option',
            self::hasOption($order) ? 1 : 0,
            'woo_order_' . $order->get_id()
        );
    }

    /**
     * Retourne vrai si l'une des lignes Studio de la commande porte un
     * add-on WAPO listé dans `lumiboo_studio_wapo_addon_ids`.
     *
     * Double-vérification : on n'accepte l'add-on que s'il est porté par
     * un produit Studio (nom contenant « studio »). Empêche une
     * mauvaise configuration WAPO (add-on attaché à Premium par erreur)
     * de figer à tort l'option sur une commande Premium.
     */
    public static function hasOption(\WC_Order $order): bool
    {
        $addon_ids = array_map('intval', (array) apply_filters(
            'lumiboo_studio_wapo_addon_ids',
            self::DEFAULT_ADDON_IDS
        ));

        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();

            if (! $product instanceof \WC_Product) {
                continue;
            }

            // Garde-fou : l'option Studio n'existe QUE sur un produit Studio.
            if (stripos((string) $product->get_name(), 'studio') === false) {
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

                    if ($id > 0 && in_array($id, $addon_ids, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
