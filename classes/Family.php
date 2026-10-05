<?php

namespace Theme_base;

class Family
{
    public static function register(): void
    {
        add_filter('acf/decode_post_id', [self::class, 'decodeBookingPostId'], 10, 2);
    }

    public static function decodeBookingPostId(array $decoded, $post_id): array
    {
        if (! is_string($post_id) || ! str_starts_with($post_id, 'yith_booking_')) {
            return $decoded;
        }

        $id = (int) substr($post_id, strlen('yith_booking_'));

        if ($id <= 0 || (string) $id !== substr($post_id, strlen('yith_booking_'))) {
            return $decoded;
        }

        return [
            'type' => 'post',
            'id'   => $id,
        ];
    }

    public static function forBooking(\YITH_WCBK_Booking $booking): ?string
    {
        return Gamme::ofBooking($booking);
    }

    public static function forOrderItem(\WC_Order_Item_Product $item): ?string
    {
        $data = $item->get_meta('yith_booking_data');

        if (! is_array($data) || ! isset($data['resource_ids']) || ! is_array($data['resource_ids'])) {
            return null;
        }

        if (! array_key_exists(0, $data['resource_ids'])) {
            return null;
        }

        return Gamme::ofPost((int) $data['resource_ids'][0]);
    }
}
