<?php

namespace Theme_base;

class Form
{
    private const ACTION_NAME = 'custom_contact_form';
    private const NONCE_NAME = 'custom_contact_form';
    private const HONEYPOT_FIELD = 'cf_honeypot';
    private const RECAPTCHA_TOKEN_FIELD = 'cf_recaptcha_token';
    private const RECAPTCHA_SCORE_THRESHOLD = 0.5;
    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private const ACF_GROUP_KEY = 'group_6f0a1b2c3d4e5';
    private const ACF_JSON_FILE = '/acf/form.json';

    public static function register(): void
    {
        add_action('acf/init', [self::class, 'registerAcfGroup']);
        add_action('admin_post_' . self::ACTION_NAME, [self::class, 'handleSubmission']);
        add_action('admin_post_nopriv_' . self::ACTION_NAME, [self::class, 'handleSubmission']);
        add_action('wp_enqueue_scripts', [self::class, 'dequeueRecaptchaOnCheckout'], 999);
    }

    /**
     * Renders the form partial and enqueues its JS/reCAPTCHA on demand.
     * Call this from the footer section instead of get_template_part() directly.
     */
    public static function render(): void
    {
        self::enqueueAssets();
        get_template_part('partials/form');
    }

    /**
     * Load reCAPTCHA v3 + the contact-form Vite entry. Skipped on checkout /
     * order-received / account to avoid conflicts with payment gateways.
     */
    private static function enqueueAssets(): void
    {
        if (is_admin()) {
            return;
        }
        if (function_exists('is_checkout') && is_checkout()) {
            return;
        }
        if (function_exists('is_order_received_page') && is_order_received_page()) {
            return;
        }
        if (function_exists('is_account_page') && is_account_page()) {
            return;
        }

        $vite = Vite::instance();
        if ($vite instanceof Vite) {
            $vite->enqueueNamed('contact-form');
        }

        $site_key = self::getRecaptchaSiteKey();
        if (empty($site_key)) {
            return;
        }

        $recaptcha_url = add_query_arg('render', $site_key, 'https://www.google.com/recaptcha/api.js');
        wp_enqueue_script(
            'google-recaptcha-v3',
            $recaptcha_url,
            [],
            null,
            true
        );
        wp_script_add_data('google-recaptcha-v3', 'defer', true);
        wp_script_add_data('google-recaptcha-v3', 'async', true);
    }

    public static function registerAcfGroup(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        if (self::acfGroupExistsInDatabase()) {
            return;
        }

        foreach (self::readAcfGroups() as $group) {
            acf_add_local_field_group($group);
        }
    }

    private static function acfGroupExistsInDatabase(): bool
    {
        global $wpdb;

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'acf-field-group' AND post_name = %s
             LIMIT 1",
            self::ACF_GROUP_KEY
        ));

        return ! empty($id);
    }

    /**
     * @return array<int, array>
     */
    private static function readAcfGroups(): array
    {
        $path = get_template_directory() . self::ACF_JSON_FILE;
        if (! is_readable($path)) {
            return [];
        }

        $groups = json_decode((string) file_get_contents($path), true);

        return is_array($groups) ? $groups : [];
    }

    /**
     * Order: nonce → honeypot → reCAPTCHA → validation → email.
     */
    public static function handleSubmission(): void
    {
        if (! self::verifySecurity()) {
            self::redirectError('security');
            return;
        }

        if (! self::checkHoneypot()) {
            self::redirectError('spam');
            return;
        }

        if (! self::verifyRecaptcha()) {
            self::redirectError('spam');
            return;
        }

        $data = self::sanitizeInputs();
        $errors = self::validateInputs($data);

        if (! empty($errors)) {
            self::redirectValidationErrors($errors);
            return;
        }

        if (self::sendEmail($data)) {
            self::redirectSuccess();
        } else {
            self::redirectError('email_failed');
        }
    }

    private static function verifySecurity(): bool
    {
        return isset($_POST['cf_nonce']) && wp_verify_nonce($_POST['cf_nonce'], self::NONCE_NAME);
    }

    private static function checkHoneypot(): bool
    {
        return empty($_POST[self::HONEYPOT_FIELD]);
    }

    /**
     * Safety net: never deregister google-recaptcha-v3 (payment gateways may
     * share the handle), only dequeue on checkout / order-received.
     */
    public static function dequeueRecaptchaOnCheckout(): void
    {
        if (! function_exists('is_checkout')) {
            return;
        }
        $on_checkout = is_checkout() || (function_exists('is_order_received_page') && is_order_received_page());
        if (! $on_checkout) {
            return;
        }
        wp_dequeue_script('google-recaptcha-v3');
    }

    public static function getRecaptchaSiteKey(): string
    {
        $key = function_exists('get_field') ? get_field('form_recaptcha_site_key', 'option') : '';
        return is_string($key) ? trim($key) : '';
    }

    private static function getRecaptchaSecretKey(): string
    {
        $key = function_exists('get_field') ? get_field('form_recaptcha_secret_key', 'option') : '';
        return is_string($key) ? trim($key) : '';
    }

    /**
     * Skip verification when no secret is configured (dev). Honeypot still runs.
     */
    private static function verifyRecaptcha(): bool
    {
        $secret = self::getRecaptchaSecretKey();
        if (empty($secret)) {
            return true;
        }

        $token = isset($_POST[self::RECAPTCHA_TOKEN_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::RECAPTCHA_TOKEN_FIELD]))
            : '';

        if (empty($token)) {
            return false;
        }

        $response = wp_remote_post(self::RECAPTCHA_VERIFY_URL, [
            'timeout' => 10,
            'body'    => [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => self::getClientIp(),
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (! is_array($data) || empty($data['success'])) {
            return false;
        }

        $score = isset($data['score']) ? (float) $data['score'] : 0.0;
        return $score >= self::RECAPTCHA_SCORE_THRESHOLD;
    }

    private static function getClientIp(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $ip = sanitize_text_field(wp_unslash($_SERVER[$key]));
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }

    private static function sanitizeInputs(): array
    {
        $phone_raw = wp_unslash($_POST['cf_phone'] ?? '');
        $phone = preg_replace('/[^0-9+]/', '', $phone_raw);

        $event_type_raw = isset($_POST['cf_event_type']) ? (int) $_POST['cf_event_type'] : 0;
        $event_type = self::whitelistEventType($event_type_raw);

        $client_type_raw = sanitize_text_field(wp_unslash($_POST['cf_client_type'] ?? ''));
        $client_type = in_array($client_type_raw, ['particulier', 'entreprise'], true) ? $client_type_raw : '';

        return [
            'name'        => sanitize_text_field(wp_unslash($_POST['cf_name'] ?? '')),
            'email'       => sanitize_email(wp_unslash($_POST['cf_email'] ?? '')),
            'phone'       => sanitize_text_field($phone),
            'message'     => sanitize_textarea_field(wp_unslash($_POST['cf_message'] ?? '')),
            'event_type'  => $event_type,
            'client_type' => $client_type,
        ];
    }

    /**
     * Only accept event_type values that are in the LP relationship. Anything
     * else silently falls back to 0 (not sent).
     */
    private static function whitelistEventType(int $event_type_id): int
    {
        if ($event_type_id <= 0) {
            return 0;
        }

        $pages = function_exists('get_field') ? get_field('form_lp_pages', 'option') : null;
        if (empty($pages) || ! is_array($pages)) {
            return 0;
        }

        foreach ($pages as $page) {
            $id = $page instanceof \WP_Post ? (int) $page->ID : (int) ($page['ID'] ?? $page);
            if ($id === $event_type_id) {
                return $event_type_id;
            }
        }

        return 0;
    }

    private static function validateInputs(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'name';
        }

        if (empty($data['email']) || ! is_email($data['email'])) {
            $errors[] = 'email';
        }

        if (empty($data['phone']) || ! preg_match('/^\+?[0-9]{8,15}$/', $data['phone'])) {
            $errors[] = 'phone';
        }

        if (empty($data['message'])) {
            $errors[] = 'message';
        }

        return $errors;
    }

    private static function sendEmail(array $data): bool
    {
        $email_to = function_exists('get_field') ? get_field('form_email_to', 'option') : '';
        $to = (! empty($email_to) && is_email($email_to)) ? sanitize_email($email_to) : get_option('admin_email');
        $subject = sprintf('Nouveau message de %s', esc_html($data['name']));

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', get_option('blogname'), get_option('admin_email')),
            sprintf('Reply-To: %s <%s>', esc_html($data['name']), sanitize_email($data['email'])),
        ];

        $event_label = '';
        if (! empty($data['event_type'])) {
            $event_label = get_the_title($data['event_type']);
        }

        $client_label = '';
        if ($data['client_type'] === 'particulier') {
            $client_label = 'Particulier';
        } elseif ($data['client_type'] === 'entreprise') {
            $client_label = 'Entreprise';
        }

        $rows = '';
        $rows .= self::emailRow('Nom', esc_html($data['name']));
        $rows .= self::emailRow('Email', sprintf('<a href="mailto:%1$s">%1$s</a>', esc_attr($data['email'])));
        $rows .= self::emailRow('Téléphone', sprintf('<a href="tel:%1$s">%1$s</a>', esc_attr($data['phone'])));
        if ($event_label !== '') {
            $rows .= self::emailRow('Type d\'évènement', esc_html($event_label));
        }
        if ($client_label !== '') {
            $rows .= self::emailRow('Type de client', esc_html($client_label));
        }

        $message_body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
            . '<table cellspacing="0" cellpadding="8" border="0">'
            . $rows
            . '<tr><td colspan="2"><strong>Message :</strong></td></tr>'
            . '<tr><td colspan="2">' . nl2br(esc_html($data['message'])) . '</td></tr>'
            . '</table>'
            . '</body></html>';

        return wp_mail($to, $subject, $message_body, $headers);
    }

    private static function emailRow(string $label, string $value): string
    {
        return '<tr><td><strong>' . esc_html($label) . ' :</strong></td><td>' . $value . '</td></tr>';
    }

    private static function getFormAnchor(): string
    {
        return '#section-form-title';
    }

    /**
     * Resolve the redirect target: hidden cf_redirect (validated against the
     * site), then HTTP_REFERER, then home_url().
     */
    private static function getRedirectBase(): string
    {
        $home = home_url('/');

        $candidate = isset($_POST['cf_redirect']) ? esc_url_raw(wp_unslash($_POST['cf_redirect'])) : '';
        if ($candidate !== '') {
            $safe = wp_validate_redirect($candidate, '');
            if ($safe !== '') {
                return $safe;
            }
        }

        $referer = wp_get_referer();
        if ($referer) {
            return $referer;
        }

        return $home;
    }

    private static function buildRedirectUrl(array $params): string
    {
        $base = self::getRedirectBase();
        $base = strtok($base, '#');
        $sep  = (strpos($base, '?') === false) ? '?' : '&';

        return $base . $sep . http_build_query($params) . self::getFormAnchor();
    }

    private static function redirectValidationErrors(array $errors): void
    {
        $params = ['form_errors' => implode(',', $errors)];

        $preserve = ['cf_name', 'cf_email', 'cf_phone', 'cf_message', 'cf_event_type', 'cf_client_type'];
        foreach ($preserve as $key) {
            if (! isset($_POST[$key])) {
                continue;
            }
            $raw = wp_unslash($_POST[$key]);
            if ($key === 'cf_email') {
                $params[$key] = sanitize_email($raw);
            } elseif ($key === 'cf_phone') {
                $params[$key] = sanitize_text_field(preg_replace('/[^0-9+]/', '', $raw));
            } elseif ($key === 'cf_message') {
                $params[$key] = sanitize_textarea_field($raw);
            } elseif ($key === 'cf_event_type') {
                $params[$key] = (int) $raw;
            } else {
                $params[$key] = sanitize_text_field($raw);
            }
        }

        wp_safe_redirect(self::buildRedirectUrl($params));
        exit;
    }

    private static function redirectError(string $error_type): void
    {
        wp_safe_redirect(self::buildRedirectUrl(['form_error' => sanitize_key($error_type)]));
        exit;
    }

    private static function redirectSuccess(): void
    {
        wp_safe_redirect(self::buildRedirectUrl(['form_success' => 1]));
        exit;
    }
}
