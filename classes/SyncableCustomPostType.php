<?php

namespace Theme_base;

/**
 * CustomPostType enrichi d'un moteur de synchronisation générique.
 *
 * Aucune connaissance métier de la source (INSEE, Nominis, Wikidata, ...) :
 * la spécificité vit dans la couche qui produit les records.
 *
 * Voir .cursor/import.md § "Répartition du code" pour la spec complète.
 */
class SyncableCustomPostType extends CustomPostType
{
    /**
     * Synchronise une liste de records avec les posts de ce CPT.
     *
     * Chaque record est un array associatif contenant au minimum :
     *   - la valeur de lookup (champ ciblé par $options['lookup_field'])
     *   - le titre (champ ciblé par $options['title_field'])
     *   - éventuellement les champs listés par meta_map / taxonomy_terms
     *
     * @param iterable $records
     * @param array    $options Voir tableau d'options dans .cursor/import.md.
     * @return array{created:int,updated:int,skipped:int,errors:int}
     */
    public function syncRecords(iterable $records, array $options = []): array
    {
        $opts = array_merge([
            'lookup_field'    => 'key',
            'lookup_meta_key' => '_source_key',
            'title_field'     => 'title',
            'slug_field'      => 'slug',
            'content_field'   => 'content',
            'content_marker'  => null,
            'meta_map'        => [],
            'taxonomy_terms'  => [],
            'post_status'     => 'publish',
            'dry_run'         => false,
            'batch_size'      => 500,
            'logger'          => null,
        ], $options);

        $stats  = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $logger = is_callable($opts['logger']) ? $opts['logger'] : null;
        $log = function (string $level, string $msg, array $ctx = []) use ($logger): void {
            if ($logger) {
                $logger($level, $msg, $ctx);
            }
        };

        $postType = $this->getSlug();

        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        $processed = 0;

        foreach ($records as $record) {
            try {
                if (!is_array($record)) {
                    $stats['skipped']++;
                    continue;
                }

                $lookupValue = $record[$opts['lookup_field']] ?? null;
                $title       = $record[$opts['title_field']]  ?? null;

                if ($lookupValue === null || $lookupValue === '' || $title === null || $title === '') {
                    $stats['skipped']++;
                    $log('warning', 'record ignoré (lookup/title manquant)', ['record' => $record]);
                    continue;
                }

                $existingId = $this->findPostIdByMeta($postType, (string) $opts['lookup_meta_key'], (string) $lookupValue);

                if ($existingId === 0) {
                    $newId = $this->createRecordPost($record, $opts, $postType, $lookupValue, (string) $title);
                    $stats[$newId !== 0 ? 'created' : 'errors']++;
                } else {
                    $ok = $this->updateRecordPost($existingId, $record, $opts);
                    $stats[$ok ? 'updated' : 'errors']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                $log('error', 'exception dans syncRecords', [
                    'message' => $e->getMessage(),
                    'record'  => $record ?? null,
                ]);
            }

            $processed++;
            if ($processed % max(1, (int) $opts['batch_size']) === 0) {
                if (function_exists('wp_cache_flush_runtime')) {
                    wp_cache_flush_runtime();
                } else {
                    wp_cache_flush();
                }
                $log('info', 'batch flush', ['processed' => $processed, 'stats' => $stats]);
            }
        }

        wp_defer_term_counting(false);
        wp_defer_comment_counting(false);

        $log('info', 'syncRecords terminé', ['stats' => $stats, 'processed' => $processed]);

        return $stats;
    }

    private function findPostIdByMeta(string $postType, string $metaKey, string $metaValue): int
    {
        $q = new \WP_Query([
            'post_type'      => $postType,
            'post_status'    => 'any',
            'meta_key'       => $metaKey,
            'meta_value'     => $metaValue,
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'cache_results'  => false,
        ]);

        return isset($q->posts[0]) ? (int) $q->posts[0] : 0;
    }

    /**
     * @return int ID du post créé, 0 en cas d'erreur, -1 en dry-run.
     */
    private function createRecordPost(array $record, array $opts, string $postType, $lookupValue, string $title): int
    {
        if ($opts['dry_run']) {
            return -1;
        }

        $slug = ($opts['slug_field'] !== null && !empty($record[$opts['slug_field']]))
            ? sanitize_title((string) $record[$opts['slug_field']])
            : sanitize_title($title);

        $content = '';
        if ($opts['content_field'] !== null && isset($record[$opts['content_field']])) {
            $content = (string) $record[$opts['content_field']];
        }

        $postId = wp_insert_post([
            'post_type'    => $postType,
            'post_status'  => (string) $opts['post_status'],
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
        ], true);

        if (is_wp_error($postId) || !$postId) {
            return 0;
        }

        update_post_meta($postId, (string) $opts['lookup_meta_key'], (string) $lookupValue);

        $this->applyMeta((int) $postId, $record, (array) $opts['meta_map']);
        $this->applyTaxonomies((int) $postId, $record, (array) $opts['taxonomy_terms']);

        return (int) $postId;
    }

    private function updateRecordPost(int $postId, array $record, array $opts): bool
    {
        if ($opts['dry_run']) {
            return true;
        }

        // post_content : réécrit UNIQUEMENT si l'existant commence par le marker.
        if ($opts['content_field'] !== null && $opts['content_marker'] !== null) {
            $existing = get_post_field('post_content', $postId);
            $marker   = (string) $opts['content_marker'];
            if (is_string($existing)
                && strncmp($existing, $marker, strlen($marker)) === 0
                && isset($record[$opts['content_field']])
            ) {
                wp_update_post([
                    'ID'           => $postId,
                    'post_content' => (string) $record[$opts['content_field']],
                ], true);
            }
        }

        $this->applyMeta($postId, $record, (array) $opts['meta_map']);
        $this->applyTaxonomies($postId, $record, (array) $opts['taxonomy_terms']);

        return true;
    }

    private function applyMeta(int $postId, array $record, array $metaMap): void
    {
        foreach ($metaMap as $recordField => $metaKey) {
            if (!array_key_exists($recordField, $record)) {
                continue;
            }
            $value = $record[$recordField];
            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value);
            }
            update_post_meta($postId, (string) $metaKey, $value);
        }
    }

    private function applyTaxonomies(int $postId, array $record, array $taxonomyTerms): void
    {
        foreach ($taxonomyTerms as $taxonomy => $recordField) {
            if (!isset($record[$recordField])) {
                continue;
            }
            $term  = $record[$recordField];
            $terms = is_array($term) ? $term : [$term];
            wp_set_object_terms($postId, $terms, (string) $taxonomy, false);
        }
    }
}
