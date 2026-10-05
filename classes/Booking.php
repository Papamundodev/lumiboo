<?php

namespace Theme_base;

class Booking
{

    public static function eventDate(\WC_Order $order): ?\DateTimeImmutable
    {
        $booking = self::firstBookingFor($order);

        return $booking !== null ? self::timestampToDate((int) $booking->get_from()) : null;
    }

    public static function returnDate(\WC_Order $order): ?\DateTimeImmutable
    {
        $booking = self::firstBookingFor($order);

        return $booking !== null ? self::timestampToDate((int) $booking->get_to()) : null;
    }

    public static function forPost(int $post_id): ?\YITH_WCBK_Booking
    {
        if ($post_id <= 0 || ! function_exists('yith_get_booking')) {
            return null;
        }

        $booking = yith_get_booking($post_id);

        return $booking instanceof \YITH_WCBK_Booking ? $booking : null;
    }

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

    private static function timestampToDate(int $timestamp): ?\DateTimeImmutable
    {
        if ($timestamp <= 0) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone());
    }
}
