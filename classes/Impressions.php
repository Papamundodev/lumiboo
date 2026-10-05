<?php

namespace Theme_base;

class Impressions
{

    private const DEFAULT_ADDON_IDS = [1];

    public static function register(): void
    {
        add_action('yith_wcbk_new_booking', [self::class, 'snapshot'], 20, 2);
    }

    public static function snapshot(int $booking_id, \YITH_WCBK_Booking $booking): void
    {
        if ($booking_id <= 0 || Family::forBooking($booking) !== 'premium') {
            return;
        }

        $tier = self::detectTier($booking);

        if ($tier === null) {
            return;
        }

        update_field('lumiboo_prints', $tier, 'yith_booking_' . $booking_id);
    }

    /**
     * Commercial name for a stored prints tier.
     * Null when the tier is missing or unknown.
     */
    public static function marketingLabel(?string $tier): ?string
    {
        if ($tier === null || $tier === '') {
            return null;
        }

        $map = apply_filters('lumiboo_impressions_marketing_labels', [
            '400'       => 'Light',
            '800'       => 'Pro',
            'unlimited' => 'Ultra',
        ]);

        if (! is_array($map)) {
            return null;
        }

        $label = $map[$tier] ?? null;

        return is_string($label) && $label !== '' ? $label : null;
    }

    public static function detectTier(\YITH_WCBK_Booking $booking): ?string
    {
        if (Family::forBooking($booking) !== 'premium') {
            return null;
        }

        $item = self::orderItem($booking);

        if ($item === null) {
            return null;
        }

        $addon_ids = array_map('intval', (array) apply_filters(
            'lumiboo_impressions_wapo_addon_ids',
            self::DEFAULT_ADDON_IDS
        ));

        $meta = $item->get_meta('_ywapo_meta_data');

        if (! is_array($meta)) {
            return null;
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

        return null;
    }

    private static function valueToTier(string $value): ?string
    {
        $needle = mb_strtolower($value);

        // Match 800 before 400, then "illimit", so a combined label cannot hit the wrong tier.
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
