<?php

namespace Theme_base;

/**
 * Modèle de lecture d'un dossier Lumiboo.
 *
 * Une commande WooCommerce = un dossier = une carte du compte (G02).
 * Cette classe consolide en un seul endroit tout ce que les écrans
 * doivent afficher : produit, quantité, événement, option Studio,
 * personnalisation, liens post-événement.
 *
 * Elle est en LECTURE SEULE : elle ne stocke rien. Les données viennent
 * des sources natives (WooCommerce, YITH Booking via `Booking`, ACF) et
 * les valeurs figées sur la commande (option Studio) sont posées ailleurs.
 *
 * Contrats à connaître :
 *
 * - `fromOrder()` retourne `null` si la commande n'est pas un dossier
 *   Lumiboo (aucun produit YITH Booking détecté).
 * - `findForCurrentUser()` est le SEUL accès autorisé côté public :
 *   il vérifie la propriété et l'éligibilité à chaque appel — connaître
 *   un identifiant ne suffit jamais.
 * - Visibilité (`eligibleStatuses`) ≠ éditabilité (`isPaid`).
 *   `on-hold` (virement) peut apparaître au dashboard ; la
 *   personnalisation (chunks 6–7) ne s'ouvre que si `isPaid()`.
 * - Les statuts visibles par défaut (`processing`, `completed`) sont
 *   filtrables via `lumiboo_dossier_eligible_statuses` (blocage B01).
 */
class Dossier
{
    /** Statuts visibles au compte (surchargeables, ex. `on-hold`). */
    private const ELIGIBLE_STATUSES = ['processing', 'completed'];

    /** Statuts qui autorisent la personnalisation côté client. */
    private const PAID_STATUSES = ['processing', 'completed'];

    /**
     * Construit un dossier depuis une commande WooCommerce.
     *
     * @return array<string, mixed>|null `null` si ce n'est pas un dossier Lumiboo.
     */
    public static function fromOrder(\WC_Order $order): ?array
    {
        [$product_key, $quantity, $title] = self::detectProduct($order);

        if ($product_key === null) {
            return null;
        }

        $pid = 'woo_order_' . $order->get_id();

        return [
            'id'                => (string) $order->get_id(),
            'order'             => $order,
            'reference'         => (string) $order->get_order_number(),
            'product'           => $product_key,
            'title'             => $title,
            'quantity'          => $quantity,
            'event_date'        => Booking::eventDate($order),
            'return_date'       => Booking::returnDate($order),
            'paid'              => self::isPaid($order),
            'has_studio_option' => (bool) get_field('lumiboo_has_studio_option', $pid),
            'welcome_mode'      => (string) (get_field('lumiboo_welcome_mode', $pid) ?: 'not_answered'),
            'welcome'           => (int) (get_field('lumiboo_welcome', $pid) ?: 0),
            'rear_mode'         => (string) (get_field('lumiboo_rear_mode', $pid) ?: 'not_answered'),
            'rear'              => (int) (get_field('lumiboo_rear', $pid) ?: 0),
            'prints'            => (string) (get_field('lumiboo_prints', $pid) ?: ''),
            'shipping_mode'     => (string) (get_field('lumiboo_shipping_mode', $pid) ?: ''),
            'location'          => (string) (get_field('lumiboo_location', $pid) ?: ''),
            'units'             => self::readUnits($pid, $quantity),
            'gallery_url'       => (string) (get_field('lumiboo_gallery_url', $pid) ?: ''),
            'invoice_url'       => (string) (get_field('lumiboo_invoice_url', $pid) ?: ''),
            'emails_url'        => (string) (get_field('lumiboo_emails_url', $pid) ?: ''),
            'report_url'        => (string) (get_field('lumiboo_report_url', $pid) ?: ''),
        ];
    }

    /**
     * Recherche un dossier appartenant au client connecté.
     *
     * Vérifie la propriété et l'éligibilité de la commande. Retourne
     * `null` sans révéler la cause : commande inexistante, appartenant
     * à un autre client, ou dont le statut n'ouvre pas d'accès (§10.2).
     */
    public static function findForCurrentUser(string $id): ?array
    {
        if ($id === '' || ! is_user_logged_in()) {
            return null;
        }

        $order = wc_get_order((int) $id);

        if (! $order instanceof \WC_Order) {
            return null;
        }

        if ((int) $order->get_user_id() !== get_current_user_id()) {
            return null;
        }

        if (! in_array($order->get_status(), self::eligibleStatuses(), true)) {
            return null;
        }

        return self::fromOrder($order);
    }

    /**
     * Tous les dossiers du client connecté (non triés, non partitionnés).
     * Le tri en cours/passé viendra avec le moteur de deadline (étape 7).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function dossiersForCurrentUser(): array
    {
        $user_id = get_current_user_id();

        if (! $user_id) {
            return [];
        }

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => self::eligibleStatuses(),
        ]);

        $dossiers = [];

        foreach ($orders as $order) {
            if (! $order instanceof \WC_Order) {
                continue;
            }

            $dossier = self::fromOrder($order);

            if ($dossier !== null) {
                $dossiers[] = $dossier;
            }
        }

        return $dossiers;
    }

    /**
     * @return array<int, string>
     */
    public static function eligibleStatuses(): array
    {
        return (array) apply_filters(
            'lumiboo_dossier_eligible_statuses',
            self::ELIGIBLE_STATUSES
        );
    }

    /**
     * Le client peut-il modifier la personnalisation ?
     *
     * Distinct de la visibilité : un virement en `on-hold` a droit à
     * une carte en lecture seule ; le CTA / formulaire perso (chunks
     * 6–7) ne s'active que sur `processing` / `completed`.
     */
    public static function isPaid(\WC_Order $order): bool
    {
        return in_array($order->get_status(), self::paidStatuses(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function paidStatuses(): array
    {
        return (array) apply_filters('lumiboo_dossier_paid_statuses', self::PAID_STATUSES);
    }

    /**
     * Identifie le produit Lumiboo de la commande.
     *
     * Repère la première ligne dont le produit est de type YITH Booking
     * (les fiches Premium et Studio étant les seuls produits réservables
     * du site) et déduit la famille par présence de « studio » dans le
     * nom. Le nom du produit est stable, contrairement à l'ID, qui
     * change si la fiche est recréée.
     *
     * @return array{0: 'premium'|'studio'|null, 1: int, 2: string}
     */
    private static function detectProduct(\WC_Order $order): array
    {
        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();

            if (! $product instanceof \WC_Product || $product->get_type() !== 'booking') {
                continue;
            }

            $name = (string) $product->get_name();
            $key  = stripos($name, 'studio') !== false ? 'studio' : 'premium';
            $quantity = $key === 'premium' ? Booking::machines($order) : 1;

            return [$key, $quantity, $name];
        }

        return [null, 0, ''];
    }

    /**
     * Lit les unités de personnalisation depuis le repeater ACF
     * `lumiboo_units` et aligne systématiquement leur nombre sur la
     * quantité commandée. Une unité vide reçoit les défauts tri-état.
     *
     * Cet alignement garantit qu'un dossier expose toujours autant
     * d'unités que le client a commandé de Lumiboo, même si le repeater
     * n'a jamais été sauvegardé ou si la quantité a été modifiée après
     * coup.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function readUnits(string $pid, int $quantity): array
    {
        $rows  = (array) (get_field('lumiboo_units', $pid) ?: []);
        $units = [];

        foreach ($rows as $row) {
            $units[] = self::normaliseUnit((array) $row);
        }

        while (count($units) < $quantity) {
            $units[] = self::normaliseUnit([]);
        }

        if (count($units) > $quantity && $quantity > 0) {
            $units = array_slice($units, 0, $quantity);
        }

        return $units;
    }

    /**
     * Ramène une ligne de repeater à des types prévisibles, avec les
     * défauts tri-état pour les modes non renseignés.
     *
     * Les sous-champs ACF gardent le préfixe `lumiboo_` : ce sont les
     * clés reçues du repeater.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function normaliseUnit(array $row): array
    {
        return [
            'template'     => (string) ($row['lumiboo_template'] ?? ''),
            'logo_mode'    => (string) ($row['lumiboo_logo_mode'] ?? 'not_answered'),
            'logo'         => (int) ($row['lumiboo_logo'] ?? 0),
            'text_mode'    => (string) ($row['lumiboo_text_mode'] ?? 'not_answered'),
            'text'         => (string) ($row['lumiboo_text'] ?? ''),
            'requests'     => (string) ($row['lumiboo_requests'] ?? ''),
        ];
    }
}
