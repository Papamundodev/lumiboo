<?php

/**
 * Barre de progression de la personnalisation.
 *
 * Le dernier segment est un résultat calculé, jamais un champ ni une
 * approbation de l'équipe. Un état n'est pas porté par la seule couleur :
 * chaque segment garde son libellé et son état accessible.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;

if (! is_array($dossier)) {
    return;
}

$progress = $dossier['progress'];
?>

<div class="account-progress" role="group" aria-label="<?= esc_attr(Account::text('step_complete', __('Personnalisation complétée', 'theme_base'))); ?>">
    <ol class="account-progress-steps">
        <?php foreach ($progress['steps'] as $step) : ?>
            <li class="account-progress-step<?= $step['done'] ? ' is-done' : ''; ?>">
                <span class="account-progress-bar" aria-hidden="true"></span>
                <span class="account-progress-label"><?= esc_html($step['label']); ?></span>
                <span class="screen-reader-text">
                    <?= $step['done'] ? esc_html__('Complété', 'theme_base') : esc_html__('À compléter', 'theme_base'); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
