<?php

namespace Theme_base;

class Gamme
{
    public const TAXONOMY = 'gamme';

    public const PREMIUM = 'premium';

    public const STUDIO = 'studio';

    private const OBJECT_TYPES = ['product', 'yith_booking', 'ywcbk-resource'];

    private const TERMS = [
        self::PREMIUM => 'Premium',
        self::STUDIO  => 'Studio',
    ];

    public static function register(): void
    {
        add_action('init', [self::class, 'registerTaxonomy']);
        add_action('yith_wcbk_new_booking', [self::class, 'inheritFromResource'], 5, 2);
        add_filter('template_include', [self::class, 'routeProductTemplate'], 20);

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('lumiboo backfill-gamme', [self::class, 'backfillCli']);
        }
    }

    public static function backfillCli(array $args, array $assoc_args): void
    {
        $products  = self::backfillProducts();
        $resources = self::backfillResources();
        $bookings  = self::backfillBookings();

        \WP_CLI::log(sprintf(
            'Produits : %d tagués, %d déjà à jour.',
            $products['tagged'],
            $products['skipped']
        ));
        \WP_CLI::log(sprintf(
            'Ressources : %d tagués, %d déjà à jour, %d hors mapping.',
            $resources['tagged'],
            $resources['skipped'],
            $resources['unmapped']
        ));
        \WP_CLI::log(sprintf(
            'Réservations : %d tagués, %d déjà à jour, %d sans ressource.',
            $bookings['tagged'],
            $bookings['skipped'],
            $bookings['orphan']
        ));

        \WP_CLI::success('Backfill gamme terminé.');
    }

    private static function backfillProducts(): array
    {
        $tagged  = 0;
        $skipped = 0;

        $ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        foreach ($ids as $id) {
            $id = (int) $id;

            if (self::ofPost($id) !== null) {
                $skipped++;
                continue;
            }

            $name = (string) get_the_title($id);
            $slug = stripos($name, 'studio') !== false ? self::STUDIO : self::PREMIUM;

            wp_set_object_terms($id, $slug, self::TAXONOMY, false);
            $tagged++;
        }

        return ['tagged' => $tagged, 'skipped' => $skipped];
    }

    private static function backfillResources(): array
    {
        $tagged   = 0;
        $skipped  = 0;
        $unmapped = 0;

        $map = self::resourceIdMap();

        $ids = get_posts([
            'post_type'      => 'ywcbk-resource',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        foreach ($ids as $id) {
            $id = (int) $id;

            if (self::ofPost($id) !== null) {
                $skipped++;
                continue;
            }

            $slug = $map[$id] ?? null;

            if ($slug === null) {
                $unmapped++;
                continue;
            }

            wp_set_object_terms($id, $slug, self::TAXONOMY, false);
            $tagged++;
        }

        return ['tagged' => $tagged, 'skipped' => $skipped, 'unmapped' => $unmapped];
    }

    private static function backfillBookings(): array
    {
        $tagged  = 0;
        $skipped = 0;
        $orphan  = 0;

        $ids = get_posts([
            'post_type'      => 'yith_booking',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        foreach ($ids as $id) {
            $id = (int) $id;

            if (self::ofPost($id) !== null) {
                $skipped++;
                continue;
            }

            $booking = Booking::forPost($id);

            if ($booking === null) {
                $orphan++;
                continue;
            }

            $slug = self::ofBooking($booking);

            if ($slug === null) {
                $orphan++;
                continue;
            }

            wp_set_object_terms($id, $slug, self::TAXONOMY, false);
            $tagged++;
        }

        return ['tagged' => $tagged, 'skipped' => $skipped, 'orphan' => $orphan];
    }

    private static function resourceIdMap(): array
    {
        if (! function_exists('get_field')) {
            return [];
        }

        $map = [];

        foreach ([self::PREMIUM => 'premium_ressource_id', self::STUDIO => 'studio_ressource_id'] as $slug => $key) {
            $id = (int) get_field($key, 'lumiboo-settings');

            if ($id > 0) {
                $map[$id] = $slug;
            }
        }

        return $map;
    }

    public static function routeProductTemplate(string $template): string
    {
        if (! is_singular('product')) {
            return $template;
        }

        $gamme = self::ofPost((int) get_queried_object_id());

        if ($gamme === null) {
            return $template;
        }

        $located = locate_template('woocommerce/single-product-' . $gamme . '.php');

        return $located !== '' ? $located : $template;
    }

    public static function registerTaxonomy(): void
    {
        register_taxonomy(self::TAXONOMY, self::OBJECT_TYPES, self::args());
        self::ensureTerms();
    }

    public static function ofPost(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $slugs = wp_get_object_terms($id, self::TAXONOMY, ['fields' => 'slugs']);

        if (is_wp_error($slugs) || ! is_array($slugs)) {
            return null;
        }

        foreach ($slugs as $slug) {
            if (isset(self::TERMS[(string) $slug])) {
                return (string) $slug;
            }
        }

        return null;
    }

    public static function ofBooking(\YITH_WCBK_Booking $booking): ?string
    {
        $own = self::ofPost((int) $booking->get_id());

        if ($own !== null) {
            return $own;
        }

        $resource_id = self::resourceId($booking);

        return $resource_id > 0 ? self::ofPost($resource_id) : null;
    }

    public static function inheritFromResource(int $booking_id, \YITH_WCBK_Booking $booking): void
    {
        if ($booking_id <= 0 || self::ofPost($booking_id) !== null) {
            return;
        }

        $slug = self::ofBooking($booking);

        if ($slug === null) {
            return;
        }

        wp_set_object_terms($booking_id, $slug, self::TAXONOMY, false);
    }

    private static function args(): array
    {
        return [
            'labels'            => [
                'name'          => __('Gammes', 'theme_base'),
                'singular_name' => __('Gamme', 'theme_base'),
                'search_items'  => __('Rechercher une gamme', 'theme_base'),
                'all_items'     => __('Toutes les gammes', 'theme_base'),
                'edit_item'     => __('Modifier la gamme', 'theme_base'),
                'update_item'   => __('Mettre à jour la gamme', 'theme_base'),
                'add_new_item'  => __('Ajouter une gamme', 'theme_base'),
                'new_item_name' => __('Nom de la nouvelle gamme', 'theme_base'),
                'menu_name'     => __('Gammes', 'theme_base'),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => false,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => false,
            'query_var'         => false,
            'capabilities'      => [
                'manage_terms' => 'manage_product_terms',
                'edit_terms'   => 'edit_product_terms',
                'delete_terms' => 'delete_product_terms',
                'assign_terms' => 'assign_product_terms',
            ],
        ];
    }

    private static function ensureTerms(): void
    {
        foreach (self::TERMS as $slug => $name) {
            if (term_exists($slug, self::TAXONOMY)) {
                continue;
            }

            wp_insert_term($name, self::TAXONOMY, ['slug' => $slug]);
        }
    }

    private static function resourceId(\YITH_WCBK_Booking $booking): int
    {
        $ids = $booking->get_resource_ids();

        if (! is_array($ids) || ! array_key_exists(0, $ids)) {
            return 0;
        }

        return (int) $ids[0];
    }
}
