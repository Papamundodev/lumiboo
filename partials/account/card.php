<?php

/**
 * Carte d'un dossier Lumiboo.
 *
 * Une carte = une commande = une réservation. Pas cliquable (Q060) :
 * seul le bouton CTA (chunk 6, cartes « en cours ») est un lien.
 *
 * @var array $args {
 *     @type array  $dossier Normalisé par Theme_base\Dossier.
 *     @type string $context 'current' ou 'past'.
 * }
 */

use Theme_base\Account;
use Theme_base\Deadline;
use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;
$context = $args['context'] ?? 'current';

if (! is_array($dossier)) {
    return;
}

$event = $dossier['event_date'] ?? null;
$event_date = $event instanceof DateTimeImmutable
    ? wp_date((string) get_option('date_format'), $event->getTimestamp())
    : '';

$options = [];
if (! empty($dossier['has_studio_option'])) {
    $options[] = Account::text('option_studio', 'Option Studio');
}

$shipping = Account::shippingLabel($dossier);
$location = (string) ($dossier['location'] ?? '');
$title    = (string) ($dossier['title'] ?? '');

$paid   = ! empty($dossier['paid']);
$locked = Deadline::isLocked($dossier);
$url    = Personalisation::viewOrderUrl($dossier);

$card_class = 'account-card account-card--' . $context;
if ($context === 'current' && $locked) {
    $card_class .= ' is-locked';
}
?>

<li class="<?php echo esc_attr($card_class); ?>">

    <h5 class="account-card-title"><?php echo esc_html($title); ?></h5>

    <dl class="account-card-meta">
        <?php if ($event_date !== '') : ?>
            <div class="account-card-row">
                <dt><?php echo esc_html(Account::text('label_event', 'Événement')); ?></dt>
                <dd><?php echo esc_html($event_date); ?></dd>
            </div>
        <?php endif; ?>

        <?php if ($shipping !== '') : ?>
            <div class="account-card-row">
                <dt><?php echo esc_html(Account::text('label_shipping', 'Mode de retrait')); ?></dt>
                <dd><?php echo esc_html($shipping); ?></dd>
            </div>
        <?php endif; ?>

        <?php if ($location !== '') : ?>
            <div class="account-card-row">
                <dt><?php echo esc_html(Account::text('label_location', 'Adresse')); ?></dt>
                <dd><?php echo esc_html($location); ?></dd>
            </div>
        <?php endif; ?>

        <div class="account-card-row">
            <dt><?php echo esc_html(Account::text('label_options', 'Options')); ?></dt>
            <dd><?php echo esc_html($options === [] ? Account::text('label_no_option', 'Sans option') : implode(', ', $options)); ?></dd>
        </div>
    </dl>

    <?php if ($context === 'current') : ?>

        <?php get_template_part('partials/account/progress', null, ['dossier' => $dossier]); ?>

        <div class="account-card-actions">
            <?php if (! $paid) : ?>

                <div class="button-container button-background-secondary is-disabled">
                    <button type="button" class="btn" disabled aria-disabled="true">
                        <?php echo esc_html(Account::text('cta_awaiting_payment', 'En attente de paiement')); ?>
                    </button>
                </div>
                <p class="account-card-note">
                    <?php echo esc_html(Account::text('msg_awaiting_payment', 'Votre virement est en cours de validation.')); ?>
                </p>

            <?php elseif ($locked && $url !== '') : ?>

                <div class="button-container button-background-secondary">
                    <a class="btn" href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html(Account::text('cta_personalise_locked', 'Voir ma personnalisation')); ?>
                    </a>
                </div>
                <p class="account-card-note">
                    <?php echo esc_html(Account::text('msg_locked', 'Les modifications en ligne sont fermées.')); ?>
                </p>

            <?php elseif ($url !== '') : ?>

                <?php $deadline_note = Personalisation::deadlineNote($dossier); ?>
                <div class="button-container button-background-tertiary">
                    <a class="btn" href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html(Account::text('cta_personalise', 'Personnaliser ma commande')); ?>
                    </a>
                </div>
                <?php if ($deadline_note !== '') : ?>
                    <p class="account-card-note"><?php echo esc_html($deadline_note); ?></p>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    <?php endif; ?>

</li>
