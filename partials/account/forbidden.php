<?php

/**
 * Dossier inexistant ou appartenant à un autre client.
 *
 * Aucune donnée du dossier tiers n'est révélée : seul un retour vers le
 * compte du client connecté est proposé.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;
?>

<div class="account account-forbidden">
    <p><?= esc_html(Account::text('msg_forbidden', __('Ce dossier n’est pas accessible depuis votre compte.', 'theme_base'))); ?></p>

    <div class="button-wrapper">
        <div class="button-container button-background-tertiary">
            <a class="btn" href="<?= esc_url(wc_get_account_endpoint_url('dashboard')); ?>">
                <?= esc_html(Account::text('perso_back', __('Retour à mon espace', 'theme_base'))); ?>
            </a>
        </div>
    </div>
</div>
