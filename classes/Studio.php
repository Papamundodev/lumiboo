<?php

namespace Theme_base;

class Studio
{

    private const DEFAULT_ADDON_IDS = [2];

    public static function register(): void
    {
        add_action('yith_wcbk_new_booking', [self::class, 'snapshot'], 20, 2);
    }

    public static function snapshot(int $booking_id, \YITH_WCBK_Booking $booking): void
    {
        if ($booking_id <= 0 || Family::forBooking($booking) !== 'studio') {
            return;
        }

        update_field(
            'lumiboo_has_studio_option',
            self::hasOption($booking) ? 1 : 0,
            'yith_booking_' . $booking_id
        );
    }

    public static function hasOption(\YITH_WCBK_Booking $booking): bool
    {
        if (Family::forBooking($booking) !== 'studio') {
            return false;
        }

        $item = self::orderItem($booking);

        if ($item === null) {
            return false;
        }

        $addon_ids = array_map('intval', (array) apply_filters(
            'lumiboo_studio_wapo_addon_ids',
            self::DEFAULT_ADDON_IDS
        ));

        $meta = $item->get_meta('_ywapo_meta_data');

        if (! is_array($meta)) {
            return false;
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

        return false;
    }

    private static function orderItem(\YITH_WCBK_Booking $booking): ?\WC_Order_Item_Product
    {
        $item_id = (int) $booking->get_order_item_id();

        if ($item_id <= 0) {
            return null;
        }

        $order = wc_get_order((int) $booking->get_order_id());

        if (! $order instanceof \WC_Order) {
            return null;
        }

        $item = $order->get_item($item_id);

        return $item instanceof \WC_Order_Item_Product ? $item : null;
    }
}
