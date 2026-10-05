<?php

namespace Theme_base;

class Personalisation
{
    public const TEXT_MAX = 50;

    public const REQUESTS_MAX = 2000;

    private static $uploadFailed = false;

    public static function register(): void
    {
        add_action('woocommerce_view_order', [self::class, 'render'], 20, 1);
        add_action('admin_post_lumiboo_save_personalisation', [self::class, 'handleSave']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueForm'], 20);
    }

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

    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('lumiboo_perso_' . $key, 'option');

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

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

    public static function templateKeys(): array
    {
        $keys = [];

        foreach (self::templates() as $template) {
            $keys[] = $template['key'];
        }

        return $keys;
    }

    public static function viewOrderUrl(array $dossier): string
    {
        $order_id = (string) ($dossier['order_id'] ?? '');

        if ($order_id === '' || ! ctype_digit($order_id)) {
            return '';
        }

        return (string) wc_get_endpoint_url(
            'view-order',
            $order_id,
            wc_get_page_permalink('myaccount')
        );
    }

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

    public static function render($order_id): void
    {
        $order_id = (string) $order_id;

        if ($order_id === '' || ! ctype_digit($order_id)) {
            return;
        }

        $bookings = [];

        foreach (Dossier::bookingsForCurrentUser() as $dossier) {
            if ((string) ($dossier['order_id'] ?? '') !== $order_id) {
                continue;
            }

            $bookings[] = $dossier;
        }

        if ($bookings === []) {
            return;
        }

        get_template_part('partials/account/personalisation', null, [
            'bookings' => $bookings,
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
            'forbidden'    => self::text('forbidden', 'Cette commande n’est pas accessible depuis votre compte.'),
            'upload_error' => self::text('upload_error', 'Le fichier n’a pas pu être envoyé. Vérifiez le format et la taille.'),
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

        $booking_id = isset($_POST['booking_id'])
            ? sanitize_text_field(wp_unslash((string) $_POST['booking_id']))
            : '';

        if ($booking_id === '' || ! ctype_digit($booking_id)) {
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }

        check_admin_referer('lumiboo_perso_booking_' . $booking_id);

        $dossier = Dossier::findBookingForCurrentUser($booking_id);

        if ($dossier === null) {
            self::redirect(wc_get_page_permalink('myaccount'), 'forbidden', '');
        }

        if (! empty($dossier['is_locked'])) {
            self::redirect(self::viewOrderUrl($dossier), 'locked', (string) ($dossier['id'] ?? ''));
        }

        self::$uploadFailed = false;

        if (function_exists('update_field')) {
            self::writeBooking($dossier);
        }

        $fresh = Dossier::findBookingForCurrentUser($booking_id) ?? $dossier;
        $flag  = self::$uploadFailed
            ? 'upload_error'
            : (Progress::ofBooking($fresh)['complete'] ? 'saved' : 'partial');

        self::redirect(self::viewOrderUrl($fresh), $flag, (string) ($fresh['id'] ?? ''));
    }

    /**
     * Persist one booking's personalisation fields under yith_booking_{id}.
     *
     * welcome / rear are written only when gamme = studio AND has_studio_option.
     * A missing file keeps the stored attachment. « Sans » clears it.
     */
    private static function writeBooking(array $dossier): void
    {
        $booking_id = (int) ($dossier['id'] ?? 0);

        if ($booking_id <= 0) {
            return;
        }

        $pid = 'yith_booking_' . $booking_id;

        $template = sanitize_key((string) (self::posted('template') ?? ''));
        if (! in_array($template, self::templateKeys(), true)) {
            $template = '';
        }

        $text = sanitize_text_field((string) (self::posted('text') ?? ($dossier['text'] ?? '')));
        $text = mb_substr($text, 0, self::TEXT_MAX, 'UTF-8');

        $requests = sanitize_textarea_field((string) (self::posted('requests') ?? ($dossier['requests'] ?? '')));
        $requests = mb_substr($requests, 0, self::REQUESTS_MAX, 'UTF-8');

        update_field('lumiboo_template', $template, $pid);
        update_field(
            'lumiboo_logo_mode',
            self::sanitizeMode(self::posted('logo_mode'), (string) ($dossier['logo_mode'] ?? Progress::MODE_NOT_ANSWERED)),
            $pid
        );
        update_field('lumiboo_logo', self::resolveFile('logo', self::posted('logo_mode'), $dossier['logo'] ?? 0, ['png']), $pid);
        update_field(
            'lumiboo_text_mode',
            self::sanitizeMode(self::posted('text_mode'), (string) ($dossier['text_mode'] ?? Progress::MODE_NOT_ANSWERED)),
            $pid
        );
        update_field('lumiboo_text', $text, $pid);
        update_field('lumiboo_requests', $requests, $pid);

        $is_studio_with_option = (string) ($dossier['gamme'] ?? '') === Gamme::STUDIO
            && ! empty($dossier['has_studio_option']);

        if ($is_studio_with_option) {
            update_field(
                'lumiboo_welcome_mode',
                self::sanitizeMode(self::posted('welcome_mode'), (string) ($dossier['welcome_mode'] ?? Progress::MODE_NOT_ANSWERED)),
                $pid
            );
            update_field('lumiboo_welcome', self::resolveFile('welcome', self::posted('welcome_mode'), $dossier['welcome'] ?? 0, ['png']), $pid);
            update_field(
                'lumiboo_rear_mode',
                self::sanitizeMode(self::posted('rear_mode'), (string) ($dossier['rear_mode'] ?? Progress::MODE_NOT_ANSWERED)),
                $pid
            );
            update_field('lumiboo_rear', self::resolveFile('rear', self::posted('rear_mode'), $dossier['rear'] ?? 0, ['png', 'mp4']), $pid);
        }
    }

    private static function posted(string $key)
    {
        if (! array_key_exists($key, $_POST)) {
            return null;
        }

        $value = wp_unslash($_POST[$key]);

        return is_scalar($value) ? (string) $value : null;
    }

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

    private static function keepFileId($value)
    {
        $id = (int) $value;

        return $id > 0 ? $id : '';
    }

    /**
     * @param string[] $exts
     */
    private static function resolveFile(string $input, $postedMode, $existing, array $exts)
    {
        if (self::sanitizeMode($postedMode, Progress::MODE_NOT_ANSWERED) === Progress::MODE_WITHOUT) {
            return '';
        }

        $file = $_FILES[$input] ?? null;

        if (! is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return self::keepFileId($existing);
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! self::fileAllowed($file, $exts)) {
            self::$uploadFailed = true;

            return self::keepFileId($existing);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $grant = static function (array $caps): array {
            $caps['upload_files'] = true;

            return $caps;
        };

        add_filter('user_has_cap', $grant);
        $id = media_handle_upload($input, 0, [], ['test_form' => false]);
        remove_filter('user_has_cap', $grant);

        if (is_wp_error($id) || (int) $id <= 0) {
            self::$uploadFailed = true;

            return self::keepFileId($existing);
        }

        return (int) $id;
    }

    /**
     * @param string[] $exts
     */
    private static function fileAllowed(array $file, array $exts): bool
    {
        $maxMb = function_exists('get_field') ? (int) get_field('lumiboo_upload_max_mb', 'option') : 5;
        $maxMb = $maxMb > 0 ? $maxMb : 5;
        $cap   = min($maxMb * MB_IN_BYTES, (int) wp_max_upload_size());

        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > $cap) {
            return false;
        }

        $mimes = [];

        if (in_array('png', $exts, true)) {
            $mimes['png'] = 'image/png';
        }

        if (in_array('mp4', $exts, true)) {
            $mimes['mp4'] = 'video/mp4';
        }

        $checked = wp_check_filetype_and_ext((string) ($file['tmp_name'] ?? ''), (string) ($file['name'] ?? ''), $mimes);
        $ext     = is_string($checked['ext'] ?? null) ? $checked['ext'] : '';

        return $ext !== '' && in_array($ext, $exts, true);
    }

    private static function redirect(string $url, string $flag, string $booking_id = ''): never
    {
        $target = $url !== '' ? $url : (string) wc_get_page_permalink('myaccount');
        $target = add_query_arg('lumiboo_saved', $flag, $target);

        if ($booking_id !== '' && ctype_digit($booking_id)) {
            $target .= '#lumiboo-booking-' . $booking_id;
        }

        wp_safe_redirect($target);
        exit;
    }
}
