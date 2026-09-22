<?php

namespace Theme_base;

/**
 * Personnalisation client (chunks 6–7).
 *
 * Chunk 6 : libellés de la barre, URL native `view-order/{id}`,
 * note de deadline.
 *
 * Chunk 7 : formulaire sous le détail de commande WooCommerce
 * (`woocommerce_view_order` priorité 20). Un formulaire, deux boutons
 * d'enregistrement (Q054). Sauvegarde partielle (Q051). Écriture ACF
 * via `update_field` sur `woo_order_{id}`, jamais `$order->update_meta_data()`.
 *
 * Distinct de `Account` (checkout, dashboard, menu). Textes du formulaire :
 * `Personalisation::text()` (`lumiboo_perso_*`). Libellés de progression :
 * `Account::text()`.
 */
class Personalisation
{
    public const TEXT_MAX = 50;

    public const REQUESTS_MAX = 2000;

    public static function register(): void
    {
        add_action('woocommerce_view_order', [self::class, 'render'], 20, 1);
        add_action('admin_post_lumiboo_save_personalisation', [self::class, 'handleSave']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueForm'], 20);
    }

    /**
     * Libellés des étapes de la barre, clés identiques à `Progress::ofUnit`.
     *
     * @return array<string, string>
     */
    public static function progressLabels(): array
    {
        return [
            'template' => Account::text('step_template', 'Choix du template'),
            'logo'     => Account::text('step_logo', 'Logo'),
            'text'     => Account::text('step_text', 'Texte'),
            'welcome'  => Account::text('step_welcome', 'Écran d’accueil'),
            'rear'     => Account::text('step_rear', 'Écran arrière'),
            'complete' => Account::text('step_complete', 'Personnalisation complétée'),
        ];
    }

    /**
     * Textes du formulaire, page d'options `lumiboo-settings`.
     * Clé ACF = `lumiboo_perso_{$key}`. Fallback FR avant import JSON.
     */
    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('lumiboo_perso_' . $key, 'option');

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    /**
     * Modèles depuis le répéteur d'options `lumiboo_templates`.
     * Mémoïsé pour la requête.
     *
     * @return array<int, array{key: string, label: string, image: int}>
     */
    public static function templates(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];

        if (! function_exists('get_field')) {
            return $cache;
        }

        $rows = get_field('lumiboo_templates', 'option') ?: [];

        foreach ((array) $rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = sanitize_key((string) ($row['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $image = $row['image'] ?? 0;

            if (is_array($image)) {
                $image = (int) ($image['ID'] ?? $image['id'] ?? 0);
            } else {
                $image = (int) $image;
            }

            $cache[] = [
                'key'   => $key,
                'label' => (string) ($row['label'] ?? $key),
                'image' => $image,
            ];
        }

        return $cache;
    }

    /**
     * @return array<int, string>
     */
    public static function templateKeys(): array
    {
        $keys = [];

        foreach (self::templates() as $template) {
            $keys[] = $template['key'];
        }

        return $keys;
    }

    /**
     * URL native WooCommerce de la commande (`view-order/{id}`).
     * Chaîne vide si l'identifiant manque — le partial ne rend pas le lien.
     *
     * @param array<string, mixed> $dossier
     */
    public static function viewOrderUrl(array $dossier): string
    {
        $id = (string) ($dossier['id'] ?? '');

        if ($id === '' || ! ctype_digit($id)) {
            return '';
        }

        return (string) wc_get_endpoint_url(
            'view-order',
            $id,
            wc_get_page_permalink('myaccount')
        );
    }

    /**
     * Note « Jusqu’au {date} ». `str_replace` plutôt que `sprintf` :
     * un `%s` retiré dans ACF ne déclenche pas d'avertissement PHP.
     *
     * @param array<string, mixed> $dossier
     */
    public static function deadlineNote(array $dossier): string
    {
        $deadline = Deadline::of($dossier);

        if (! $deadline instanceof \DateTimeImmutable) {
            return '';
        }

        $date = wp_date((string) get_option('date_format'), $deadline->getTimestamp());
        $tpl  = Account::text('msg_deadline', 'Jusqu’au %s');

        return str_contains($tpl, '%s')
            ? str_replace('%s', $date, $tpl)
            : $tpl . ' ' . $date;
    }

    /**
     * Titre d'une unité. `str_replace` sur `%s`, jamais `sprintf`.
     */
    public static function unitTitle(int $index): string
    {
        $n   = (string) ($index + 1);
        $tpl = self::text('unit_title', 'Lumiboo %s');

        return str_contains($tpl, '%s')
            ? str_replace('%s', $n, $tpl)
            : $tpl . ' ' . $n;
    }

    public static function enqueueForm(): void
    {
        if (! function_exists('is_wc_endpoint_url') || ! is_wc_endpoint_url('view-order')) {
            return;
        }

        $vite = Vite::instance();

        if ($vite instanceof Vite) {
            $vite->enqueueNamed('account');
        }
    }

    /**
     * Formulaire sous le tableau de commande natif. Silence si le dossier
     * n'appartient pas au client (IDOR).
     *
     * @param int|string $order_id
     */
    public static function render($order_id): void
    {
        $order_id = (string) $order_id;

        if ($order_id === '' || ! ctype_digit($order_id)) {
            return;
        }

        $dossier = Dossier::findForCurrentUser($order_id);

        if ($dossier === null) {
            return;
        }

        get_template_part('partials/account/personalisation', null, [
            'dossier' => $dossier,
        ]);
    }

    public static function renderNotice(): void
    {
        static $printed = false;

        if ($printed) {
            return;
        }

        $flag = isset($_GET['lumiboo_saved'])
            ? sanitize_key(wp_unslash((string) $_GET['lumiboo_saved']))
            : '';

        $map = [
            'saved'     => self::text('saved', 'Personnalisation enregistrée.'),
            'partial'   => self::text('partial', 'Vos modifications sont enregistrées. Il reste des étapes à compléter.'),
            'locked'    => self::text('locked_notice', 'Modifications fermées : contactez Lumiboo.'),
            'forbidden' => self::text('forbidden', 'Cette commande n’est pas accessible depuis votre compte.'),
        ];

        if (! isset($map[$flag])) {
            return;
        }

        $printed = true;

        printf(
            '<p class="account-notice account-notice--%s" role="status">%s</p>',
            esc_attr($flag),
            esc_html($map[$flag])
        );
    }

    public static function handleSave(): void
    {
        if (! is_user_logged_in()) {
            wp_die('', 403);
        }

        $order_id = isset($_POST['order_id'])
            ? sanitize_text_field(wp_unslash((string) $_POST['order_id']))
            : '';

        if ($order_id === '' || ! ctype_digit($order_id)) {
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }

        check_admin_referer('lumiboo_perso_' . $order_id);

        $dossier = Dossier::findForCurrentUser($order_id);

        if ($dossier === null) {
            self::redirect(wc_get_page_permalink('myaccount'), 'forbidden');
        }

        if (empty($dossier['paid']) || Deadline::isLocked($dossier)) {
            self::redirect(self::viewOrderUrl($dossier), 'locked');
        }

        $posted = isset($_POST['units']) && is_array($_POST['units'])
            ? wp_unslash($_POST['units'])
            : [];

        if (function_exists('update_field')) {
            update_field(
                'lumiboo_units',
                self::rowsFromPost($dossier, $posted),
                'woo_order_' . $order_id
            );
            self::saveStudio($dossier, $order_id);
        }

        $fresh = Dossier::findForCurrentUser($order_id);
        $flag  = (is_array($fresh) && Progress::isComplete($fresh)) ? 'saved' : 'partial';

        self::redirect(self::viewOrderUrl($fresh ?? $dossier), $flag);
    }

    /**
     * @param array<string, mixed> $dossier
     * @param array<int|string, mixed> $posted
     * @return array<int, array<string, mixed>>
     */
    private static function rowsFromPost(array $dossier, array $posted): array
    {
        $quantity = max(0, (int) ($dossier['quantity'] ?? 0));
        $existing = (array) ($dossier['units'] ?? []);
        $keys     = self::templateKeys();
        $rows      = [];

        for ($i = 0; $i < $quantity; $i++) {
            $current = is_array($existing[$i] ?? null) ? $existing[$i] : [];
            $input   = is_array($posted[$i] ?? null) ? $posted[$i] : [];

            $template = sanitize_key((string) ($input['template'] ?? ''));

            if (! in_array($template, $keys, true)) {
                $template = '';
            }

            $text = sanitize_text_field((string) ($input['text'] ?? ($current['text'] ?? '')));
            $text = mb_substr($text, 0, self::TEXT_MAX, 'UTF-8');

            $requests = sanitize_textarea_field((string) ($input['requests'] ?? ($current['requests'] ?? '')));
            $requests = mb_substr($requests, 0, self::REQUESTS_MAX, 'UTF-8');

            $row = [
                'lumiboo_template'  => $template,
                'lumiboo_logo_mode' => self::sanitizeMode($input['logo_mode'] ?? null, $current['logo_mode'] ?? Progress::MODE_NOT_ANSWERED),
                'lumiboo_logo'      => self::keepFileId($current['logo'] ?? 0),
                'lumiboo_text_mode' => self::sanitizeMode($input['text_mode'] ?? null, $current['text_mode'] ?? Progress::MODE_NOT_ANSWERED),
                'lumiboo_text'      => $text,
                'lumiboo_requests'  => $requests,
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Écran d'accueil et plaque arrière : une fois par commande, hors repeater.
     *
     * @param array<string, mixed> $dossier
     */
    private static function saveStudio(array $dossier, string $order_id): void
    {
        if (empty($dossier['has_studio_option'])) {
            return;
        }

        $posted = isset($_POST['studio']) && is_array($_POST['studio'])
            ? wp_unslash($_POST['studio'])
            : [];

        $pid = 'woo_order_' . $order_id;

        update_field(
            'lumiboo_welcome_mode',
            self::sanitizeMode($posted['welcome_mode'] ?? null, (string) ($dossier['welcome_mode'] ?? Progress::MODE_NOT_ANSWERED)),
            $pid
        );
        update_field('lumiboo_welcome', self::keepFileId($dossier['welcome'] ?? 0), $pid);
        update_field(
            'lumiboo_rear_mode',
            self::sanitizeMode($posted['rear_mode'] ?? null, (string) ($dossier['rear_mode'] ?? Progress::MODE_NOT_ANSWERED)),
            $pid
        );
        update_field('lumiboo_rear', self::keepFileId($dossier['rear'] ?? 0), $pid);
    }

    /**
     * @param mixed $posted
     */
    private static function sanitizeMode($posted, string $fallback): string
    {
        $mode = is_string($posted) ? sanitize_key($posted) : '';
        $allowed = [
            Progress::MODE_NOT_ANSWERED,
            Progress::MODE_WITH,
            Progress::MODE_WITHOUT,
        ];

        return in_array($mode, $allowed, true) ? $mode : $fallback;
    }

    /**
     * @param mixed $value
     * @return int|string
     */
    private static function keepFileId($value)
    {
        $id = (int) $value;

        return $id > 0 ? $id : '';
    }

    private static function redirect(string $url, string $flag): never
    {
        wp_safe_redirect(add_query_arg('lumiboo_saved', $flag, $url));
        exit;
    }
}
