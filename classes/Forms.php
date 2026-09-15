<?php

namespace Theme_base;

/**
 * Formulaires publics : contact et demande de devis.
 *
 * Deux formulaires distincts, un seul traitement :
 *
 * - « Une question avant de réserver ? » (§13.2, R24) : type d'événement,
 *   choix particulier/entreprise, nom ou nom de l'entreprise, email,
 *   téléphone, message. Nom, email et message sont obligatoires ; téléphone
 *   et qualification restent facultatifs.
 * - « Demander un devis » au-delà de 24 heures (§8.5, R06) : nom, prénom,
 *   nom de l'entreprise, téléphone, email, description libre, produit
 *   intéressé, nombre d'heures et type d'événement.
 *
 * Les deux transmettent une demande par e-mail et affichent un accusé de
 * réception. Aucun des deux ne crée de commande, de réservation ferme ou
 * d'occupation de stock : le traitement manuel décrit en §4.3 prend le relais.
 *
 * Le choix particulier/entreprise n'est qu'une information de contact : il ne
 * change ni les produits, ni les options, ni le checkout. Il est présélectionné
 * sur « Entreprise » quand le formulaire est affiché sur une page d'atterrissage
 * entreprise, désignée depuis la page d'options ACF « Formulaires ».
 *
 * Destinataire : le champ ACF, sinon l'adresse d'administration du site.
 */
class Forms
{
    /** Slug de la page d'options ACF. */
    public const OPTIONS_SLUG = 'lumiboo-forms';

    /** Paramètre d'URL portant le jeton du message de retour. */
    private const NOTICE_QUERY = 'lum_notice';

    /** Préfixe du transient stockant un message de retour. */
    private const NOTICE_PREFIX = 'lumiboo_form_notice_';

    /** Durée de vie d'un message de retour, en secondes. */
    private const NOTICE_TTL = 300;

    /**
     * Réglages d'envoi et d'anti-spam, avec leur valeur par défaut.
     *
     * Tous sont surchargeables depuis la page d'options : le passage de
     * l'environnement local à l'hébergement ne demande aucune modification de
     * code. Le défaut s'applique tant que le champ reste vide.
     *
     * @var array<string, int|float>
     */
    private const SETTINGS = [
        // Délai minimal entre deux envois depuis une même adresse IP.
        'throttle_delay'      => 30,
        // Nombre maximal de soumissions par heure et par adresse IP.
        'throttle_max'        => 20,
        // Score reCAPTCHA v3 en dessous duquel la demande est refusée.
        'recaptcha_threshold' => 0.5,
    ];

    public function register(): void
    {
        add_action('acf/init', [$this, 'registerOptionsPage']);

        foreach (array_keys(self::definitions()) as $form) {
            $action = 'lumiboo_form_' . $form;

            add_action('admin_post_' . $action, [$this, 'handle']);
            add_action('admin_post_nopriv_' . $action, [$this, 'handle']);
        }
    }

    public function registerOptionsPage(): void
    {
        if (! function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'      => __('Formulaires', 'theme_base'),
            'menu_title'      => __('Formulaires', 'theme_base'),
            'menu_slug'       => self::OPTIONS_SLUG,
            'capability'      => 'edit_posts',
            'redirect'        => false,
            'autoload'        => false,
            'update_button'   => __('Mise à jour', 'theme_base'),
            'updated_message' => __('Options mises à jour', 'theme_base'),
        ]);
    }

    /* ---------------------------------------------------------------------
     * Définition des formulaires
     * ------------------------------------------------------------------ */

    /**
     * Description complète des deux formulaires.
     *
     * Le rendu, la validation et le corps de l'e-mail sont tous dérivés d'ici :
     * un champ ajouté au tableau est affiché, contrôlé et transmis sans autre
     * modification. Les listes de choix sont résolues par `choices()` et
     * revalidées à la réception : une valeur absente de la liste est refusée.
     *
     * L'obligation champ par champ du devis reste à confirmer (§8.5). Le
     * filtre `lumiboo_form_fields` permet de l'ajuster sans toucher au thème.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function definitions(): array
    {
        return [
            'contact' => [
                'subject' => __('Nouvelle demande de contact', 'theme_base'),
                'fields'  => [
                    'event_type' => ['type' => 'select', 'label' => __('Type d’événement', 'theme_base'), 'choices' => 'event_types'],
                    'audience'   => ['type' => 'radio', 'label' => __('Particulier ou entreprise', 'theme_base'), 'choices' => 'audiences'],
                    'name'       => ['type' => 'text', 'label' => __('Nom ou nom de l’entreprise', 'theme_base'), 'required' => true, 'max' => 120],
                    'email'      => ['type' => 'email', 'label' => __('Email', 'theme_base'), 'required' => true, 'max' => 120],
                    'phone'      => ['type' => 'tel', 'label' => __('Téléphone', 'theme_base'), 'max' => 40],
                    'message'    => ['type' => 'textarea', 'label' => __('Message', 'theme_base'), 'required' => true, 'max' => 3000],
                ],
            ],
            'devis' => [
                'subject' => __('Nouvelle demande de devis', 'theme_base'),
                'fields'  => [
                    'last_name'  => ['type' => 'text', 'label' => __('Nom', 'theme_base'), 'required' => true, 'max' => 80],
                    'first_name' => ['type' => 'text', 'label' => __('Prénom', 'theme_base'), 'required' => true, 'max' => 80],
                    'company'    => ['type' => 'text', 'label' => __('Nom de l’entreprise', 'theme_base'), 'max' => 120],
                    'email'      => ['type' => 'email', 'label' => __('Email', 'theme_base'), 'required' => true, 'max' => 120],
                    'phone'      => ['type' => 'tel', 'label' => __('Téléphone', 'theme_base'), 'max' => 40],
                    'product'    => ['type' => 'select', 'label' => __('Produit qui vous intéresse', 'theme_base'), 'choices' => 'products'],
                    'event_type' => ['type' => 'select', 'label' => __('Type d’événement', 'theme_base'), 'choices' => 'event_types'],
                    'hours'      => ['type' => 'number', 'label' => __('Nombre d’heures', 'theme_base'), 'min' => 24, 'max' => 720],
                    'message'    => ['type' => 'textarea', 'label' => __('Description de votre événement', 'theme_base'), 'required' => true, 'max' => 3000],
                ],
            ],
        ];
    }

    /**
     * Champs d'un formulaire, avec leurs valeurs par défaut complétées.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $form): array
    {
        $definition = self::definitions()[$form] ?? null;

        if ($definition === null) {
            return [];
        }

        $fields = [];

        foreach ($definition['fields'] as $name => $field) {
            $fields[$name] = array_merge([
                'type'     => 'text',
                'label'    => '',
                'required' => false,
                'max'      => 255,
                'min'      => 0,
                'choices'  => '',
            ], $field);
        }

        /**
         * Permet d'ajuster les obligations et les longueurs sans dupliquer le
         * formulaire, en attendant l'arbitrage champ par champ du devis.
         *
         * @param array<string, array<string, mixed>> $fields
         */
        return (array) apply_filters('lumiboo_form_fields', $fields, $form);
    }

    public static function exists(string $form): bool
    {
        return isset(self::definitions()[$form]);
    }

    /** Valeur du champ `action` attendue par `admin-post.php`. */
    public static function action(string $form): string
    {
        return 'lumiboo_form_' . $form;
    }

    /** Ancre de retour, pour revenir sur le formulaire après l'envoi. */
    public static function anchor(string $form): string
    {
        return 'form-' . $form;
    }

    /* ---------------------------------------------------------------------
     * Listes de choix
     * ------------------------------------------------------------------ */

    /**
     * Valeurs autorisées d'une liste, sous la forme clé => libellé.
     *
     * Ces listes servent à la fois au rendu et au contrôle de la soumission :
     * il n'existe donc aucun cas où une valeur affichée et une valeur acceptée
     * divergent.
     *
     * @return array<string, string>
     */
    public static function choices(string $list): array
    {
        switch ($list) {
            case 'audiences':
                return [
                    'particulier' => __('Particulier', 'theme_base'),
                    'entreprise'  => __('Entreprise', 'theme_base'),
                ];
            case 'event_types':
                return self::eventTypes();
            case 'products':
                return self::products();
            default:
                return [];
        }
    }

    /**
     * Types d'événement proposés.
     *
     * Les cinq usages présentés sur les fiches produit (§13.1) servent de
     * défaut tant que la page d'options n'a pas été remplie.
     *
     * @return array<string, string>
     */
    private static function eventTypes(): array
    {
        $rows    = function_exists('get_field') ? get_field('forms_event_types', 'option') : null;
        $choices = [];

        foreach ((array) $rows as $row) {
            $label = is_array($row) ? (string) ($row['label'] ?? '') : '';

            if ($label !== '') {
                $choices[sanitize_key(remove_accents($label))] = $label;
            }
        }

        if ($choices === []) {
            $choices = [
                'mariage'       => __('Mariage', 'theme_base'),
                'anniversaire'  => __('Anniversaire', 'theme_base'),
                'baby-shower'   => __('Baby shower', 'theme_base'),
                'entreprise'    => __('Entreprise', 'theme_base'),
                'evenement-public' => __('Événement public', 'theme_base'),
                'autre'         => __('Autre', 'theme_base'),
            ];
        }

        return (array) apply_filters('lumiboo_form_event_types', $choices);
    }

    /**
     * Produits proposés dans la demande de devis.
     *
     * La liste suit le catalogue publié : elle n'a pas à être maintenue une
     * seconde fois dans la page d'options.
     *
     * @return array<string, string>
     */
    private static function products(): array
    {
        $choices = [];

        if (function_exists('wc_get_products')) {
            $products = wc_get_products([
                'status'  => 'publish',
                'limit'   => 20,
                'orderby' => 'menu_order',
                'order'   => 'ASC',
            ]);

            foreach ($products as $product) {
                if ($product instanceof \WC_Product) {
                    $choices[(string) $product->get_id()] = $product->get_name();
                }
            }
        }

        return (array) apply_filters('lumiboo_form_products', $choices);
    }

    /* ---------------------------------------------------------------------
     * Contexte d'affichage
     * ------------------------------------------------------------------ */

    /**
     * Qualification présélectionnée : « entreprise » sur une page
     * d'atterrissage entreprise, « particulier » partout ailleurs.
     *
     * Les pages concernées sont désignées depuis la page d'options ACF. Une
     * sous-page hérite du contexte de son parent, pour qu'une arborescence
     * entreprise n'ait pas à être listée page par page.
     */
    public static function defaultAudience(): string
    {
        $pages   = self::companyPages();
        $current = get_queried_object_id();

        $lineage = $current ? array_merge([$current], array_map('intval', get_post_ancestors($current))) : [];

        $audience = array_intersect($lineage, $pages) !== []
            ? 'entreprise'
            : 'particulier';

        return (string) apply_filters('lumiboo_form_default_audience', $audience);
    }

    /**
     * Identifiants des pages d'atterrissage entreprise.
     *
     * @return array<int, int>
     */
    private static function companyPages(): array
    {
        $rows = function_exists('get_field') ? get_field('forms_company_pages', 'option') : null;
        $ids  = [];

        foreach ((array) $rows as $row) {
            $id = $row instanceof \WP_Post ? (int) $row->ID : (int) $row;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Produit servant de contexte à la demande, quand le formulaire est
     * affiché sur une fiche (R24 : « reprendre le produit comme contexte »).
     */
    public static function contextProduct(): string
    {
        $id = function_exists('is_product') && is_product() ? get_queried_object_id() : 0;

        return $id > 0 ? (string) $id : '';
    }

    /* ---------------------------------------------------------------------
     * Textes pilotés par ACF
     * ------------------------------------------------------------------ */

    /**
     * Libellé éditable depuis la page d'options « Formulaires ».
     * Le défaut du code n'est qu'un repli avant l'import du JSON ACF.
     */
    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('forms_' . $key, 'option');

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Réglage numérique de la page d'options, ou son défaut.
     *
     * @return int|float
     */
    private static function setting(string $key)
    {
        $default = self::SETTINGS[$key] ?? 0;
        $value   = function_exists('get_field') ? get_field('forms_' . $key, 'option') : null;

        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }

        return is_float($default) ? (float) $value : (int) $value;
    }

    /**
     * Destinataire des demandes.
     *
     * L'adresse d'administration reste le défaut : elle garantit qu'aucune
     * demande ne se perd tant que Lumiboo n'a pas fourni l'adresse
     * opérationnelle définitive (§8.5, §13.2).
     */
    public static function recipient(string $form): string
    {
        $address = self::text($form . '_recipient', self::text('recipient', ''));

        if (! is_email($address)) {
            $address = (string) get_option('admin_email');
        }

        return (string) apply_filters('lumiboo_form_recipient', $address, $form);
    }

    /**
     * Adresses en copie, saisies séparées par des virgules.
     *
     * @return array<int, string>
     */
    private static function copies(): array
    {
        $addresses = [];

        foreach (explode(',', self::text('copy', '')) as $address) {
            $address = sanitize_email(trim($address));

            if (is_email($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Expéditeur des messages.
     *
     * Laissé vide, WordPress utilise `wordpress@domaine`. Chez l'hébergeur,
     * renseigner une adresse du domaine réellement autorisée à envoyer
     * (SPF/DKIM) évite que les demandes finissent en indésirable. L'adresse
     * du visiteur n'est jamais utilisée ici : elle serait rejetée par les
     * mêmes contrôles et rendrait l'en-tête falsifiable.
     *
     * @return array<int, string>
     */
    private static function fromHeaders(): array
    {
        $email = sanitize_email(self::text('from_email', ''));

        if (! is_email($email)) {
            return [];
        }

        $name = sanitize_text_field(self::text('from_name', ''));

        return [$name !== '' ? sprintf('From: %s <%s>', $name, $email) : 'From: ' . $email];
    }

    /* ---------------------------------------------------------------------
     * reCAPTCHA v3 — optionnel
     * ------------------------------------------------------------------ */

    /**
     * La protection ne s'active que si les deux clés sont renseignées.
     *
     * Elle reste donc absente en local et se met en route le jour où les
     * clés sont saisies chez l'hébergeur, sans redéploiement.
     *
     * @return array{site: string, secret: string}|null
     */
    private static function recaptchaKeys(): ?array
    {
        $site   = trim(self::text('recaptcha_site_key', ''));
        $secret = trim(self::text('recaptcha_secret', ''));

        return $site !== '' && $secret !== '' ? ['site' => $site, 'secret' => $secret] : null;
    }

    /**
     * Charge reCAPTCHA au moment où un formulaire est rendu.
     *
     * L'appel depuis le gabarit évite de servir le script de Google sur
     * toutes les pages du site alors que deux écrans seulement en ont besoin.
     *
     * @return string Clé publique, ou chaîne vide si la protection est inactive.
     */
    public static function enqueueRecaptcha(): string
    {
        $keys = self::recaptchaKeys();

        if ($keys === null) {
            return '';
        }

        $handle = THEME_HANDLE . '-recaptcha';

        if (wp_script_is($handle, 'enqueued')) {
            return $keys['site'];
        }

        wp_enqueue_script(
            $handle,
            'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($keys['site']),
            [],
            null,
            true
        );

        // Le jeton expire en deux minutes : il est demandé à l'envoi, pas au
        // chargement de la page, sinon un visiteur qui prend son temps
        // verrait sa demande refusée.
        wp_add_inline_script($handle, sprintf(
            'document.querySelectorAll(\'input[name="lum_recaptcha"]\').forEach(function (field) {'
            . 'var form = field.closest("form");'
            . 'if (!form) { return; }'
            . 'form.addEventListener("submit", function (event) {'
            . 'if (form.dataset.recaptchaReady === "1") { return; }'
            . 'event.preventDefault();'
            . 'grecaptcha.ready(function () {'
            . 'grecaptcha.execute(%s, { action: field.dataset.action }).then(function (token) {'
            . 'field.value = token;'
            . 'form.dataset.recaptchaReady = "1";'
            . 'if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }'
            . '});});});});',
            wp_json_encode($keys['site'])
        ));

        return $keys['site'];
    }

    /**
     * Contrôle le jeton auprès de Google.
     *
     * Un jeton absent, refusé ou dont le score est trop bas fait échouer
     * l'envoi. En revanche, une panne de réseau laisse passer la demande :
     * une indisponibilité chez Google ne doit pas fermer le formulaire.
     */
    private function passesRecaptcha(): bool
    {
        $keys = self::recaptchaKeys();

        if ($keys === null) {
            return true;
        }

        $token = isset($_POST['lum_recaptcha']) ? sanitize_text_field(wp_unslash($_POST['lum_recaptcha'])) : '';

        if ($token === '') {
            return false;
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body'    => [
                'secret'   => $keys['secret'],
                'response' => $token,
                'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '',
            ],
        ]);

        if (is_wp_error($response)) {
            return true;
        }

        $result = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($result) || empty($result['success'])) {
            return false;
        }

        return (float) ($result['score'] ?? 0) >= (float) self::setting('recaptcha_threshold');
    }

    /* ---------------------------------------------------------------------
     * Traitement de la soumission
     * ------------------------------------------------------------------ */

    /**
     * Point d'entrée unique des deux formulaires.
     *
     * L'ordre des contrôles est volontaire : le formulaire visé, puis le
     * jeton de sécurité, puis le piège à robots, puis la limite d'envoi, et
     * seulement ensuite la validation métier. Un robot ne déclenche donc
     * jamais ni envoi d'e-mail, ni écriture de transient de saisie.
     */
    public function handle(): void
    {
        $form = isset($_POST['lum_form']) ? sanitize_key(wp_unslash($_POST['lum_form'])) : '';

        if (! self::exists($form)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        $back  = $this->backUrl();
        $nonce = isset($_POST['lum_nonce']) ? sanitize_text_field(wp_unslash($_POST['lum_nonce'])) : '';

        // Le jeton couvre l'origine de la requête. Un échec est le plus souvent
        // une page restée ouverte trop longtemps : on le dit, sans écran blanc.
        if (! wp_verify_nonce($nonce, self::action($form))) {
            $this->respond($back, $form, 'error', self::text(
                'error_expired',
                __('Votre session a expiré. Merci de renvoyer le formulaire.', 'theme_base')
            ));
        }

        // Piège à robots : le champ est masqué et doit rester vide. Un envoi
        // rempli reçoit la même confirmation qu'un envoi valide, pour ne rien
        // apprendre à l'émetteur, mais n'est pas transmis.
        if (isset($_POST['lum_hp']) && trim((string) wp_unslash($_POST['lum_hp'])) !== '') {
            $this->respond($back, $form, 'success', self::successMessage($form));
        }

        if (! $this->withinRateLimit($form)) {
            $this->respond($back, $form, 'error', self::text(
                'error_throttled',
                __('Une demande vient d’être envoyée depuis cet appareil. Merci de patienter quelques instants.', 'theme_base')
            ));
        }

        $this->markAttempt($form);

        if (! $this->passesRecaptcha()) {
            $this->respond($back, $form, 'error', self::text(
                'error_recaptcha',
                __('Votre envoi n’a pas pu être vérifié. Merci de réessayer.', 'theme_base')
            ));
        }

        $submission = $this->validate($form, (array) wp_unslash($_POST));

        if ($submission['errors'] !== []) {
            $this->respond(
                $back,
                $form,
                'error',
                self::text('error_invalid', __('Certains champs doivent être corrigés.', 'theme_base')),
                $submission['errors'],
                $submission['values']
            );
        }

        if (! $this->send($form, $submission['values'])) {
            $this->respond(
                $back,
                $form,
                'error',
                self::text('error_mail', __('L’envoi a échoué. Merci de réessayer ou de nous contacter directement.', 'theme_base')),
                [],
                $submission['values']
            );
        }

        $this->markSent($form);

        $this->respond($back, $form, 'success', self::successMessage($form));
    }

    private static function successMessage(string $form): string
    {
        return self::text(
            $form . '_success',
            __('Merci, votre demande est bien reçue. Nous vous répondons dans les meilleurs délais.', 'theme_base')
        );
    }

    /**
     * Contrôle et nettoie une soumission.
     *
     * Chaque valeur est ramenée au type déclaré : une liste n'accepte que ses
     * propres clés, un email doit être un email, un numéro ne garde que les
     * caractères d'un numéro. Rien n'est repris tel quel depuis la requête.
     *
     * @param array<string, mixed> $input Valeurs déjà déséchappées.
     * @return array{values: array<string, string>, errors: array<string, string>}
     */
    private function validate(string $form, array $input): array
    {
        $values = [];
        $errors = [];

        foreach (self::fields($form) as $name => $field) {
            $raw = isset($input[$name]) && is_scalar($input[$name]) ? (string) $input[$name] : '';
            $raw = trim($raw);

            switch ($field['type']) {
                case 'email':
                    $value = sanitize_email($raw);

                    if ($value !== '' && ! is_email($value)) {
                        $errors[$name] = sprintf(
                            /* translators: %s: field label */
                            __('%s : adresse invalide.', 'theme_base'),
                            $field['label']
                        );
                        $value = '';
                    }
                    break;

                case 'tel':
                    $value = sanitize_text_field($raw);

                    if ($value !== '' && ! preg_match('/^[0-9+().\-\/\s]{6,40}$/', $value)) {
                        $errors[$name] = sprintf(
                            /* translators: %s: field label */
                            __('%s : numéro invalide.', 'theme_base'),
                            $field['label']
                        );
                        $value = '';
                    }
                    break;

                case 'number':
                    $value = $raw === '' ? '' : (string) absint($raw);

                    if ($value !== '' && ((int) $value < (int) $field['min'] || (int) $value > (int) $field['max'])) {
                        $errors[$name] = sprintf(
                            /* translators: 1: field label, 2: minimum, 3: maximum */
                            __('%1$s : indiquez une valeur entre %2$d et %3$d.', 'theme_base'),
                            $field['label'],
                            (int) $field['min'],
                            (int) $field['max']
                        );
                        $value = '';
                    }
                    break;

                case 'select':
                case 'radio':
                    $value = sanitize_key($raw);

                    if ($value !== '' && ! array_key_exists($value, self::choices((string) $field['choices']))) {
                        $value = '';
                    }
                    break;

                case 'textarea':
                    $value = sanitize_textarea_field($raw);
                    break;

                default:
                    $value = sanitize_text_field($raw);
                    break;
            }

            if ($value !== '' && $field['type'] !== 'number' && mb_strlen($value) > (int) $field['max']) {
                $errors[$name] = sprintf(
                    /* translators: 1: field label, 2: maximum number of characters */
                    __('%1$s : %2$d caractères maximum.', 'theme_base'),
                    $field['label'],
                    (int) $field['max']
                );
                $value = mb_substr($value, 0, (int) $field['max']);
            }

            if ($value === '' && $field['required'] && ! isset($errors[$name])) {
                $errors[$name] = sprintf(
                    /* translators: %s: field label */
                    __('%s : ce champ est obligatoire.', 'theme_base'),
                    $field['label']
                );
            }

            $values[$name] = $value;
        }

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * Transmet la demande au destinataire.
     *
     * L'expéditeur reste celui du site : utiliser l'adresse du visiteur ferait
     * échouer SPF/DKIM et rendrait l'en-tête falsifiable. L'adresse saisie
     * n'apparaît qu'en `Reply-To`, après validation — aucune valeur libre
     * n'entre dans un en-tête ou dans l'objet du message.
     *
     * @param array<string, string> $values
     */
    private function send(string $form, array $values): bool
    {
        $headers = array_merge(['Content-Type: text/plain; charset=UTF-8'], self::fromHeaders());

        if (($values['email'] ?? '') !== '') {
            $headers[] = 'Reply-To: ' . $values['email'];
        }

        foreach (self::copies() as $copy) {
            $headers[] = 'Cc: ' . $copy;
        }

        $subject = sprintf(
            '[%s] %s',
            wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES),
            (string) self::definitions()[$form]['subject']
        );

        return (bool) wp_mail(
            self::recipient($form),
            $subject,
            $this->body($form, $values),
            $headers
        );
    }

    /**
     * Corps du message, en texte brut.
     *
     * Les listes sont restituées par leur libellé : l'équipe lit « Baby
     * shower », pas la clé technique correspondante.
     *
     * @param array<string, string> $values
     */
    private function body(string $form, array $values): string
    {
        $lines = [];

        foreach (self::fields($form) as $name => $field) {
            $value = $values[$name] ?? '';

            if ($value === '') {
                continue;
            }

            if (in_array($field['type'], ['select', 'radio'], true)) {
                $value = self::choices((string) $field['choices'])[$value] ?? $value;
            }

            $lines[] = sprintf('%s : %s', $field['label'], $value);
        }

        $origin = $this->backUrl();

        $lines[] = '';
        $lines[] = sprintf(
            /* translators: %s: URL of the page the form was sent from */
            __('Envoyé depuis : %s', 'theme_base'),
            $origin
        );
        $lines[] = sprintf(
            /* translators: %s: submission date */
            __('Date : %s', 'theme_base'),
            wp_date('d.m.Y H:i')
        );

        return wp_strip_all_tags(implode("\n", $lines));
    }

    /* ---------------------------------------------------------------------
     * Limitation d'envoi
     * ------------------------------------------------------------------ */

    /**
     * Empêche l'usage du formulaire comme relais d'envoi en masse.
     *
     * Deux limites se cumulent. Le plafond horaire compte les soumissions,
     * pas les envois : pour un visiteur anonyme, tous les jetons de sécurité
     * se valent, et un robot qui poste des données invalides en boucle serait
     * sinon illimité. Le délai court, lui, ne se déclenche qu'après un envoi
     * réussi, pour qu'une saisie corrigée deux fois n'immobilise personne.
     */
    private function withinRateLimit(string $form): bool
    {
        $key = $this->throttleKey($form);

        if (get_transient('lumiboo_form_last_' . $key) !== false) {
            return false;
        }

        return (int) get_transient('lumiboo_form_count_' . $key) < (int) self::setting('throttle_max');
    }

    private function markAttempt(string $form): void
    {
        $key = 'lumiboo_form_count_' . $this->throttleKey($form);

        set_transient($key, (int) get_transient($key) + 1, HOUR_IN_SECONDS);
    }

    private function markSent(string $form): void
    {
        set_transient('lumiboo_form_last_' . $this->throttleKey($form), 1, (int) self::setting('throttle_delay'));
    }

    /**
     * L'adresse IP n'est jamais stockée en clair : seule son empreinte sert
     * de clé de comptage, et elle disparaît avec le transient.
     */
    private function throttleKey(string $form): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        return md5($form . '|' . $ip . '|' . wp_salt());
    }

    /* ---------------------------------------------------------------------
     * Message de retour
     * ------------------------------------------------------------------ */

    /**
     * Retourne sur la page d'origine avec le résultat de l'envoi.
     *
     * Le message et les valeurs ressaisies transitent par un transient
     * désigné par un jeton aléatoire, pas par l'URL : une adresse e-mail ou
     * un message ne se retrouve donc ni dans l'historique du navigateur, ni
     * dans les journaux du serveur, ni dans un en-tête `Referer`.
     *
     * @param array<string, string> $errors
     * @param array<string, string> $values
     */
    private function respond(string $url, string $form, string $type, string $message, array $errors = [], array $values = []): void
    {
        $token = strtolower(wp_generate_password(24, false, false));

        set_transient(self::NOTICE_PREFIX . $token, [
            'form'    => $form,
            'type'    => $type,
            'message' => $message,
            'errors'  => $errors,
            // Une demande transmise n'a plus à être conservée côté site.
            'values'  => $type === 'success' ? [] : $values,
        ], self::NOTICE_TTL);

        $url = add_query_arg(self::NOTICE_QUERY, $token, remove_query_arg(self::NOTICE_QUERY, $url));

        wp_safe_redirect($url . '#' . self::anchor($form));
        exit;
    }

    /**
     * Page à laquelle revenir après l'envoi.
     *
     * `wp_get_referer()` valide déjà que l'adresse appartient au site ; le
     * repli sur l'accueil couvre les navigateurs qui n'envoient pas d'en-tête.
     */
    private function backUrl(): string
    {
        $referer = wp_get_referer();

        return is_string($referer) && $referer !== '' ? $referer : home_url('/');
    }

    /**
     * Résultat du dernier envoi de ce formulaire, consommé à l'affichage.
     *
     * @return array{type: string, message: string, errors: array<string, string>, values: array<string, string>}|null
     */
    public static function notice(string $form): ?array
    {
        $token = isset($_GET[self::NOTICE_QUERY]) ? sanitize_key(wp_unslash($_GET[self::NOTICE_QUERY])) : '';

        if ($token === '') {
            return null;
        }

        $notice = get_transient(self::NOTICE_PREFIX . $token);

        if (! is_array($notice) || ($notice['form'] ?? '') !== $form) {
            return null;
        }

        delete_transient(self::NOTICE_PREFIX . $token);

        return [
            'type'    => (string) ($notice['type'] ?? 'success'),
            'message' => (string) ($notice['message'] ?? ''),
            'errors'  => (array) ($notice['errors'] ?? []),
            'values'  => (array) ($notice['values'] ?? []),
        ];
    }
}
