<?php

namespace Theme_base;

/**
 * Stockage des fichiers privés d'un dossier Lumiboo.
 *
 * Les logos, écrans Studio, CSV d'emails et rapports PDF ne sont pas des
 * médias du site : ce sont des pièces appartenant à une commande. Ils ne
 * doivent donc jamais recevoir d'adresse publique (§10.8).
 *
 * WordPress place les pièces jointes dans `wp-content/uploads`, un dossier
 * servi directement par le serveur web : la requête n'exécute pas WordPress,
 * donc aucun contrôle d'identité n'est possible. Ces fichiers sont pour cette
 * raison écrits en dehors de la racine publique, sous `app/private/lumiboo`,
 * et lus uniquement par les points d'entrée de `Theme_base\Account`, qui
 * revérifient le propriétaire à chaque appel.
 *
 * Une référence de fichier est un tableau : chemin relatif au dossier privé,
 * nom d'origine affiché au client, type, taille et date de dépôt. Le chemin
 * réel n'est jamais transmis au navigateur.
 */
class PrivateFiles
{
    /** Sous-dossier par nature de pièce. */
    public const SCOPES = [
        'logo'    => 'logos',
        'welcome' => 'studio',
        'rear'    => 'studio',
        'emails'  => 'emails',
        'report'  => 'reports',
    ];

    /**
     * Contraintes de dépôt par nature de pièce.
     *
     * Les valeurs des images sont l'hypothèse H10, en attente des formats,
     * poids et dimensions réels que Lumiboo doit fournir (blocage B03).
     *
     * @return array{types: array<int, string>, max: int}
     */
    public static function constraints(string $scope): array
    {
        $rules = [
            'emails' => [
                'types' => ['text/csv', 'text/plain', 'application/csv'],
                'max'   => 10 * MB_IN_BYTES,
            ],
            'report' => [
                'types' => ['application/pdf'],
                'max'   => 20 * MB_IN_BYTES,
            ],
        ];

        $default = [
            'types' => ['image/png', 'image/jpeg'],
            'max'   => 10 * MB_IN_BYTES,
        ];

        return (array) apply_filters(
            'lumiboo_private_constraints',
            $rules[$scope] ?? $default,
            $scope
        );
    }

    /* ---------------------------------------------------------------------
     * Emplacement
     * ------------------------------------------------------------------ */

    /**
     * Dossier racine du stockage privé.
     *
     * Par défaut hors de la racine publique, à côté d'elle. Si cet
     * emplacement n'est pas inscriptible — hébergement mutualisé, racine
     * verrouillée — le repli reste dans `uploads`, protégé par un nom de
     * fichier imprévisible et par les fichiers de blocage écrits ci-dessous.
     * Ce repli est volontairement moins sûr : le filtre permet de désigner un
     * emplacement réellement hors ligne sur le serveur de production.
     */
    public static function basePath(): string
    {
        static $path = null;

        if ($path !== null) {
            return $path;
        }

        $default = dirname(ABSPATH) . '/private/lumiboo';
        $chosen  = (string) apply_filters('lumiboo_private_path', $default);

        if (! self::prepare($chosen)) {
            $uploads = wp_upload_dir();
            $chosen  = trailingslashit($uploads['basedir']) . 'lumiboo-private';

            self::prepare($chosen);
        }

        $path = untrailingslashit($chosen);

        return $path;
    }

    /**
     * Crée le dossier et y dépose les garde-fous d'usage.
     *
     * `.htaccess` ne concerne qu'Apache et reste sans effet sous Nginx ; il
     * est écrit malgré tout, car l'hébergement de production n'est pas connu
     * et ce fichier ne coûte rien. La protection réelle vient de
     * l'emplacement du dossier, pas de ce fichier.
     */
    private static function prepare(string $path): bool
    {
        if (! is_dir($path) && ! wp_mkdir_p($path)) {
            return false;
        }

        if (! is_writable($path)) {
            return false;
        }

        $guards = [
            'index.php'  => "<?php\n// Silence.\n",
            '.htaccess'  => "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
        ];

        foreach ($guards as $file => $contents) {
            $target = $path . '/' . $file;

            if (! file_exists($target)) {
                @file_put_contents($target, $contents);
            }
        }

        return true;
    }

    /**
     * Chemin absolu d'une référence, ou chaîne vide si elle ne pointe sur
     * rien. Le chemin est revalidé pour qu'une valeur trafiquée en base ne
     * puisse pas sortir du dossier privé.
     */
    public static function path(array $reference): string
    {
        $relative = (string) ($reference['path'] ?? '');

        if ($relative === '' || strpos($relative, '..') !== false) {
            return '';
        }

        $base = self::basePath();
        $full = $base . '/' . ltrim($relative, '/');
        $real = realpath($full);

        if ($real === false || strpos($real, (string) realpath($base)) !== 0) {
            return '';
        }

        return $real;
    }

    public static function exists(array $reference): bool
    {
        $path = self::path($reference);

        return $path !== '' && is_readable($path);
    }

    /* ---------------------------------------------------------------------
     * Dépôt et suppression
     * ------------------------------------------------------------------ */

    /**
     * Déplace un fichier reçu vers le stockage privé.
     *
     * @return array<string, mixed>|\WP_Error Référence de fichier, ou l'erreur
     *                                        rencontrée. Le message précise la
     *                                        contrainte non respectée.
     */
    public static function store(string $input, string $scope)
    {
        $file = $_FILES[$input] ?? null;

        if (! is_array($file) || empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            return new \WP_Error('lumiboo_upload', __('Aucun fichier reçu.', 'theme_base'));
        }

        if (! empty($file['error'])) {
            return new \WP_Error('lumiboo_upload', __('Le téléversement a échoué.', 'theme_base'));
        }

        $rules = self::constraints($scope);

        if ((int) $file['size'] > (int) $rules['max']) {
            return new \WP_Error('lumiboo_size', sprintf(
                Account::text('error_file_size', __('Fichier trop lourd : %s maximum.', 'theme_base')),
                size_format((int) $rules['max'])
            ));
        }

        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        $type  = (string) ($check['type'] ?? '');
        $ext   = (string) ($check['ext'] ?? '');

        if ($type === '' || ! in_array($type, (array) $rules['types'], true)) {
            return new \WP_Error('lumiboo_type', self::typeError($scope, (array) $rules['types']));
        }

        $folder = self::SCOPES[$scope] ?? 'divers';
        $target = self::basePath() . '/' . $folder;

        if (! self::prepare($target)) {
            return new \WP_Error('lumiboo_path', __('Le stockage privé n’est pas accessible en écriture.', 'theme_base'));
        }

        // Nom imprévisible : le nom d'origine n'est conservé que pour
        // l'affichage et pour le téléchargement.
        $name = wp_generate_password(32, false, false) . '.' . $ext;

        if (! move_uploaded_file($file['tmp_name'], $target . '/' . $name)) {
            return new \WP_Error('lumiboo_move', __('Le fichier n’a pas pu être enregistré.', 'theme_base'));
        }

        @chmod($target . '/' . $name, 0600);

        return [
            'path' => $folder . '/' . $name,
            'name' => sanitize_file_name((string) $file['name']),
            'mime' => $type,
            'size' => (int) $file['size'],
            'date' => current_time('mysql'),
        ];
    }

    /**
     * @param array<int, string> $types
     */
    private static function typeError(string $scope, array $types): string
    {
        if ($scope === 'emails') {
            return __('Format refusé. Déposez un fichier CSV.', 'theme_base');
        }

        if ($scope === 'report') {
            return __('Format refusé. Déposez un fichier PDF.', 'theme_base');
        }

        return Account::text('error_file_type', __('Format refusé. Formats acceptés : PNG, JPEG.', 'theme_base'));
    }

    public static function delete(array $reference): void
    {
        $path = self::path($reference);

        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    /* ---------------------------------------------------------------------
     * Lecture
     * ------------------------------------------------------------------ */

    /**
     * Ramène une valeur enregistrée à une référence exploitable.
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    public static function normalise($value): array
    {
        if (! is_array($value) || empty($value['path'])) {
            return [];
        }

        return [
            'path' => (string) $value['path'],
            'name' => (string) ($value['name'] ?? ''),
            'mime' => (string) ($value['mime'] ?? ''),
            'size' => (int) ($value['size'] ?? 0),
            'date' => (string) ($value['date'] ?? ''),
        ];
    }

    /**
     * Nom présenté au client, jamais le chemin réel.
     */
    public static function label(array $reference): string
    {
        $reference = self::normalise($reference);

        if ($reference === []) {
            return '';
        }

        return $reference['name'] !== '' ? $reference['name'] : basename($reference['path']);
    }

    /**
     * Envoie le fichier au navigateur. L'appelant a déjà vérifié le droit
     * d'accès : cette méthode ne fait aucun contrôle d'identité.
     */
    public static function stream(array $reference, bool $inline = false): void
    {
        $reference = self::normalise($reference);
        $path      = self::path($reference);

        if ($path === '' || ! is_readable($path)) {
            wp_die(
                esc_html(Account::text('empty_document', __('Document en préparation', 'theme_base'))),
                '',
                ['response' => 404]
            );
        }

        nocache_headers();

        header('Content-Type: ' . ($reference['mime'] !== '' ? $reference['mime'] : 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header(sprintf(
            '%s; filename="%s"',
            $inline ? 'Content-Disposition: inline' : 'Content-Disposition: attachment',
            self::label($reference)
        ));

        readfile($path);
        exit;
    }
}
