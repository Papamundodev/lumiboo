<?php

namespace Theme_base;

/**
 * Espace compte Lumiboo — maquette fonctionnelle.
 *
 * Toute la logique de l'espace compte et de la personnalisation vit ici :
 * rendu du tableau de bord, endpoint de personnalisation, calcul de
 * complétude, échéance J-7, uploads et sauvegarde.
 *
 * Priorité de réalisation : WooCommerce natif (endpoints, templates
 * `myaccount/*`, `wc_get_orders`, meta de commande compatible HPOS), puis les
 * plugins installés (YITH Booking pour la date d'événement), puis du code.
 *
 * Les textes, libellés, images et jeux de démonstration sont pilotés par la
 * page d'options ACF « Espace compte ». Les valeurs par défaut du code ne
 * servent qu'à faire fonctionner l'écran avant l'import du JSON ACF.
 */
class Account
{
    /** Endpoint WooCommerce de la page de personnalisation. */
    public const ENDPOINT = 'personnalisation';

    /** Slug de la page d'options ACF. */
    public const OPTIONS_SLUG = 'lumiboo-account';

    /** Préfixe des meta de personnalisation stockées sur la commande. */
    private const META_PREFIX = '_lumiboo_perso_';

    /** Préfixe des meta utilisateur utilisées par les dossiers de démonstration. */
    private const FIXTURE_PREFIX = '_lumiboo_fixture_perso_';

    /** Préfixe des meta décrivant le dossier, renseignées par l'équipe. */
    private const DOSSIER_PREFIX = '_lumiboo_dossier_';

    /** Préfixe des meta de documents rattachés à une commande. */
    private const DOC_PREFIX = '_lumiboo_doc_';

    /** Marque un document supprimé au terme de sa durée de conservation. */
    private const DOC_EXPIRED_PREFIX = '_lumiboo_doc_expired_';

    /** Clé du message flash affiché après une sauvegarde. */
    private const NOTICE_KEY = 'lumiboo_account_notice';

    /** Marque un compte dont le mot de passe reste à définir. */
    private const PASSWORD_PENDING = '_lumiboo_password_pending';

    /**
     * Fuseau de référence pour J-7 et le classement en cours / passé.
     * Hypothèse H02 de la spécification, à confirmer par Lumiboo.
     */
    private const TIMEZONE = 'Europe/Zurich';

    /**
     * Champs de personnalisation, avec leur type de stockage.
     *
     * @var array<string, string>
     */
    private const PERSO_FIELDS = [
        'template'     => 'text',
        'logo_mode'    => 'text',
        'logo_file'    => 'file',
        'text_mode'    => 'text',
        'text'         => 'text',
        'welcome_file' => 'file',
        'rear_file'    => 'file',
        'requests'     => 'textarea',
    ];

    /**
     * Pièces privées d'une personnalisation.
     *
     * Elles ne sont pas des médias du site : elles vivent hors de la racine
     * publique et ne sont lues que par les points d'entrée contrôlés de cette
     * classe (§10.8).
     *
     * @var array<string, string> Nature de la pièce => champ de stockage.
     */
    public const PERSO_FILES = [
        'logo'    => 'logo_file',
        'welcome' => 'welcome_file',
        'rear'    => 'rear_file',
    ];

    /**
     * Champs du dossier saisis par l'équipe sur la commande.
     *
     * Ils décrivent ce que la carte du compte doit afficher : choix
     * d'impressions, options achetées et acheminement retenu (§10.3, §12.1).
     * Leur dérivation automatique depuis la configuration du produit dépend
     * des fiches Premium et Studio, qui ne sont pas encore réalisées ; en
     * attendant, ils couvrent aussi les commandes créées manuellement sur
     * devis (§4.3).
     *
     * @var array<string, string>
     */
    public const DOSSIER_FIELDS = [
        'prints'            => 'text',
        'has_roll'          => 'bool',
        'has_studio_option' => 'bool',
        'shipping_mode'     => 'text',
        'location'          => 'text',
    ];

    /**
     * Documents rattachés à une commande (§12.3).
     *
     * `link` : adresse fournie par l'équipe, ouverte telle quelle.
     * `file` : pièce déposée dans la médiathèque, servie uniquement par le
     * téléchargement contrôlé de cette classe.
     *
     * @var array<string, string>
     */
    public const DOC_FIELDS = [
        'gallery' => 'link',
        'invoice' => 'link',
        'emails'  => 'file',
        'report'  => 'file',
    ];

    public function register(): void
    {
        add_action('acf/init', [$this, 'registerOptionsPage']);
        add_action('init', [$this, 'addEndpoint']);
        add_action('init', [$this, 'maybeFlushRewrites'], 20);
        add_filter('query_vars', [$this, 'addQueryVar'], 0);
        add_filter('woocommerce_account_menu_items', [$this, 'filterMenuItems']);
        add_action('woocommerce_account_' . self::ENDPOINT . '_endpoint', [$this, 'renderPersonalisation']);
        add_action('woocommerce_thankyou', [$this, 'renderOrderReceived'], 5);

        // Compte obligatoire et créé automatiquement, sans champ de mot de
        // passe au checkout : WooCommerce génère le mot de passe et envoie le
        // lien par e-mail (règle G14, réponse Q033).
        add_filter('woocommerce_checkout_registration_required', '__return_true');
        add_filter('woocommerce_checkout_registration_enabled', '__return_true');
        add_filter('option_woocommerce_registration_generate_password', [$this, 'forceGeneratedPassword']);
        add_action('woocommerce_checkout_order_processed', [$this, 'ensureCustomerAccount'], 10, 3);
        add_action('woocommerce_created_customer', [$this, 'flagPasswordPending'], 10, 3);
        add_action('after_password_reset', [$this, 'clearPasswordPending']);
        add_filter('the_title', [$this, 'filterEndpointTitle'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 20);
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 3);
        add_action('admin_post_lumiboo_save_personalisation', [$this, 'handlePersonalisation']);
        add_action('admin_post_lumiboo_save_profile', [$this, 'handleProfile']);

        // Les livrables Studio ne sont jamais servis par leur adresse de
        // médiathèque : chaque téléchargement repasse par un contrôle
        // d'identité (§10.8).
        add_action('admin_post_lumiboo_document', [$this, 'handleDocument']);
        add_action('admin_post_nopriv_lumiboo_document', [$this, 'handleDocument']);
        add_action('admin_post_lumiboo_file', [$this, 'handleFile']);
        add_action('admin_post_nopriv_lumiboo_file', [$this, 'handleFile']);

        add_action('init', [$this, 'scheduleDocumentPurge'], 30);
        add_action('lumiboo_purge_documents', [$this, 'purgeDocuments']);
    }

    /**
     * Capacité requise pour consulter et corriger un dossier depuis
     * l'administration.
     *
     * Arbitrage B06 du 14/09 : administrateurs uniquement. Les gestionnaires
     * de boutique n'accèdent donc ni à la personnalisation, ni aux fichiers
     * contenant les emails des participants.
     */
    public static function adminCapability(): string
    {
        return (string) apply_filters('lumiboo_account_admin_capability', 'manage_options');
    }

    /* ---------------------------------------------------------------------
     * Bootstrap : options ACF, endpoint, assets
     * ------------------------------------------------------------------ */

    public function registerOptionsPage(): void
    {
        if (! function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title'      => __('Espace compte', 'theme_base'),
            'menu_title'      => __('Espace compte', 'theme_base'),
            'menu_slug'       => self::OPTIONS_SLUG,
            'capability'      => 'edit_posts',
            'redirect'        => false,
            'autoload'        => false,
            'update_button'   => __('Mise à jour', 'theme_base'),
            'updated_message' => __('Options mises à jour', 'theme_base'),
        ]);
    }

    public function addEndpoint(): void
    {
        add_rewrite_endpoint(self::ENDPOINT, EP_ROOT | EP_PAGES);
    }

    /**
     * Déclare les règles de réécriture une seule fois, pour que l'endpoint
     * réponde sans passer par un enregistrement manuel des permaliens.
     */
    public function maybeFlushRewrites(): void
    {
        $version = '1';

        if (get_option('lumiboo_account_rewrites') === $version) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('lumiboo_account_rewrites', $version, false);
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public function addQueryVar(array $vars): array
    {
        $vars[] = self::ENDPOINT;

        return $vars;
    }

    /**
     * La personnalisation s'ouvre depuis la carte d'une commande, pas depuis
     * le menu. Le tableau de bord est renommé pour coller au wireframe.
     *
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public function filterMenuItems(array $items): array
    {
        unset($items[self::ENDPOINT]);

        if (isset($items['dashboard'])) {
            $items['dashboard'] = self::text('menu_dashboard', __('Mon espace Lumiboo', 'theme_base'));
        }

        return $items;
    }

    public function filterEndpointTitle(string $title, $id = null): string
    {
        if (! is_main_query() || ! in_the_loop() || ! is_account_page()) {
            return $title;
        }

        if (! $this->currentDossierId()) {
            return $title;
        }

        remove_filter('the_title', [$this, 'filterEndpointTitle'], 10);

        return self::text('perso_title', __('Personnalise ton Lumiboo !', 'theme_base'));
    }

    /**
     * `account.scss` est compilé dans le bundle principal (importé depuis
     * `main.scss`) : seul le JS a besoin d'être chargé à part. Le mode dev est
     * déduit de la source déjà enregistrée par Theme_base\Vite, pour éviter un
     * second appel HTTP au serveur HMR à chaque page.
     */
    public function enqueue(): void
    {
        if (! function_exists('is_account_page') || ! is_account_page()) {
            return;
        }

        $src = $this->isHmrRunning()
            ? THEME_HMR_HOST . '/resources/scripts/account.js'
            : $this->manifestUrl('resources/scripts/account.js');

        if ($src === '') {
            return;
        }

        wp_enqueue_script(
            THEME_HANDLE . '-account',
            $src,
            [],
            $this->isHmrRunning() ? null : THEME_VERSION,
            true
        );
    }

    public function addModuleType(string $tag, string $handle, string $src): string
    {
        if ($handle === THEME_HANDLE . '-account') {
            return str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }

    private function isHmrRunning(): bool
    {
        static $running = null;

        if ($running === null) {
            $main    = wp_scripts()->query(THEME_HANDLE . '-main');
            $running = $main && is_string($main->src) && str_starts_with($main->src, THEME_HMR_HOST);
        }

        return $running;
    }

    private function manifestUrl(string $entry): string
    {
        $path = THEME_ASSETS_PATH . '/.vite/manifest.json';
        if (! is_readable($path)) {
            return '';
        }

        $manifest = json_decode((string) file_get_contents($path), true);
        $file     = is_array($manifest) ? ($manifest[$entry]['file'] ?? '') : '';

        return $file ? THEME_ASSETS_URI . '/' . $file : '';
    }

    /* ---------------------------------------------------------------------
     * Textes pilotés par ACF
     * ------------------------------------------------------------------ */

    /**
     * Libellé éditable depuis la page d'options « Espace compte ».
     * Le défaut du code n'est qu'un repli avant l'import du JSON ACF.
     */
    public static function text(string $key, string $default = ''): string
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field('account_' . $key, 'option');

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Image éditable depuis la page d'options, retournée en tableau ACF.
     *
     * @return array<string, mixed>|null
     */
    public static function image(string $key): ?array
    {
        if (! function_exists('get_field')) {
            return null;
        }

        $value = get_field('account_' . $key, 'option');

        return is_array($value) ? $value : null;
    }

    /**
     * Modèles proposés à la personnalisation. Trois choix communs Premium et
     * Studio, conformément à la réponse Q047.
     *
     * @return array<int, array{key: string, label: string, image: int}>
     */
    public static function templates(): array
    {
        $rows = function_exists('get_field') ? get_field('account_templates', 'option') : null;

        if (! is_array($rows) || $rows === []) {
            return [
                ['key' => 'template-1', 'label' => __('Modèle 1', 'theme_base'), 'image' => 0],
                ['key' => 'template-2', 'label' => __('Modèle 2', 'theme_base'), 'image' => 0],
                ['key' => 'template-3', 'label' => __('Modèle 3', 'theme_base'), 'image' => 0],
            ];
        }

        $templates = [];
        foreach ($rows as $index => $row) {
            $key = isset($row['key']) && $row['key'] !== '' ? sanitize_key((string) $row['key']) : 'template-' . ($index + 1);

            $templates[] = [
                'key'   => $key,
                'label' => (string) ($row['label'] ?? sprintf(__('Modèle %d', 'theme_base'), $index + 1)),
                'image' => (int) ($row['image'] ?? 0),
            ];
        }

        return $templates;
    }

    /* ---------------------------------------------------------------------
     * Dossiers : commandes réelles + jeux de démonstration
     * ------------------------------------------------------------------ */

    /**
     * Statuts de commande considérés comme une réservation confirmée.
     *
     * Blocage B01 : le mapping complet des statuts WooCommerce / YITH n'a pas
     * été arbitré par le client. Ce défaut rend l'écran testable et reste
     * filtrable sans toucher au thème.
     *
     * @return array<int, string>
     */
    public static function confirmedStatuses(): array
    {
        return (array) apply_filters('lumiboo_account_confirmed_statuses', ['processing', 'completed']);
    }

    /**
     * Tous les dossiers visibles par l'utilisateur courant, triés par section.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function dossiers(): array
    {
        $dossiers = array_merge(self::realDossiers(), self::fixtureDossiers());

        $current = [];
        $past    = [];

        foreach ($dossiers as $dossier) {
            if (self::isPast($dossier)) {
                $past[] = $dossier;
                continue;
            }

            $current[] = $dossier;
        }

        usort($current, static fn($a, $b) => strcmp((string) $a['event_date'], (string) $b['event_date']));
        usort($past, static fn($a, $b) => strcmp((string) $b['event_date'], (string) $a['event_date']));

        return ['current' => $current, 'past' => $past];
    }

    /**
     * Commandes réelles du client connecté.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function realDossiers(): array
    {
        $user_id = get_current_user_id();

        if (! $user_id || ! function_exists('wc_get_orders')) {
            return [];
        }

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => self::confirmedStatuses(),
        ]);

        $dossiers = [];

        foreach ($orders as $order) {
            if (! $order instanceof \WC_Order) {
                continue;
            }

            $dossier = self::dossierFromOrder($order);

            if ($dossier !== null) {
                $dossiers[] = $dossier;
            }
        }

        return $dossiers;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function dossierFromOrder(\WC_Order $order): ?array
    {
        $product_key = '';
        $title       = '';

        foreach ($order->get_items() as $item) {
            $product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;

            if (! $product instanceof \WC_Product) {
                continue;
            }

            $name        = $product->get_name();
            $product_key = stripos($name, 'studio') !== false ? 'studio' : 'premium';
            $title       = $name;
            break;
        }

        if ($product_key === '') {
            return null;
        }

        $meta = self::readDossierMeta($order);

        // Dès que le choix d'impressions est connu, le libellé de la carte le
        // rend identifiable (§10.3) ; sinon le nom réel du produit est conservé.
        if ($meta['prints'] !== '') {
            $title = '';
        }

        return self::normalise(array_merge($meta, [
            'id'             => (string) $order->get_id(),
            'order_id'       => $order->get_id(),
            'is_fixture'     => false,
            'reference'      => $order->get_order_number(),
            'product'        => $product_key,
            'title'          => $title,
            'event_date'     => self::bookingDate($order),
            'perso'          => self::readPerso($order),
            'gallery_url'    => (string) $order->get_meta(self::DOC_PREFIX . 'gallery'),
            'invoice_url'    => (string) $order->get_meta(self::DOC_PREFIX . 'invoice'),
            'emails_url'     => self::documentUrl($order, 'emails'),
            'report_url'     => self::documentUrl($order, 'report'),
            'emails_expired' => self::isDocumentExpired($order, 'emails'),
            'report_expired' => self::isDocumentExpired($order, 'report'),
        ]));
    }

    /**
     * Champs de dossier enregistrés par l'équipe sur la commande.
     *
     * @return array<string, mixed>
     */
    public static function readDossierMeta(\WC_Order $order): array
    {
        $values = [];

        foreach (self::DOSSIER_FIELDS as $field => $type) {
            $raw = $order->get_meta(self::DOSSIER_PREFIX . $field);

            $values[$field] = $type === 'bool' ? (bool) $raw : (string) $raw;
        }

        return $values;
    }

    /**
     * Valeurs brutes des documents, telles que le formulaire d'administration
     * doit les présenter : adresse pour un lien, référence pour un fichier.
     *
     * @return array<string, mixed>
     */
    public static function readDocuments(\WC_Order $order): array
    {
        $values = [];

        foreach (self::DOC_FIELDS as $doc => $type) {
            $raw = $order->get_meta(self::DOC_PREFIX . $doc);

            $values[$doc] = $type === 'file'
                ? PrivateFiles::normalise($raw)
                : (string) $raw;
        }

        return $values;
    }

    /**
     * Adresse de téléchargement contrôlé d'un document déposé, ou chaîne vide
     * lorsque le fichier n'existe pas.
     */
    private static function documentUrl(\WC_Order $order, string $doc): string
    {
        $reference = PrivateFiles::normalise($order->get_meta(self::DOC_PREFIX . $doc));

        if ($reference === []) {
            return '';
        }

        return add_query_arg(
            [
                'action' => 'lumiboo_document',
                'order'  => $order->get_id(),
                'doc'    => $doc,
            ],
            admin_url('admin-post.php')
        );
    }

    /**
     * Adresse de consultation d'une pièce de personnalisation.
     *
     * Elle passe par le même contrôle d'identité que les livrables : aucune
     * image de dossier n'est exposée par une adresse de médiathèque.
     *
     * @param array<string, mixed> $dossier
     */
    public static function fileUrl(array $dossier, string $piece): string
    {
        $field = self::PERSO_FILES[$piece] ?? '';
        $perso = (array) ($dossier['perso'] ?? []);

        if ($field === '' || PrivateFiles::normalise($perso[$field] ?? []) === []) {
            return '';
        }

        return add_query_arg(
            [
                'action'  => 'lumiboo_file',
                'dossier' => (string) $dossier['id'],
                'piece'   => $piece,
            ],
            admin_url('admin-post.php')
        );
    }

    /**
     * Nom d'origine d'une pièce, pour l'afficher sans révéler son chemin.
     *
     * @param array<string, mixed> $dossier
     */
    public static function fileLabel(array $dossier, string $piece): string
    {
        $field = self::PERSO_FILES[$piece] ?? '';
        $perso = (array) ($dossier['perso'] ?? []);

        return $field === '' ? '' : PrivateFiles::label((array) ($perso[$field] ?? []));
    }

    /**
     * Un document retiré au terme de sa durée de conservation n'est pas un
     * document « en préparation » : les deux états sont distingués.
     */
    private static function isDocumentExpired(\WC_Order $order, string $doc): bool
    {
        return (string) $order->get_meta(self::DOC_EXPIRED_PREFIX . $doc) === '1';
    }

    /**
     * Date d'événement fournie par YITH Booking lorsqu'une réservation est
     * rattachée à la commande, sinon repli sur la date de commande.
     */
    private static function bookingDate(\WC_Order $order): string
    {
        if (function_exists('yith_wcbk')) {
            $yith = yith_wcbk();

            if (isset($yith->booking_helper) && method_exists($yith->booking_helper, 'get_bookings_by_order')) {
                $bookings = $yith->booking_helper->get_bookings_by_order($order->get_id());

                foreach ((array) $bookings as $booking) {
                    if (is_object($booking) && method_exists($booking, 'get_from')) {
                        $from = (int) $booking->get_from();

                        if ($from > 0) {
                            return gmdate('Y-m-d', $from);
                        }
                    }
                }
            }
        }

        $created = $order->get_date_created();

        return $created ? $created->date('Y-m-d') : gmdate('Y-m-d');
    }

    /**
     * Dossiers de démonstration définis dans la page d'options ACF.
     *
     * Ils couvrent les états que les commandes réelles ne produisent pas
     * encore : verrouillage J-7, documents manquants, Studio avec option,
     * commande tardive. Leur personnalisation est enregistrée en meta
     * utilisateur pour que le parcours de sauvegarde reste testable.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function fixtureDossiers(): array
    {
        $rows = function_exists('get_field') ? get_field('account_fixtures', 'option') : null;
        $rows = is_array($rows) && $rows !== [] ? $rows : self::defaultFixtures();

        $user_id  = get_current_user_id();
        $dossiers = [];

        foreach ($rows as $index => $row) {
            $id = isset($row['id']) && $row['id'] !== '' ? sanitize_key((string) $row['id']) : 'demo-' . ($index + 1);

            $saved = $user_id ? get_user_meta($user_id, self::FIXTURE_PREFIX . $id, true) : [];

            $dossiers[] = self::normalise([
                'id'                => $id,
                'order_id'          => 0,
                'is_fixture'        => true,
                'reference'         => (string) ($row['reference'] ?? strtoupper($id)),
                'product'           => ($row['product'] ?? 'premium') === 'studio' ? 'studio' : 'premium',
                'title'             => (string) ($row['title'] ?? ''),
                'prints'            => (string) ($row['prints'] ?? ''),
                'has_roll'          => ! empty($row['has_roll']),
                'has_studio_option' => ! empty($row['has_studio_option']),
                'event_date'        => self::relativeDate($row['event_date'] ?? ''),
                'shipping_mode'     => (string) ($row['shipping_mode'] ?? ''),
                'location'          => (string) ($row['location'] ?? ''),
                'gallery_url'       => (string) ($row['gallery_url'] ?? ''),
                'invoice_url'       => (string) ($row['invoice_url'] ?? ''),
                'emails_url'        => (string) ($row['emails_url'] ?? ''),
                'report_url'        => (string) ($row['report_url'] ?? ''),
                'perso'             => is_array($saved) ? $saved : [],
            ]);
        }

        return $dossiers;
    }

    /**
     * Accepte une date absolue (`2026-12-24`) ou relative (`+10 days`), pour
     * que les jeux de démonstration restent valables dans le temps.
     */
    private static function relativeDate(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return gmdate('Y-m-d');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $date = date_create_immutable($value, new \DateTimeZone(self::TIMEZONE));

        return $date ? $date->format('Y-m-d') : gmdate('Y-m-d');
    }

    /**
     * Jeux de démonstration livrés avec le thème, utilisés tant que la page
     * d'options ACF n'a pas été remplie. Ils balayent la matrice de recette.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function defaultFixtures(): array
    {
        return [
            [
                'id'            => 'demo-premium-lite',
                'reference'     => 'LUM-1041',
                'product'       => 'premium',
                'prints'        => '400',
                'event_date'    => '+30 days',
                'shipping_mode' => 'pickup',
                'location'      => 'Écublens',
            ],
            [
                'id'            => 'demo-premium-pro',
                'reference'     => 'LUM-1042',
                'product'       => 'premium',
                'prints'        => '800',
                'has_roll'      => true,
                'event_date'    => '+12 days',
                'shipping_mode' => 'delivery',
                'location'      => 'Rue du Lac 12, 1007 Lausanne',
            ],
            [
                'id'            => 'demo-studio-option',
                'reference'     => 'LUM-1043',
                'product'       => 'studio',
                'has_studio_option' => true,
                'event_date'    => '+45 days',
                'shipping_mode' => 'planzer',
                'location'      => 'Avenue de la Gare 3, 1950 Sion',
            ],
            [
                'id'            => 'demo-locked',
                'reference'     => 'LUM-1044',
                'product'       => 'premium',
                'prints'        => 'unlimited',
                'event_date'    => '+4 days',
                'shipping_mode' => 'pickup',
                'location'      => 'Genève',
            ],
            [
                'id'            => 'demo-past-full',
                'reference'     => 'LUM-0980',
                'product'       => 'studio',
                'has_studio_option' => true,
                'event_date'    => '-20 days',
                'shipping_mode' => 'planzer',
                'location'      => 'Neuchâtel',
                'gallery_url'   => 'https://kdrive.infomaniak.com/app/share/000000/demo-galerie',
                'invoice_url'   => '#',
                'emails_url'    => '#',
                'report_url'    => '#',
            ],
            [
                'id'            => 'demo-past-empty',
                'reference'     => 'LUM-0975',
                'product'       => 'premium',
                'prints'        => '400',
                'event_date'    => '-6 days',
                'shipping_mode' => 'pickup',
                'location'      => 'Fribourg',
            ],
        ];
    }

    /**
     * Complète un dossier avec ses valeurs dérivées : libellés, progression,
     * verrouillage, disponibilité des documents.
     *
     * @param array<string, mixed> $dossier
     * @return array<string, mixed>
     */
    private static function normalise(array $dossier): array
    {
        $defaults = [
            'id'                => '',
            'order_id'          => 0,
            'is_fixture'        => false,
            'reference'         => '',
            'product'           => 'premium',
            'title'             => '',
            'prints'            => '',
            'has_roll'          => false,
            'has_studio_option' => false,
            'event_date'        => gmdate('Y-m-d'),
            'shipping_mode'     => '',
            'location'          => '',
            'gallery_url'       => '',
            'invoice_url'       => '',
            'emails_url'        => '',
            'report_url'        => '',
            'emails_expired'    => false,
            'report_expired'    => false,
            'perso'             => [],
        ];

        $dossier = array_merge($defaults, $dossier);

        $dossier['perso']        = self::normalisePerso((array) $dossier['perso']);
        $dossier['return_date']  = self::addDays((string) $dossier['event_date'], 1);
        $dossier['deadline']     = self::addDays((string) $dossier['event_date'], -7);
        $dossier['title']        = $dossier['title'] !== '' ? $dossier['title'] : self::dossierTitle($dossier);
        $dossier['locked']       = self::isLocked($dossier);
        $dossier['progress']     = self::progress($dossier);
        $dossier['url']          = self::personalisationUrl((string) $dossier['id']);

        return $dossier;
    }

    /**
     * @param array<string, mixed> $dossier
     */
    private static function dossierTitle(array $dossier): string
    {
        if ($dossier['product'] === 'studio') {
            return self::text('card_title_studio', __('Lumiboo Studio', 'theme_base'));
        }

        $labels = [
            '400'       => self::text('card_title_premium_400', __('Lumiboo Premium — 400 impressions (Lite)', 'theme_base')),
            '800'       => self::text('card_title_premium_800', __('Lumiboo Premium — 800 impressions (Pro)', 'theme_base')),
            'unlimited' => self::text('card_title_premium_unlimited', __('Lumiboo Premium — impressions illimitées (Ultra)', 'theme_base')),
        ];

        return $labels[(string) $dossier['prints']] ?? self::text('card_title_premium', __('Lumiboo Premium', 'theme_base'));
    }

    /**
     * @param array<string, mixed> $dossier
     */
    public static function shippingLabel(array $dossier): string
    {
        switch ((string) $dossier['shipping_mode']) {
            case 'pickup':
                return self::text('card_shipping_pickup', __('Point de retrait', 'theme_base'));
            case 'delivery':
                return self::text('card_shipping_delivery', __('Livraison clé en main', 'theme_base'));
            case 'planzer':
                return self::text('card_shipping_planzer', __('Livraison Planzer incluse', 'theme_base'));
            default:
                return '';
        }
    }

    /* ---------------------------------------------------------------------
     * Dates, verrouillage et progression
     * ------------------------------------------------------------------ */

    private static function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone(self::TIMEZONE));
    }

    private static function addDays(string $date, int $days): string
    {
        $value = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone(self::TIMEZONE));

        if (! $value) {
            return $date;
        }

        return $value->modify(sprintf('%+d days', $days))->format('Y-m-d');
    }

    /**
     * Une réservation reste « en cours » jusqu'à sa date de retour incluse et
     * bascule en « passée » le lendemain (hypothèse H01).
     *
     * @param array<string, mixed> $dossier
     */
    public static function isPast(array $dossier): bool
    {
        return self::today()->format('Y-m-d') > (string) $dossier['return_date'];
    }

    /**
     * La modification client ferme dès 00:00 le jour J-7 (hypothèse H02).
     *
     * @param array<string, mixed> $dossier
     */
    public static function isLocked(array $dossier): bool
    {
        return self::today()->format('Y-m-d') >= (string) $dossier['deadline'];
    }

    /**
     * Étapes de la barre de progression et complétude du dossier.
     *
     * Une étape logo ou texte est résolue soit par une valeur, soit par un
     * choix explicite « Sans » (réponse Q048). Un champ vide ne vaut jamais
     * un choix « Sans ».
     *
     * @param array<string, mixed> $dossier
     * @return array{steps: array<int, array{key: string, label: string, done: bool}>, complete: bool, done: int, total: int}
     */
    public static function progress(array $dossier): array
    {
        $perso = self::normalisePerso((array) ($dossier['perso'] ?? []));

        $steps = [
            [
                'key'   => 'template',
                'label' => self::text('step_template', __('Choix du template', 'theme_base')),
                'done'  => $perso['template'] !== '',
            ],
            [
                'key'   => 'logo',
                'label' => self::text('step_logo', __('Ajout d’un logo', 'theme_base')),
                'done'  => $perso['logo_mode'] === 'without' || ($perso['logo_mode'] === 'with' && $perso['logo_file'] !== []),
            ],
            [
                'key'   => 'text',
                'label' => self::text('step_text', __('Texte personnalisé', 'theme_base')),
                'done'  => $perso['text_mode'] === 'without' || ($perso['text_mode'] === 'with' && $perso['text'] !== ''),
            ],
        ];

        if (! empty($dossier['has_studio_option'])) {
            $steps[] = [
                'key'   => 'welcome',
                'label' => self::text('step_welcome', __('Écran d’accueil', 'theme_base')),
                'done'  => $perso['welcome_file'] !== [],
            ];
            $steps[] = [
                'key'   => 'rear',
                'label' => self::text('step_rear', __('Écran arrière', 'theme_base')),
                'done'  => $perso['rear_file'] !== [],
            ];
        }

        $done     = count(array_filter($steps, static fn(array $step): bool => $step['done']));
        $complete = $done === count($steps);

        $steps[] = [
            'key'   => 'complete',
            'label' => self::text('step_complete', __('Personnalisation complétée', 'theme_base')),
            'done'  => $complete,
        ];

        return [
            'steps'    => $steps,
            'complete' => $complete,
            'done'     => $done,
            'total'    => count($steps) - 1,
        ];
    }

    /**
     * Indicateurs du haut de page. Il n'existe aucun compteur de photos :
     * un lien K-Suite ne fournit pas de nombre (réponse Q062).
     *
     * @return array<int, array{value: int, label: string}>
     */
    public static function indicators(): array
    {
        $dossiers = self::dossiers();

        $pending = 0;
        foreach ($dossiers['current'] as $dossier) {
            if (! $dossier['progress']['complete']) {
                $pending++;
            }
        }

        return [
            [
                'value' => count($dossiers['past']),
                'label' => self::text('indicator_past', __('Commandes passées', 'theme_base')),
            ],
            [
                'value' => $pending,
                'label' => self::text('indicator_pending', __('Personnalisations en attente', 'theme_base')),
            ],
        ];
    }

    /* ---------------------------------------------------------------------
     * Lecture / écriture de la personnalisation
     * ------------------------------------------------------------------ */

    /**
     * @param array<string, mixed> $perso
     * @return array<string, mixed>
     */
    private static function normalisePerso(array $perso): array
    {
        $clean = [];

        foreach (self::PERSO_FIELDS as $field => $type) {
            $value = $perso[$field] ?? '';

            if ($type === 'file') {
                $clean[$field] = PrivateFiles::normalise($value);

                continue;
            }

            $clean[$field] = is_scalar($value) ? (string) $value : '';
        }

        return $clean;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readPerso(\WC_Order $order): array
    {
        $perso = [];

        foreach (array_keys(self::PERSO_FIELDS) as $field) {
            $perso[$field] = $order->get_meta(self::META_PREFIX . $field);
        }

        return self::normalisePerso($perso);
    }

    /**
     * Enregistre les champs de dossier saisis par l'équipe.
     *
     * @param array<string, mixed> $values
     */
    public static function writeDossierMeta(\WC_Order $order, array $values): void
    {
        foreach (self::DOSSIER_FIELDS as $field => $type) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $value = $type === 'bool'
                ? (empty($values[$field]) ? '' : '1')
                : sanitize_text_field((string) $values[$field]);

            $order->update_meta_data(self::DOSSIER_PREFIX . $field, $value);
        }
    }

    /**
     * Enregistre les documents rattachés à la commande. Chaque document est
     * indépendant : en retirer un n'affecte ni les autres, ni une autre
     * commande (§12.3).
     *
     * @param array<string, mixed> $values
     */
    public static function writeDocuments(\WC_Order $order, array $values): void
    {
        foreach (self::DOC_FIELDS as $doc => $type) {
            if (! array_key_exists($doc, $values)) {
                continue;
            }

            if ($type === 'file') {
                $reference = PrivateFiles::normalise($values[$doc]);
                $previous  = PrivateFiles::normalise($order->get_meta(self::DOC_PREFIX . $doc));

                if ($previous !== [] && $previous['path'] !== ($reference['path'] ?? '')) {
                    PrivateFiles::delete($previous);
                }

                $order->update_meta_data(self::DOC_PREFIX . $doc, $reference);

                // Un nouveau dépôt annule l'état « expiré » du livrable.
                if ($reference !== []) {
                    $order->delete_meta_data(self::DOC_EXPIRED_PREFIX . $doc);
                }

                continue;
            }

            $order->update_meta_data(self::DOC_PREFIX . $doc, esc_url_raw((string) $values[$doc]));
        }
    }

    public static function personalisationUrl(string $dossier_id): string
    {
        if (! function_exists('wc_get_account_endpoint_url')) {
            return '';
        }

        return trailingslashit(wc_get_account_endpoint_url(self::ENDPOINT)) . rawurlencode($dossier_id);
    }

    /**
     * Identifiant du dossier demandé dans l'URL de personnalisation.
     */
    private function currentDossierId(): string
    {
        $value = get_query_var(self::ENDPOINT);

        return is_string($value) ? trim($value, '/') : '';
    }

    /**
     * Retrouve un dossier appartenant à l'utilisateur connecté.
     *
     * La propriété est revérifiée ici et à chaque écriture : connaître un
     * identifiant ne donne jamais accès au dossier d'un autre client.
     *
     * @return array<string, mixed>|null
     */
    public static function findDossier(string $id): ?array
    {
        if ($id === '' || ! is_user_logged_in()) {
            return null;
        }

        $dossiers = self::dossiers();

        foreach (array_merge($dossiers['current'], $dossiers['past']) as $dossier) {
            if ((string) $dossier['id'] === $id) {
                return $dossier;
            }
        }

        return null;
    }

    /* ---------------------------------------------------------------------
     * Rendu
     * ------------------------------------------------------------------ */

    public function renderDashboard(): void
    {
        get_template_part('partials/account/dashboard');
    }

    /**
     * Le mot de passe n'est jamais saisi au checkout : WooCommerce le génère
     * et envoie un lien de création par e-mail.
     */
    public function forceGeneratedPassword($value): string
    {
        return 'yes';
    }

    /**
     * Filet de sécurité : si une commande arrive sans compte rattaché, le
     * compte est créé à partir de l'adresse de facturation et l'e-mail
     * contenant le lien de mot de passe est envoyé par WooCommerce.
     *
     * Une adresse déjà utilisée n'est jamais rattachée automatiquement : le
     * client existant doit se connecter lui-même (décision Q034).
     */
    public function ensureCustomerAccount($order_id, $posted_data = [], $order = null): void
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
     * Marque un compte dont le mot de passe n'a pas encore été défini, pour
     * pouvoir l'orienter vers l'e-mail reçu plutôt que vers une connexion.
     */
    public function flagPasswordPending($customer_id, $new_customer_data = [], $password_generated = false): void
    {
        if ($password_generated) {
            update_user_meta($customer_id, self::PASSWORD_PENDING, '1');
        }
    }

    public function clearPasswordPending($user): void
    {
        if ($user instanceof \WP_User) {
            delete_user_meta($user->ID, self::PASSWORD_PENDING);
        }
    }

    /**
     * Accès à l'espace compte depuis la page de confirmation de commande.
     *
     * Trois situations distinctes :
     * - le client est connecté et la commande lui appartient : accès direct ;
     * - un compte a été créé au checkout mais la session n'est pas ouverte :
     *   le mot de passe se définit depuis le lien reçu par e-mail (Q033) ;
     * - la commande n'est rattachée à aucun compte : le client est invité à
     *   se connecter avec l'adresse utilisée pour la commande.
     */
    public function renderOrderReceived($order_id): void
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
            'password_pending' => $owner_id > 0 && get_user_meta($owner_id, self::PASSWORD_PENDING, true) === '1',
        ]);
    }

    public function renderPersonalisation(): void
    {
        $dossier = self::findDossier($this->currentDossierId());

        if ($dossier === null) {
            get_template_part('partials/account/forbidden');

            return;
        }

        get_template_part('partials/account/personalisation', null, ['dossier' => $dossier]);
    }

    /* ---------------------------------------------------------------------
     * Sauvegarde
     * ------------------------------------------------------------------ */

    /**
     * Enregistre la personnalisation d'un dossier.
     *
     * Tout ce qui est valide est enregistré, même lorsque le dossier reste
     * incomplet : un logo téléversé ou un texte saisi ne doit jamais être
     * perdu parce qu'un autre champ manque. Deux natures de problème sont
     * distinguées et signalées séparément :
     *
     * - refusé : la valeur soumise est invalide (format, poids, longueur).
     *   Elle n'est pas écrite et la valeur précédente est conservée.
     * - à compléter : le choix requis n'a pas encore été fait. Le reste est
     *   enregistré et la progression reste incomplète.
     *
     * Le dossier ne devient « complété » que lorsque tous les choix requis
     * sont résolus (réponse Q051).
     */
    /**
     * Applique une soumission de personnalisation à un dossier.
     *
     * Méthode partagée par le formulaire client et l'écran d'administration :
     * une seule règle de validation, donc une progression identique des deux
     * côtés. Elle n'écrit rien et ne redirige pas.
     *
     * Une clé absente de `$input` laisse la valeur enregistrée intacte ; une
     * clé présente et vide réinitialise le choix, ce qui permet à l'équipe
     * d'enregistrer volontairement un état partiel (arbitrage B06 du 14/09).
     *
     * Les pièces jointes ne transitent jamais par un identifiant soumis :
     * seul un fichier réellement téléversé pendant cette requête remplace la
     * pièce enregistrée. Un client ne peut donc pas rattacher à son dossier
     * une pièce appartenant à quelqu'un d'autre.
     *
     * @param array<string, mixed>  $dossier
     * @param array<string, mixed>  $input Valeurs soumises, déjà déséchappées.
     * @param array<string, string> $files Nature de la pièce => nom du champ fichier.
     * @return array{perso: array<string, mixed>, rejected: array<string, string>}
     */
    public static function applySubmission(array $dossier, array $input, array $files = []): array
    {
        $perso    = self::normalisePerso((array) ($dossier['perso'] ?? []));
        $rejected = [];

        if (array_key_exists('template', $input)) {
            $template = sanitize_key((string) $input['template']);

            if ($template === '' || in_array($template, wp_list_pluck(self::templates(), 'key'), true)) {
                $perso['template'] = $template;
            }
        }

        foreach (['logo_mode', 'text_mode'] as $mode) {
            if (! array_key_exists($mode, $input)) {
                continue;
            }

            $value = sanitize_key((string) $input[$mode]);

            if (in_array($value, ['', 'with', 'without'], true)) {
                $perso[$mode] = $value;
            }
        }

        $max  = (int) apply_filters('lumiboo_account_text_max_length', 100);
        $text = isset($input['perso_text']) ? sanitize_text_field((string) $input['perso_text']) : '';

        if ($perso['text_mode'] === 'without') {
            $perso['text'] = '';
        } elseif ($text !== '') {
            if (mb_strlen($text) > $max) {
                $rejected['text'] = sprintf(self::text('error_text_length', __('Le texte dépasse %d caractères.', 'theme_base')), $max);
            } else {
                $perso['text'] = $text;
            }
        }

        // Le champ libre est facultatif : il est conservé tel quel (H03).
        if (array_key_exists('requests', $input)) {
            $perso['requests'] = sanitize_textarea_field((string) $input['requests']);
        }

        // Pièces téléversées pendant cette requête. Une pièce refusée
        // n'écrase jamais la version enregistrée (H09).
        $replaced = [];

        foreach ($files as $piece => $name) {
            $field = self::PERSO_FILES[$piece] ?? '';

            if ($field === '' || empty($_FILES[$name]['name'])) {
                continue;
            }

            $stored = PrivateFiles::store($name, $piece);

            if (is_wp_error($stored)) {
                $rejected[$name] = $stored->get_error_message();
                continue;
            }

            $replaced[]    = $perso[$field];
            $perso[$field] = $stored;
        }

        if ($perso['logo_mode'] === 'without') {
            $replaced[]          = $perso['logo_file'];
            $perso['logo_file']  = [];
        }

        // Le droit aux pièces Studio dépend de la commande : une pièce
        // déposée sur un dossier qui perd l'option ne doit pas subsister.
        if (empty($dossier['has_studio_option'])) {
            $replaced[]             = $perso['welcome_file'];
            $replaced[]             = $perso['rear_file'];
            $perso['welcome_file']  = [];
            $perso['rear_file']     = [];
        }

        foreach ($replaced as $reference) {
            if (is_array($reference) && $reference !== []) {
                PrivateFiles::delete($reference);
            }
        }

        return [
            'perso'    => self::normalisePerso($perso),
            'rejected' => $rejected,
        ];
    }

    /**
     * Étapes encore ouvertes pour ce dossier, avec leur consigne.
     *
     * @param array<string, mixed> $dossier
     * @return array<string, string>
     */
    public static function missing(array $dossier): array
    {
        $missing = [];

        foreach (self::progress($dossier)['steps'] as $step) {
            if ($step['done'] || $step['key'] === 'complete') {
                continue;
            }

            $missing[$step['key']] = self::stepHint($step['key'], $step['label']);
        }

        return $missing;
    }

    public function handlePersonalisation(): void
    {
        $id = isset($_POST['dossier']) ? sanitize_key(wp_unslash($_POST['dossier'])) : '';

        check_admin_referer('lumiboo_perso_' . $id);

        $dossier = self::findDossier($id);

        if ($dossier === null) {
            $this->redirect(wc_get_account_endpoint_url('dashboard'), 'error', self::text('msg_forbidden', __('Ce dossier n’est pas accessible depuis votre compte.', 'theme_base')));
        }

        if ($dossier['locked']) {
            $this->redirect($dossier['url'], 'error', self::text('msg_locked', __('Les modifications en ligne sont fermées. Contactez l’équipe Lumiboo sur WhatsApp pour une correction.', 'theme_base')));
        }

        $result = self::applySubmission($dossier, (array) wp_unslash($_POST), [
            'logo'    => 'logo',
            'welcome' => 'welcome',
            'rear'    => 'rear',
        ]);

        $rejected = $result['rejected'];

        // Tout ce qui est valide est écrit, même si le dossier reste incomplet.
        self::writePerso($dossier, $result['perso']);

        $dossier['perso'] = $result['perso'];
        $missing          = self::missing($dossier);

        if ($rejected !== []) {
            $this->redirect(
                $dossier['url'],
                'error',
                self::text('msg_rejected', __('Certaines modifications n’ont pas pu être enregistrées.', 'theme_base')),
                $rejected + $missing
            );
        }

        if ($missing !== []) {
            $this->redirect(
                $dossier['url'],
                'warning',
                self::text('msg_incomplete', __('Vos modifications ont été enregistrées. Il reste des éléments à compléter.', 'theme_base')),
                $missing
            );
        }

        $this->redirect($dossier['url'], 'success', self::text('msg_saved', __('Votre personnalisation a été enregistrée.', 'theme_base')));
    }

    /**
     * Consigne indiquant comment résoudre une étape encore ouverte.
     */
    private static function stepHint(string $key, string $label): string
    {
        switch ($key) {
            case 'template':
                return self::text('error_template', __('Choisissez un modèle.', 'theme_base'));
            case 'logo':
                return self::text('error_logo', __('Ajoutez un logo ou choisissez « Sans logo ».', 'theme_base'));
            case 'text':
                return self::text('error_text', __('Saisissez un texte ou choisissez « Sans texte ».', 'theme_base'));
            case 'welcome':
                return self::text('error_welcome', __('L’écran d’accueil est requis pour cette commande.', 'theme_base'));
            case 'rear':
                return self::text('error_rear', __('L’écran arrière est requis pour cette commande.', 'theme_base'));
            default:
                return $label;
        }
    }

    /**
     * Écrit la personnalisation en meta de commande (compatible HPOS) ou, pour
     * un dossier de démonstration, en meta utilisateur.
     *
     * @param array<string, mixed> $dossier
     * @param array<string, mixed> $perso
     */
    public static function writePerso(array $dossier, array $perso): void
    {
        if (! empty($dossier['is_fixture'])) {
            update_user_meta(get_current_user_id(), self::FIXTURE_PREFIX . $dossier['id'], $perso);

            return;
        }

        $order = wc_get_order((int) $dossier['order_id']);

        if (! $order instanceof \WC_Order) {
            return;
        }

        foreach ($perso as $field => $value) {
            $order->update_meta_data(self::META_PREFIX . $field, $value);
        }

        $order->save();
    }

    /**
     * Met à jour le profil depuis le bloc « Informations personnelles ».
     * Les commandes existantes ne sont jamais réécrites.
     */
    public function handleProfile(): void
    {
        check_admin_referer('lumiboo_profile');

        $user_id = get_current_user_id();

        if (! $user_id) {
            $this->redirect(wc_get_page_permalink('myaccount'), 'error', self::text('msg_forbidden', __('Connectez-vous pour continuer.', 'theme_base')));
        }

        $fields = [
            'first_name'        => 'billing_first_name',
            'last_name'         => 'billing_last_name',
            'billing_phone'     => 'billing_phone',
            'billing_company'   => 'billing_company',
            'billing_country'   => 'billing_country',
            'billing_address_1' => 'billing_address_1',
            'billing_city'      => 'billing_city',
            'billing_postcode'  => 'billing_postcode',
        ];

        $customer = new \WC_Customer($user_id);

        foreach ($fields as $input => $property) {
            if (! isset($_POST[$input])) {
                continue;
            }

            $value  = sanitize_text_field(wp_unslash($_POST[$input]));
            $setter = 'set_' . $property;

            if (method_exists($customer, $setter)) {
                $customer->{$setter}($value);
            }
        }

        if (isset($_POST['first_name'])) {
            $customer->set_first_name(sanitize_text_field(wp_unslash($_POST['first_name'])));
        }

        if (isset($_POST['last_name'])) {
            $customer->set_last_name(sanitize_text_field(wp_unslash($_POST['last_name'])));
        }

        $customer->save();

        $this->redirect(wc_get_account_endpoint_url('dashboard'), 'success', self::text('msg_profile_saved', __('Vos informations ont été enregistrées.', 'theme_base')));
    }

    /* ---------------------------------------------------------------------
     * Documents privés : téléchargement contrôlé et conservation
     * ------------------------------------------------------------------ */

    /**
     * Sert un livrable Studio déposé par l'équipe.
     *
     * L'identité et l'éligibilité sont revérifiées à chaque appel : connaître
     * l'identifiant d'une commande ne donne jamais accès à son fichier
     * (§10.8). Un administrateur y accède également, pour vérification.
     */
    public function handleDocument(): void
    {
        if (! is_user_logged_in()) {
            auth_redirect();
        }

        $order_id = isset($_GET['order']) ? (int) $_GET['order'] : 0;
        $doc      = isset($_GET['doc']) ? sanitize_key(wp_unslash($_GET['doc'])) : '';
        $order    = $order_id ? wc_get_order($order_id) : null;

        $refused = self::text('msg_forbidden', __('Ce dossier n’est pas accessible depuis votre compte.', 'theme_base'));

        if (! $order instanceof \WC_Order || (self::DOC_FIELDS[$doc] ?? '') !== 'file') {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        $is_owner = (int) $order->get_user_id() === get_current_user_id();

        if (! $is_owner && ! current_user_can(self::adminCapability())) {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        $dossier = self::dossierFromOrder($order);

        // Les livrables n'existent que pour une commande Studio ayant acheté
        // l'option : aucun autre dossier ne peut les réclamer.
        if ($dossier === null || empty($dossier['has_studio_option'])) {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        PrivateFiles::stream(
            PrivateFiles::normalise($order->get_meta(self::DOC_PREFIX . $doc))
        );
    }

    /**
     * Sert une pièce de personnalisation : logo ou écran Studio.
     *
     * Le dossier est résolu dans l'espace du client connecté, ce qui vaut
     * contrôle de propriété et couvre aussi les dossiers de démonstration.
     * Un administrateur accède en plus à n'importe quelle commande, pour
     * vérification (§12.2).
     */
    public function handleFile(): void
    {
        if (! is_user_logged_in()) {
            auth_redirect();
        }

        $id    = isset($_GET['dossier']) ? sanitize_key(wp_unslash($_GET['dossier'])) : '';
        $piece = isset($_GET['piece']) ? sanitize_key(wp_unslash($_GET['piece'])) : '';
        $field = self::PERSO_FILES[$piece] ?? '';

        $refused = self::text('msg_forbidden', __('Ce dossier n’est pas accessible depuis votre compte.', 'theme_base'));

        if ($field === '') {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        $dossier = self::findDossier($id);

        if ($dossier === null && current_user_can(self::adminCapability())) {
            $order   = wc_get_order((int) $id);
            $dossier = $order instanceof \WC_Order ? self::dossierFromOrder($order) : null;
        }

        if ($dossier === null) {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        if ($piece !== 'logo' && empty($dossier['has_studio_option'])) {
            wp_die(esc_html($refused), '', ['response' => 403]);
        }

        PrivateFiles::stream((array) ($dossier['perso'][$field] ?? []), true);
    }

    public function scheduleDocumentPurge(): void
    {
        if (! wp_next_scheduled('lumiboo_purge_documents')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'lumiboo_purge_documents');
        }
    }

    /**
     * Supprime les fichiers d'emails collectés au terme de leur conservation.
     *
     * Arbitrage B06 du 14/09 : six mois après l'événement. Seul ce fichier est
     * concerné — il contient les adresses des participants. Le rapport
     * statistique n'est pas soumis à cette durée.
     */
    public function purgeDocuments(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }

        $months = (int) apply_filters('lumiboo_account_emails_retention_months', 6);
        $limit  = self::today()->modify(sprintf('-%d months', $months))->format('Y-m-d');

        $orders = wc_get_orders([
            'limit'      => 50,
            'status'     => self::confirmedStatuses(),
            'meta_query' => [
                [
                    'key'     => self::DOC_PREFIX . 'emails',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        foreach ($orders as $order) {
            if (! $order instanceof \WC_Order) {
                continue;
            }

            $dossier = self::dossierFromOrder($order);

            if ($dossier === null || (string) $dossier['event_date'] > $limit) {
                continue;
            }

            PrivateFiles::delete(
                PrivateFiles::normalise($order->get_meta(self::DOC_PREFIX . 'emails'))
            );

            $order->delete_meta_data(self::DOC_PREFIX . 'emails');
            $order->update_meta_data(self::DOC_EXPIRED_PREFIX . 'emails', '1');
            $order->add_order_note(sprintf(
                /* translators: %d: retention duration in months */
                __('Fichier des emails collectés supprimé : conservation de %d mois écoulée.', 'theme_base'),
                $months
            ));
            $order->save();
        }
    }

    /**
     * @param array<string, string> $errors
     */
    private function redirect(string $url, string $type, string $message, array $errors = []): void
    {
        set_transient(self::NOTICE_KEY . '_' . get_current_user_id(), [
            'type'    => $type,
            'message' => $message,
            'errors'  => $errors,
        ], MINUTE_IN_SECONDS);

        wp_safe_redirect($url);
        exit;
    }

    /**
     * Message flash de la dernière sauvegarde, consommé à l'affichage.
     *
     * @return array{type: string, message: string, errors: array<string, string>}|null
     */
    public static function notice(): ?array
    {
        $key    = self::NOTICE_KEY . '_' . get_current_user_id();
        $notice = get_transient($key);

        if (! is_array($notice)) {
            return null;
        }

        delete_transient($key);

        return [
            'type'    => (string) ($notice['type'] ?? 'success'),
            'message' => (string) ($notice['message'] ?? ''),
            'errors'  => (array) ($notice['errors'] ?? []),
        ];
    }
}
