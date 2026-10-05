<?php

namespace Theme_base;

class Dossier
{

    /**
     * All WC core statuses. Kept as a filter (not "any") so wc_get_orders()
     * still uses an indexed status IN() and reads stay fast on big shops.
     * cancelled / refunded / failed stay visible in the account and will
     * carry a distinct badge later ; UI decides the CTA per status, not this
     * list.
     */
    private const ELIGIBLE_STATUSES = [
        'pending',
        'processing',
        'on-hold',
        'completed',
        'cancelled',
        'refunded',
        'failed',
    ];

    /**
     * Build a per-booking dossier dictionary.
     *
     * Reads:
     *  - order-level ACF (shipping_mode, location) via woo_order_{id}
     *  - booking-level ACF (template, logo, text, requests, prints/has_studio_option,
     *    welcome/rear, locked_by_admin) via yith_booking_{id}
     *
     * The gamme is resolved through Gamme::ofBooking(). Bookings without a gamme
     * (unmapped resource) are ignored — the caller receives null.
     */
    public static function fromBooking(\YITH_WCBK_Booking $booking, \WC_Order $order): ?array
    {
        $booking_id = (int) $booking->get_id();

        if ($booking_id <= 0) {
            return null;
        }

        $gamme = Gamme::ofBooking($booking);

        if ($gamme === null) {
            return null;
        }

        $bpid = 'yith_booking_' . $booking_id;
        $opid = 'woo_order_' . $order->get_id();

        $event  = self::timestampToDate((int) $booking->get_from());
        $return = self::timestampToDate((int) $booking->get_to());

        $locked_by_admin = (bool) get_field('lumiboo_locked_by_admin', $bpid);

        $data = [
            'id'                    => (string) $booking_id,
            'order'                 => $order,
            'order_id'              => (string) $order->get_id(),
            'reference'             => (string) $order->get_order_number(),
            'gamme'                 => $gamme,
            'title'                 => (string) get_the_title($booking_id),
            'event_date'            => $event,
            'return_date'           => $return,
            'shipping_mode'         => (string) (get_field('lumiboo_shipping_mode', $opid) ?: ''),
            'location'              => (string) (get_field('lumiboo_location', $opid) ?: ''),
            'order_total_formatted' => self::formattedOrderTotal($order),
            'template'              => (string) (get_field('lumiboo_template', $bpid) ?: ''),
            'logo_mode'             => (string) (get_field('lumiboo_logo_mode', $bpid) ?: Progress::MODE_NOT_ANSWERED),
            'logo'                  => (int) (get_field('lumiboo_logo', $bpid) ?: 0),
            'text_mode'             => (string) (get_field('lumiboo_text_mode', $bpid) ?: Progress::MODE_NOT_ANSWERED),
            'text'                  => (string) (get_field('lumiboo_text', $bpid) ?: ''),
            'requests'              => (string) (get_field('lumiboo_requests', $bpid) ?: ''),
            'locked_by_admin'       => $locked_by_admin,
        ];

        if ($gamme === Gamme::PREMIUM) {
            $data['prints'] = (string) (get_field('lumiboo_prints', $bpid) ?: '');
        }

        if ($gamme === Gamme::STUDIO) {
            $has_option = (bool) get_field('lumiboo_has_studio_option', $bpid);
            $data['has_studio_option'] = $has_option;

            if ($has_option) {
                $data['welcome_mode'] = (string) (get_field('lumiboo_welcome_mode', $bpid) ?: Progress::MODE_NOT_ANSWERED);
                $data['welcome']      = (int) (get_field('lumiboo_welcome', $bpid) ?: 0);
                $data['rear_mode']    = (string) (get_field('lumiboo_rear_mode', $bpid) ?: Progress::MODE_NOT_ANSWERED);
                $data['rear']         = (int) (get_field('lumiboo_rear', $bpid) ?: 0);
            }
        }

        // Composite lock : J-7 or admin-forced.
        $data['is_locked'] = $locked_by_admin || Deadline::isLocked($data);
        $data['is_past']   = Deadline::isPast($data);

        return $data;
    }

    /**
     * Flat list of per-booking dossiers for the current customer.
     *
     * Iterates wc_get_orders(customer_id=...) filtered on eligible statuses,
     * pulls every YITH booking attached to each order, and returns a flat
     * array [booking_id => dossier] ready to sort and group.
     *
     * Orders without any YITH booking are skipped silently.
     */
    public static function bookingsForCurrentUser(): array
    {
        $user_id = get_current_user_id();

        if (! $user_id) {
            return [];
        }

        if (! function_exists('yith_wcbk')) {
            return [];
        }

        $yith = yith_wcbk();

        if (! isset($yith->booking_helper) || ! method_exists($yith->booking_helper, 'get_bookings_by_order')) {
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

            $bookings = (array) $yith->booking_helper->get_bookings_by_order($order->get_id());

            if (! $bookings) {
                continue;
            }

            foreach ($bookings as $booking) {
                if (! $booking instanceof \YITH_WCBK_Booking) {
                    continue;
                }

                $dossier = self::fromBooking($booking, $order);

                if ($dossier === null) {
                    continue;
                }

                $dossiers[(int) $booking->get_id()] = $dossier;
            }
        }

        return $dossiers;
    }

    /**
     * Resolve one booking dossier for the current customer.
     *
     * Guards: user is logged in, booking exists, its parent order belongs to
     * the current customer, order status is in eligibleStatuses().
     */
    public static function findBookingForCurrentUser(string $booking_id): ?array
    {
        if ($booking_id === '' || ! ctype_digit($booking_id) || ! is_user_logged_in()) {
            return null;
        }

        $booking = Booking::forPost((int) $booking_id);

        if ($booking === null) {
            return null;
        }

        $order_id = (int) $booking->get_order_id();

        if ($order_id <= 0) {
            return null;
        }

        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order) {
            return null;
        }

        if ((int) $order->get_user_id() !== get_current_user_id()) {
            return null;
        }

        if (! in_array($order->get_status(), self::eligibleStatuses(), true)) {
            return null;
        }

        return self::fromBooking($booking, $order);
    }

    public static function eligibleStatuses(): array
    {
        return (array) apply_filters(
            'lumiboo_dossier_eligible_statuses',
            self::ELIGIBLE_STATUSES
        );
    }

    private static function timestampToDate(int $timestamp): ?\DateTimeImmutable
    {
        if ($timestamp <= 0) {
            return null;
        }

        return (new \DateTimeImmutable('@' . $timestamp))->setTimezone(wp_timezone());
    }

    private static function formattedOrderTotal(\WC_Order $order): string
    {
        $html = (string) $order->get_formatted_order_total();

        return trim(wp_strip_all_tags(html_entity_decode($html, ENT_QUOTES, 'UTF-8')));
    }
}
