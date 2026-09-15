<?php

namespace Theme_base;

/**
 * Enregistrement des groupes de champs ACF livrés avec le thème.
 *
 * Même principe que les mu-plugins `banner`, `faq` et `odometer` : les groupes
 * sont déclarés en « local field group », donc rien n'est écrit en base. Si le
 * groupe a déjà été importé manuellement (sa clé existe dans `wp_posts`), on
 * ne l'enregistre pas, pour ne pas écraser les modifications faites en admin
 * ni déclencher les notices de synchronisation ACF.
 */
class Acf
{
    /** Fichiers JSON à charger, relatifs au dossier `acf/` du thème. */
    private const FILES = [
        'account.json',
        'forms.json',
    ];

    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        foreach (self::FILES as $file) {
            foreach (self::readGroups($file) as $group) {
                if (empty($group['key']) || self::existsInDatabase((string) $group['key'])) {
                    continue;
                }

                acf_add_local_field_group($group);
            }
        }
    }

    /**
     * ACF stocke la clé du groupe dans `post_name`.
     */
    private static function existsInDatabase(string $key): bool
    {
        global $wpdb;

        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'acf-field-group' AND post_name = %s
             LIMIT 1",
            $key
        ));

        return ! empty($id);
    }

    /**
     * Accepte un fichier contenant un groupe unique ou une liste de groupes.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function readGroups(string $file): array
    {
        $path = get_template_directory() . '/acf/' . $file;

        if (! is_readable($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return isset($decoded['key']) ? [$decoded] : array_values($decoded);
    }
}
