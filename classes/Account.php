<?php

namespace Theme_base;

/**
 * Auto-création du compte client au checkout + carte de remerciement.
 *
 * Chunk 4 : quatre méthodes reprises de la branche `archive/private-files`
 * (`forceGeneratedPassword`, `ensureCustomerAccount`, `flagPasswordPending`,
 * `clearPasswordPending`) et une méthode d'affichage
 * (`renderOrderReceived`) qui rend `partials/account/order-received.php`.
 *
 * Parcours nominal : au checkout, WooCommerce est forcé en
 * `registration_required` + `registration_enabled`, l'option
 * `woocommerce_registration_generate_password` est lue à `yes` (le champ
 * mot de passe est masqué). `WC_Checkout::process_customer()` crée
 * l'utilisateur, l'attache à la commande, l'auto-connecte et déclenche
 * `woocommerce_created_customer` — hook auquel WooCommerce a rattaché
 * l'e-mail natif « Nouveau compte » via `send_transactional_email` →
 * `woocommerce_created_customer_notification`. Le lien de définition du
 * mot de passe est donc envoyé automatiquement.
 *
 * Filet de sécurité : si `process_customer()` n'a pas créé le compte
 * (commande arrivant sans utilisateur), `ensureCustomerAccount()`
 * intervient à `woocommerce_checkout_order_processed` priorité 10 —
 * avant Studio et Impressions (priorité 20). Une adresse déjà
 * enregistrée n'est jamais rattachée en aveugle (décision Q034) : WC
 * bloque déjà en amont avec « merci de vous connecter ».
 *
 * Marqueur "mot de passe en attente" : `flagPasswordPending()` pose
 * `_lumiboo_password_pending = 1` quand WC a généré le mot de passe.
 * `clearPasswordPending()` supprime le marqueur au premier
 * `after_password_reset` (le client vient de définir son mot de passe
 * via le lien du mail natif).
 *
 * Carte Lumiboo : `renderOrderReceived()` est branchée à
 * `woocommerce_thankyou` priorité 5 pour s'insérer entre la liste
 * d'aperçu de commande (imprimée par `thankyou.php`) et le tableau de
 * détails (`woocommerce_order_details_table` à la priorité 10).
 *
 * Chunk 5 : coquille My Account. Le template WooCommerce
 * `myaccount/dashboard.php` délègue à `partials/account/dashboard.php`.
 * Le menu est réduit (dashboard + logout). Les dossiers viennent de
 * `Dossier` + `Deadline::isPast()` — pas des fixtures archive, pas de
 * fuseau Zurich. Chunk 6 : barre / deadline / CTA vivent dans
 * `Personalisation` (formulaire chunk 7).
 */
class Account
{
    /** Marque un compte dont le mot de passe reste à définir. */
    private const PASSWORD_PENDING = '_lumiboo_password_pending';

    public static function register(): void
    {
        // Compte obligatoire au checkout, mot de passe généré par WC :
        // le champ mot de passe disparaît du formulaire et WC envoie le
        // mail natif « Nouveau compte » avec le lien de définition
        // (G14 / Q033).
        add_filter('woocommerce_checkout_registration_required', '__return_true');
        add_filter('woocommerce_checkout_registration_enabled', '__return_true');
        add_filter(
            'option_woocommerce_registration_generate_password',
            [self::class, 'forceGeneratedPassword']
        );

        // Filet de sécurité : rattache la commande si `process_customer()`
        // n'a pas créé de compte. Avant Studio / Impressions à 20.
        add_action(
            'woocommerce_checkout_order_processed',
            [self::class, 'ensureCustomerAccount'],
            10,
            3
        );

        // Marqueur "mot de passe non défini" et son nettoyage.
        add_action('woocommerce_created_customer', [self::class, 'flagPasswordPending'], 10, 3);
        add_action('after_password_reset', [self::class, 'clearPasswordPending']);

        // Carte Lumiboo entre l'aperçu de commande et le tableau de
        // détails (WC hooke `woocommerce_order_details_table` à 10).
        add_action('woocommerce_thankyou', [self::class, 'renderOrderReceived'], 5);

        // Coquille My Account (chunk 5) : menu réduit.
        add_filter('woocommerce_account_menu_items', [self::class, 'filterMenuItems']);

        // Les virements bancaires restent en `on-hold` : sans ce filtre
        // la commande de test n'apparaît jamais (Dossier ne retient
        // que processing/completed, en attendant le mapping YITH B01).
        add_filter('lumiboo_dossier_eligible_statuses', [self::class, 'includeOnHoldStatus']);
    }

    /**
     * Force l'option WC « générer le mot de passe automatiquement » à
     * `yes`, quelle que soit la valeur en base. Le champ mot de passe
     * disparaît du formulaire d'inscription du checkout.
     *
     * @param mixed $value
     */
    public static function forceGeneratedPassword($value): string
    {
        return 'yes';
    }

    /**
     * Filet de sécurité : si une commande arrive sans compte rattaché,
     * le compte est créé à partir de l'adresse de facturation et
     * l'adresse est recopiée dans le profil client. Une adresse déjà
     * utilisée n'est jamais rattachée automatiquement (Q034) : WC
     * affiche déjà « merci de vous connecter » côté checkout.
     *
     * L'envoi du mail natif « Nouveau compte » est déclenché par
     * `wc_create_new_customer()` via `woocommerce_created_customer` →
     * `send_transactional_email` → `woocommerce_created_customer_notification`.
     *
     * @param int         $order_id
     * @param array       $posted_data
     * @param \WC_Order|null $order
     */
    public static function ensureCustomerAccount($order_id, $posted_data = [], $order = null): void
    {
        $order = $order instanceof \WC_Order ? $order : wc_get_order($order_id);

        if (! $order instanceof \WC_Order || $order->get_user_id() > 0) {
            return;
        }

        $email = $order->get_billing_email();

        if (! is_email($email) || email_exists($email)) {
            return;
        }

        $customer_id = wc_create_new_customer($email, '', '', [
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        ]);

        if (is_wp_error($customer_id)) {
            return;
        }

        $order->set_customer_id($customer_id);
        $order->save();

        $customer = new \WC_Customer($customer_id);
        $customer->set_props($order->get_address('billing'));
        $customer->save();
    }

    /**
     * Marque un compte dont le mot de passe n'a pas encore été défini
     * (mot de passe généré par WC). Utilisé par la carte thank-you pour
     * orienter le client vers son mail plutôt que vers un login.
     *
     * @param int   $customer_id
     * @param array $new_customer_data
     * @param bool  $password_generated
     */
    public static function flagPasswordPending($customer_id, $new_customer_data = [], $password_generated = false): void
    {
        if ($password_generated) {
            update_user_meta((int) $customer_id, self::PASSWORD_PENDING, '1');
        }
    }

    /**
     * Nettoie le marqueur au premier `after_password_reset` : le client
     * a défini son mot de passe via le lien du mail natif.
     */
    public static function clearPasswordPending($user): void
    {
        if ($user instanceof \WP_User) {
            delete_user_meta($user->ID, self::PASSWORD_PENDING);
        }
    }

    /**
     * Rend la carte Lumiboo sur la page de remerciement. Quatre cas
     * exposés au partial :
     *
     * - `password_pending` : compte créé, mot de passe en attente
     *   → « consultez votre boîte mail » ;
     * - `is_owner`         : client connecté et propriétaire, mot de
     *   passe déjà défini → « ouvrir mon espace » ;
     * - `has_account` sans `is_owner` : commande rattachée mais session
     *   n'est pas ce propriétaire → « se connecter » ;
     * - aucun compte (rare, ex. commande manuelle) → « créer un compte
     *   avec cette adresse ».
     */
    public static function renderOrderReceived($order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order instanceof \WC_Order || $order->has_status('failed')) {
            return;
        }

        $owner_id = (int) $order->get_user_id();

        get_template_part('partials/account/order-received', null, [
            'order'            => $order,
            'is_owner'         => is_user_logged_in() && $owner_id > 0 && $owner_id === get_current_user_id(),
            'has_account'      => $owner_id > 0,
            'password_pending' => $owner_id > 0
                && get_user_meta($owner_id, self::PASSWORD_PENDING, true) === '1',
        ]);
    }

    /* ---------------------------------------------------------------------
     * Chunk 5 — coquille My Account
     * ------------------------------------------------------------------ */

    /**
     * Textes de l'espace compte, page d'options `lumiboo-settings`.
     * Clé ACF = `lumiboo_account_{$key}`. Fallback FR avant import JSON.
     */
    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('lumiboo_account_' . $key, 'option');

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public static function filterMenuItems(array $items): array
    {
        unset(
            $items['orders'],
            $items['downloads'],
            $items['edit-address'],
            $items['payment-methods'],
            $items['edit-account']
        );

        if (isset($items['dashboard'])) {
            $items['dashboard'] = self::text('menu_dashboard', 'Mon espace Lumiboo');
        }

        return $items;
    }

    /**
     * @param array<int, string> $statuses
     * @return array<int, string>
     */
    public static function includeOnHoldStatus(array $statuses): array
    {
        $statuses[] = 'on-hold';

        return array_values(array_unique($statuses));
    }

    /**
     * Dossiers du client connecté, partitionnés en cours / passées.
     *
     * @return array{current: array<int, array<string, mixed>>, past: array<int, array<string, mixed>>}
     */
    public static function dossiers(): array
    {
        $current = [];
        $past    = [];

        foreach (Dossier::dossiersForCurrentUser() as $dossier) {
            if (Deadline::isPast($dossier)) {
                $past[] = $dossier;
                continue;
            }

            $current[] = $dossier;
        }

        $timestamp = static function (array $dossier): int {
            $event = $dossier['event_date'] ?? null;

            return $event instanceof \DateTimeImmutable ? $event->getTimestamp() : 0;
        };

        usort($current, static fn (array $a, array $b): int => $timestamp($a) <=> $timestamp($b));
        usort($past, static fn (array $a, array $b): int => $timestamp($b) <=> $timestamp($a));

        return ['current' => $current, 'past' => $past];
    }

    /**
     * Libellé d'acheminement (clés ACF `pickup` / `delivery` / `dhl`).
     *
     * @param array<string, mixed> $dossier
     */
    public static function shippingLabel(array $dossier): string
    {
        switch ((string) ($dossier['shipping_mode'] ?? '')) {
            case 'pickup':
                return self::text('shipping_pickup', 'Retrait');
            case 'delivery':
                return self::text('shipping_delivery', 'Livraison clé en main');
            case 'dhl':
                return self::text('shipping_dhl', 'DHL');
            default:
                return '';
        }
    }
}
