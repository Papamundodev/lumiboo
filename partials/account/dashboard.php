<?php

/**
 * Tableau de bord « Mon espace Lumiboo ».
 *
 * Ordre du wireframe : indicateurs, avis Google, commandes en cours,
 * commandes passées, informations personnelles.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$dossiers   = Account::dossiers();
$has_any    = $dossiers['current'] !== [] || $dossiers['past'] !== [];
$review_url = Account::text('review_url');
?>

<div class="account">

    <?php get_template_part('partials/account/notice'); ?>

    <header class="account-header">
        <h2 class="account-title"><?= esc_html(Account::text('dashboard_title', __('Mon espace Lumiboo', 'theme_base'))); ?></h2>

        <?php get_template_part('partials/account/indicators'); ?>

        <?php if ($review_url !== '') : ?>
            <div class="account-header-action button-container button-background-primary">
                <a class="btn" href="<?= esc_url($review_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?= esc_html(Account::text('review_label', __('Laisser un avis Google', 'theme_base'))); ?>
                </a>
            </div>
        <?php endif; ?>
    </header>

    <section class="account-orders">
        <h3 class="account-orders-title"><?= esc_html(Account::text('section_orders_title', __('Mes commandes', 'theme_base'))); ?></h3>

        <?php if (! $has_any) : ?>
            <p class="account-empty"><?= esc_html(Account::text('empty_all', __('Vous n’avez pas encore de réservation.', 'theme_base'))); ?></p>
        <?php endif; ?>

        <?php if ($has_any) : ?>
            <h4 class="account-section-title"><?= esc_html(Account::text('section_current', __('Commandes en cours', 'theme_base'))); ?></h4>

            <?php if ($dossiers['current'] === []) : ?>
                <p class="account-empty"><?= esc_html(Account::text('empty_current', __('Aucune réservation à venir.', 'theme_base'))); ?></p>
            <?php else : ?>
                <ul class="account-cards">
                    <?php foreach ($dossiers['current'] as $dossier) : ?>
                        <?php get_template_part('partials/account/card', null, ['dossier' => $dossier, 'context' => 'current']); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h4 class="account-section-title"><?= esc_html(Account::text('section_past', __('Commandes passées', 'theme_base'))); ?></h4>

            <?php if ($dossiers['past'] === []) : ?>
                <p class="account-empty"><?= esc_html(Account::text('empty_past', __('Vos événements passés apparaîtront ici.', 'theme_base'))); ?></p>
            <?php else : ?>
                <?php
                // Hypothèse H04 : dix cartes puis chargement progressif.
                $per_page = (int) apply_filters('lumiboo_account_past_per_page', 10);
                ?>
                <ul class="account-cards" data-account-paginate="<?= esc_attr((string) $per_page); ?>">
                    <?php foreach ($dossiers['past'] as $index => $dossier) : ?>
                        <?php get_template_part('partials/account/card', null, [
                            'dossier' => $dossier,
                            'context' => 'past',
                            'hidden'  => $index >= $per_page,
                        ]); ?>
                    <?php endforeach; ?>
                </ul>

                <?php if (count($dossiers['past']) > $per_page) : ?>
                    <div class="button-wrapper">
                        <div class="button-container button-background-secondary">
                            <button type="button" class="btn" data-account-load-more>
                                <?= esc_html(Account::text('load_more', __('Voir plus', 'theme_base'))); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php get_template_part('partials/account/personal-info'); ?>

    <?php get_template_part('partials/account/sav'); ?>

</div>
