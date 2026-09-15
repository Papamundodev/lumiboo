<?php

/**
 * Accusé de réception ou signalement d'erreur d'un formulaire public.
 *
 * Après envoi, une confirmation s'affiche et rien d'autre ne se produit :
 * le formulaire de contact n'est pas une commande (§13.2).
 *
 * @var array{notice: array{type: string, message: string, errors: array<string, string>}|null} $args
 */

defined('ABSPATH') || exit;

$notice = is_array($args['notice'] ?? null) ? $args['notice'] : null;

if ($notice === null || $notice['message'] === '') {
    return;
}
?>

<div class="form-notice form-notice--<?= esc_attr($notice['type']); ?>" role="status" tabindex="-1">
    <p><?= esc_html($notice['message']); ?></p>

    <?php if ($notice['errors'] !== []) : ?>
        <ul class="form-notice-errors">
            <?php foreach ($notice['errors'] as $error) : ?>
                <li><?= esc_html((string) $error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
