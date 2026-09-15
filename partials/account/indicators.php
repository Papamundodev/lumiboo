<?php

/**
 * Indicateurs du haut de page.
 *
 * Deux compteurs seulement : le compteur « Photos souvenirs » du wireframe a
 * été retiré, un lien K-Suite ne fournissant aucun nombre (réponse Q062).
 */

use Theme_base\Account;

defined('ABSPATH') || exit;
?>

<ul class="account-indicators">
    <?php foreach (Account::indicators() as $indicator) : ?>
        <li class="account-indicator">
            <span class="account-indicator-value"><?= esc_html((string) $indicator['value']); ?></span>
            <span class="account-indicator-label"><?= esc_html($indicator['label']); ?></span>
        </li>
    <?php endforeach; ?>
</ul>
