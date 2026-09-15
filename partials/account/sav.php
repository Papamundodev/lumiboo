<?php

/**
 * Accès SAV permanent.
 *
 * Le SAV passe par WhatsApp (réponse Q069). Le lien « Nos conseils » du
 * footer mène au blog et ne remplace pas cet accès.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$url   = Account::text('sav_url');
$label = Account::text('sav_label', __('Une question ? Écrivez-nous sur WhatsApp', 'theme_base'));
?>

<aside class="account-sav">
    <?php if ($url !== '') : ?>
        <a href="<?= esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?= esc_html($label); ?></a>
    <?php else : ?>
        <span><?= esc_html($label); ?></span>
    <?php endif; ?>
</aside>
