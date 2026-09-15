<?php

/**
 * Message de la dernière sauvegarde.
 *
 * Le message distingue trois résultats : tout est enregistré et complet, tout
 * ce qui était valide est enregistré mais il reste des choix à faire, ou une
 * valeur a été refusée et la version précédente conservée. Le détail champ
 * par champ accompagne les deux derniers cas.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$notice = Account::notice();

if ($notice === null || $notice['message'] === '') {
    return;
}
?>

<div class="account-notice account-notice--<?= esc_attr($notice['type']); ?>" role="status">
    <p><?= esc_html($notice['message']); ?></p>

    <?php if ($notice['errors'] !== []) : ?>
        <ul class="account-notice-errors">
            <?php foreach ($notice['errors'] as $error) : ?>
                <li><?= esc_html((string) $error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
