<?php

/**
 * Carte d'une réservation Lumiboo.
 *
 * Une carte = un booking. Pas cliquable : seuls le CTA et le lien facture
 * sont des liens. Ancre `lumiboo-booking-{id}` pour le retour après save.
 *
 * @var array $args {
 *     @type array  $dossier Normalisé par Theme_base\Dossier::fromBooking().
 *     @type string $context 'current' ou 'past'.
 * }
 */

use Theme_base\Account;
use Theme_base\Gamme;
use Theme_base\Impressions;
use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;
$context = $args['context'] ?? 'current';

if (! is_array($dossier)) {
    return;
}

$booking_id = (string) ($dossier['id'] ?? '');
$gamme      = (string) ($dossier['gamme'] ?? '');

$title = 'LUMIBOO';

if ($gamme !== '') {
    $title .= ' ' . ucfirst($gamme);
}

if ($gamme === Gamme::PREMIUM) {
    $prints    = $dossier['prints'] ?? null;
    $marketing = Impressions::marketingLabel(is_string($prints) && $prints !== '' ? $prints : null);

    if ($marketing !== null) {
        $title .= ' – ' . $marketing;
    }
}

$order = $dossier['order'] ?? null;
$total = $order instanceof WC_Order
    ? (string) $order->get_formatted_order_total()
    : esc_html((string) ($dossier['order_total_formatted'] ?? ''));

$event = $dossier['event_date'] ?? null;
$event_date = $event instanceof DateTimeImmutable
    ? wp_date((string) get_option('date_format'), $event->getTimestamp())
    : '';

$shipping = Account::shippingLabel($dossier);
$location = (string) ($dossier['location'] ?? '');
$locked   = ! empty($dossier['is_locked']);
$url      = Personalisation::viewOrderUrl($dossier);

if ($url !== '' && $booking_id !== '' && ctype_digit($booking_id)) {
    $url .= '#lumiboo-booking-' . $booking_id;
}

$card_class = 'account-card account-card--' . sanitize_html_class($context);
if ($locked) {
    $card_class .= ' is-locked';
}

$card_id = ($booking_id !== '' && ctype_digit($booking_id))
    ? 'lumiboo-booking-' . $booking_id
    : '';
?>

<li<?php echo $card_id !== '' ? ' id="' . esc_attr($card_id) . '"' : ''; ?> class="<?php echo esc_attr($card_class); ?>">

    <h5 class="account-card-title"><?php echo esc_html($title); ?></h5>

    <dl class="account-card-meta">
        <?php if ($total !== '') : ?>
            <div class="account-card-row">
                <dt><?php echo esc_html(Account::text('label_total', 'Total')); ?></dt>
                <dd><?php echo wp_kses_post($total); ?></dd>
            </div>
        <?php endif; ?>

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

        <?php if ($gamme === Gamme::STUDIO) : ?>
            <div class="account-card-row">
                <dt><?php echo esc_html(Account::text('label_options', 'Options')); ?></dt>
                <dd>
                    <?php
                    echo esc_html(
                        ! empty($dossier['has_studio_option'])
                            ? Account::text('option_entreprise', 'Entreprise')
                            : Account::text('label_no_option', 'Sans option')
                    );
                    ?>
                </dd>
            </div>
        <?php endif; ?>
    </dl>

    <?php get_template_part('partials/account/progress', null, ['dossier' => $dossier]); ?>

    <div class="account-card-actions">
        <?php if ($locked && $url !== '') : ?>

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

        <a class="account-card-invoice" href="#" aria-disabled="true">
            <?php echo esc_html(Account::text('cta_invoice', 'Télécharger la facture')); ?>
        </a>
    </div>

</li>
