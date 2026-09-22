<?php

namespace Theme_base;

/**
 * Intégration YITH Booking : point d'entrée unique.
 *
 * SEUL fichier du thème autorisé à référencer `yith_wcbk`,
 * `yith_get_booking` ou toute API YITH. Tout le reste du code passe par
 * `Booking::eventDate($order)` / `Booking::returnDate($order)`.
 *
 * Retourne toujours des `DateTimeImmutable` dans le fuseau WordPress
 * (`wp_timezone()`), ou `null` si aucune réservation valable n'est
 * rattachée à la commande. Le fuseau est celui réglé dans WordPress —
 * jamais une constante hardcodée (H02).
 */
class Booking
{
    /**
     * Date de début de l'événement enregistrée par YITH sur la commande.
     */
    public static function eventDate(\WC_Order $order): ?\DateTimeImmutable
    {
        $booking = self::firstBookingFor($order);

        return $booking !== null ? self::timestampToDate((int) $booking->get_from()) : null;
    }

    /**
     * Date de fin (retour) enregistrée par YITH sur la commande.
     */
    public static function returnDate(\WC_Order $order): ?\DateTimeImmutable
    {
        $booking = self::firstBookingFor($order);

        return $booking !== null ? self::timestampToDate((int) $booking->get_to()) : null;
    }

    /**
     * Première réservation liée à la commande, ou `null` si le plugin
     * n'est pas actif ou si aucune réservation n'est rattachée. Une
     * commande Lumiboo correspond à une seule réservation (G02).
     */
    private static function firstBookingFor(\WC_Order $order): ?object
    {
        if (! function_exists('yith_wcbk')) {
            return null;
        }

        $yith = yith_wcbk();

        if (! isset($yith->booking_helper) || ! method_exists($yith->booking_helper, 'get_bookings_by_order')) {
            return null;
        }

        $bookings = $yith->booking_helper->get_bookings_by_order($order->get_id());

        foreach ((array) $bookings as $booking) {
            if (is_object($booking) && method_exists($booking, 'get_from') && (int) $booking->get_from() > 0) {
                return $booking;
            }
        }

        return null;
    }

    /**
     * Nombre de machines Premium.
     *
     * YITH le range dans la meta de ligne `yith_booking_data.persons`.
     * La quantité WooCommerce reste 1 (une commande = une réservation).
     * Studio n'a pas cette valeur : 1.
     */
    public static function machines(\WC_Order $order): int
    {
        foreach ($order->get_items() as $item) {
            if (! $item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();

            if (! $product instanceof \WC_Product || stripos((string) $product->get_name(), 'studio') !== false) {
                continue;
            }

            $data = $item->get_meta('yith_booking_data');

            if (! is_array($data)) {
                continue;
            }

            $persons = (int) ($data['persons'] ?? 0);

            if ($persons > 0) {
                return $persons;
            }
        }

        return 1;
    }

    /**
     * Convertit un timestamp Unix YITH en `DateTimeImmutable` dans le
     * fuseau WordPress. Retourne `null` si le timestamp est vide.
     */
    private static function timestampToDate(int $timestamp): ?\DateTimeImmutable
    {
        if ($timestamp <= 0) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone());
    }
}
