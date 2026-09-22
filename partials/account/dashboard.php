<?php

/**
 * Tableau de bord « Mon espace Lumiboo » (chunk 5).
 *
 * Coquille : titre, deux sections, empty states. Les cartes sont
 * minimales (titre + méta) — progression / deadline / CTA au chunk 6.
 *
 * Textes : ACF page d'options `lumiboo-settings`
 * (`acf-export-account-dashboard.json`).
 */

use Theme_base\Account;
use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$dossiers = Account::dossiers();
$has_any  = $dossiers['current'] !== [] || $dossiers['past'] !== [];
?>

<div class="account">

    <header class="account-header">
        <h2 class="account-title"><?php echo esc_html(Account::text('dashboard_title', 'Mon espace Lumiboo')); ?></h2>
    </header>

    <?php Personalisation::renderNotice(); ?>

    <section class="account-orders">
        <h3 class="account-orders-title"><?php echo esc_html(Account::text('section_orders', 'Mes commandes')); ?></h3>

        <?php if (! $has_any) : ?>
            <p class="account-empty"><?php echo esc_html(Account::text('empty_all', 'Vous n’avez pas encore de réservation.')); ?></p>
        <?php endif; ?>

        <?php if ($has_any) : ?>
            <h4 class="account-section-title"><?php echo esc_html(Account::text('section_current', 'Commandes en cours')); ?></h4>

            <?php if ($dossiers['current'] === []) : ?>
                <p class="account-empty"><?php echo esc_html(Account::text('empty_current', 'Aucune réservation à venir.')); ?></p>
            <?php else : ?>
                <ul class="account-cards">
                    <?php foreach ($dossiers['current'] as $dossier) : ?>
                        <?php get_template_part('partials/account/card', null, ['dossier' => $dossier, 'context' => 'current']); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h4 class="account-section-title"><?php echo esc_html(Account::text('section_past', 'Commandes passées')); ?></h4>

            <?php if ($dossiers['past'] === []) : ?>
                <p class="account-empty"><?php echo esc_html(Account::text('empty_past', 'Vos événements passés apparaîtront ici.')); ?></p>
            <?php else : ?>
                <ul class="account-cards">
                    <?php foreach ($dossiers['past'] as $dossier) : ?>
                        <?php get_template_part('partials/account/card', null, ['dossier' => $dossier, 'context' => 'past']); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>
