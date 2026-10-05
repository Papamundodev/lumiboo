<?php

namespace Theme_base;

/**
 * ACF → WAPO one-way sync for a booking's paid option.
 *
 * On every save of a `yith_booking_{id}` ACF form, mirror
 * `lumiboo_prints` (Premium) / `lumiboo_has_studio_option` (Studio) onto the
 * linked order item as WAPO addon meta, and shift the item total by the
 * price delta.
 *
 * Addon / option IDs from `wp_yith_wapo_addons`, hard-coded to stay minimal.
 */
class BookingAddonSync
{
    private const PREMIUM_ADDON_ID = 1;
    private const STUDIO_ADDON_ID  = 2;

    private const PREMIUM_MAP = [
        '400'       => 0, // Light — 0 CHF
        '800'       => 1, // Pro   — 100 CHF
        'unlimited' => 2, // Ultra — 150 CHF
    ];

    private const STUDIO_OPTION_ID = 0; // "Option préférée des entreprises" — 150 CHF

    public static function register(): void
    {
        add_action('acf/save_post', [self::class, 'sync'], 20);
    }

    public static function sync($post_id): void
    {
        if (! is_string($post_id) || ! str_starts_with($post_id, 'yith_booking_')) {
            return;
        }

        if (! current_user_can('edit_shop_orders') && ! current_user_can('manage_woocommerce')) {
            return;
        }

        $booking_id = (int) substr($post_id, strlen('yith_booking_'));

        if ($booking_id <= 0 || ! function_exists('yith_get_booking')) {
            return;
        }

        $booking = yith_get_booking($booking_id);

        if (! $booking instanceof \YITH_WCBK_Booking) {
            return;
        }

        $family = Family::forBooking($booking);

        if ($family !== 'premium' && $family !== 'studio') {
            return;
        }

        $order = wc_get_order((int) $booking->get_order_id());

        if (! $order instanceof \WC_Order) {
            return;
        }

        $item = $order->get_item((int) $booking->get_order_item_id(), false);

        if (! $item instanceof \WC_Order_Item_Product) {
            return;
        }

        $addon_id = $family === 'premium' ? self::PREMIUM_ADDON_ID : self::STUDIO_ADDON_ID;
        $desired  = self::desiredOptionId($family, $booking_id);

        $raw       = $item->get_meta('_ywapo_meta_data');
        $groups    = is_array($raw) ? $raw : [];
        $old_price = 0.0;

        foreach ($groups as $gkey => $group) {
            if (! is_array($group)) {
                unset($groups[$gkey]);
                continue;
            }

            foreach ($group as $entry) {
                if (! is_array($entry) || (int) ($entry['addon_id'] ?? 0) !== $addon_id) {
                    continue;
                }

                $old_price = (float) (yith_wapo_get_option_info($addon_id, (int) ($entry['option_id'] ?? 0))['price'] ?? 0);
                unset($groups[$gkey]);
                break;
            }
        }

        // 2. Drop every visible `ywapo-addon-{addon_id}-*` row: switching
        //    Light → Pro → Ultra changes the meta_key, so the old ones linger.
        $prefix = 'ywapo-addon-' . $addon_id . '-';

        foreach ($item->get_meta_data() as $meta) {
            $key = (string) $meta->key;

            if ($key !== '' && str_starts_with($key, $prefix)) {
                $item->delete_meta_data($key);
            }
        }

        // 3. Add the fresh choice (skipped when Premium ACF is `—`).
        $new_price = 0.0;

        if ($desired !== null) {
            $info          = (array) yith_wapo_get_option_info($addon_id, $desired);
            $new_price     = (float) ($info['price'] ?? 0);
            $addon_value   = (string) ($info['label'] ?? '');
            $display_label = (string) ($info['addon_label'] ?? '');
            $display_value = html_entity_decode(stripslashes(
                YITH_WAPO_Cart()->get_addon_display_on_cart($addon_id, $desired, $addon_value, '+', $new_price, $new_price, '')
            ));

            $groups[] = [
                $addon_id => [
                    0               => (string) $desired,
                    'addon_id'      => $addon_id,
                    'option_id'     => (string) $desired,
                    'addon_value'   => $addon_value,
                    'display_label' => $display_label,
                    'display_value' => $display_value,
                ],
            ];

            $item->update_meta_data($prefix . $desired, $display_value);
        }

        $item->update_meta_data('_ywapo_meta_data', array_values($groups));

        // 4. Shift the line totals by the delta. `calculate_totals(false)`
        //    only reads these back, so the new values survive.
        $delta = $new_price - $old_price;

        if ($delta !== 0.0) {
            $item->set_subtotal((float) $item->get_subtotal() + $delta);
            $item->set_total((float) $item->get_total() + $delta);
        }

        // 5. One save, cascading to the item we just mutated.
        $order->calculate_totals(false);
        $order->save();
    }

    private static function desiredOptionId(string $family, int $booking_id): ?int
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $pid = 'yith_booking_' . $booking_id;

        if ($family === 'premium') {
            $tier = (string) (get_field('lumiboo_prints', $pid) ?: '');
            return self::PREMIUM_MAP[$tier] ?? null;
        }

        return (int) get_field('lumiboo_has_studio_option', $pid) === 1
            ? self::STUDIO_OPTION_ID
            : null;
    }
}
