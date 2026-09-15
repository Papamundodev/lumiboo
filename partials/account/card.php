<?php

/**
 * Carte d'une commande de location.
 *
 * Une carte = une commande = une réservation = un produit. Les actions
 * agissent sur cette carte uniquement ; la carte entière n'est pas cliquable
 * (décision Q060).
 *
 * @var array $args {
 *     @type array  $dossier Dossier normalisé par Theme_base\Account.
 *     @type string $context 'current' ou 'past'.
 *     @type bool   $hidden  Carte masquée tant que « Voir plus » n'est pas utilisé.
 * }
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;
$context = $args['context'] ?? 'current';
$hidden  = ! empty($args['hidden']);

if (! is_array($dossier)) {
    return;
}

$date_format = get_option('date_format');
$event_date  = wp_date($date_format, strtotime($dossier['event_date']));
$deadline    = wp_date($date_format, strtotime($dossier['deadline']));
$is_studio   = $dossier['product'] === 'studio';
$options     = [];

if (! empty($dossier['has_roll'])) {
    $options[] = Account::text('option_roll', __('Rouleau d’impressions supplémentaire', 'theme_base'));
}

if (! empty($dossier['has_studio_option'])) {
    $options[] = Account::text('option_studio', __('Option Studio', 'theme_base'));
}
?>

<li class="account-card account-card--<?= esc_attr($context); ?><?= $dossier['locked'] ? ' is-locked' : ''; ?>" <?= $hidden ? 'hidden' : ''; ?>>

    <h5 class="account-card-title"><?= esc_html($dossier['title']); ?></h5>

    <dl class="account-card-meta">
        <div class="account-card-row">
            <dt><?= esc_html(Account::text('label_event', __('Événement', 'theme_base'))); ?></dt>
            <dd><?= esc_html($event_date); ?></dd>
        </div>

        <?php if (Account::shippingLabel($dossier) !== '') : ?>
            <div class="account-card-row">
                <dt><?= esc_html(Account::text('label_shipping', __('Mode de retrait', 'theme_base'))); ?></dt>
                <dd><?= esc_html(Account::shippingLabel($dossier)); ?></dd>
            </div>
        <?php endif; ?>

        <?php if ($dossier['location'] !== '') : ?>
            <div class="account-card-row">
                <dt><?= esc_html(Account::text('label_location', __('Adresse', 'theme_base'))); ?></dt>
                <dd><?= esc_html($dossier['location']); ?></dd>
            </div>
        <?php endif; ?>

        <div class="account-card-row">
            <dt><?= esc_html(Account::text('label_options', __('Options', 'theme_base'))); ?></dt>
            <dd><?= esc_html($options === [] ? Account::text('label_no_option', __('Sans option', 'theme_base')) : implode(', ', $options)); ?></dd>
        </div>
    </dl>

    <?php if ($context === 'current') : ?>

        <?php get_template_part('partials/account/progress', null, ['dossier' => $dossier]); ?>

        <div class="account-card-actions">
            <?php if ($dossier['locked']) : ?>
                <div class="button-container button-background-secondary">
                    <a class="btn" href="<?= esc_url($dossier['url']); ?>">
                        <?= esc_html(Account::text('cta_personalise_locked', __('Voir ma personnalisation', 'theme_base'))); ?>
                    </a>
                </div>
                <p class="account-card-note"><?= esc_html(Account::text('msg_locked', __('Les modifications en ligne sont fermées. Contactez l’équipe Lumiboo sur WhatsApp pour une correction.', 'theme_base'))); ?></p>
            <?php else : ?>
                <div class="button-container button-background-tertiary">
                    <a class="btn" href="<?= esc_url($dossier['url']); ?>">
                        <?= esc_html(Account::text('cta_personalise', __('Personnaliser ma commande', 'theme_base'))); ?>
                    </a>
                </div>
                <p class="account-card-note"><?= esc_html(sprintf(Account::text('cta_deadline', __('Jusqu’au %s', 'theme_base')), $deadline)); ?></p>
            <?php endif; ?>
        </div>

    <?php else : ?>

        <div class="account-card-actions">
            <?php if ($dossier['gallery_url'] !== '') : ?>
                <div class="button-container button-background-tertiary">
                    <a class="btn" href="<?= esc_url($dossier['gallery_url']); ?>" target="_blank" rel="noopener noreferrer">
                        <?= esc_html(Account::text('cta_gallery', __('Galerie photos', 'theme_base'))); ?>
                    </a>
                </div>
            <?php else : ?>
                <p class="account-card-pending"><?= esc_html(Account::text('empty_gallery', __('Galerie en préparation', 'theme_base'))); ?></p>
            <?php endif; ?>

            <?php
            /**
             * Blocage B04 : produits, prix, tranches et livraison de l'achat
             * d'impressions ne sont pas définis. L'emplacement de l'action est
             * réservé, mais aucun tunnel incomplet n'est exposé.
             */
            ?>
            <div class="button-container button-background-secondary is-disabled">
                <button type="button" class="btn" disabled aria-disabled="true">
                    <?= esc_html(Account::text('cta_prints', __('Demander des impressions', 'theme_base'))); ?>
                </button>
            </div>
            <p class="account-card-pending"><?= esc_html(Account::text('prints_unavailable', __('Bientôt disponible', 'theme_base'))); ?></p>
        </div>

        <?php if (! empty($dossier['has_studio_option'])) : ?>
            <?php
            /**
             * Les deux livrables Studio sont indépendants. Un fichier retiré
             * au terme de sa conservation n'est pas un document « en
             * préparation » : les deux états sont distingués.
             */
            $documents = [
                'emails' => Account::text('cta_emails', __('Télécharger les emails collectés', 'theme_base')),
                'report' => Account::text('cta_report', __('Rapport statistiques', 'theme_base')),
            ];
            ?>
            <ul class="account-card-documents">
                <?php foreach ($documents as $key => $label) : ?>
                    <li>
                        <?php if ($dossier[$key . '_url'] !== '') : ?>
                            <a href="<?= esc_url($dossier[$key . '_url']); ?>" download>
                                <?= esc_html($label); ?>
                            </a>
                        <?php elseif (! empty($dossier[$key . '_expired'])) : ?>
                            <span class="account-card-pending"><?= esc_html(Account::text('expired_document', __('Fichier expiré', 'theme_base'))); ?></span>
                        <?php else : ?>
                            <span class="account-card-pending"><?= esc_html(Account::text('empty_document', __('Document en préparation', 'theme_base'))); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    <?php endif; ?>

    <p class="account-card-invoice">
        <?php if ($dossier['invoice_url'] !== '') : ?>
            <a href="<?= esc_url($dossier['invoice_url']); ?>">
                <?= esc_html(Account::text('cta_invoice', __('Télécharger la facture', 'theme_base'))); ?>
            </a>
        <?php else : ?>
            <span class="account-card-pending"><?= esc_html(Account::text('empty_invoice', __('Facture bientôt disponible', 'theme_base'))); ?></span>
        <?php endif; ?>
    </p>

</li>
