<?php

/**
 * Personnalisation sous le détail de commande natif.
 *
 * Une section par réservation de cette commande, tri chronologique
 * (date de début). Chaque section a son propre formulaire.
 *
 * @var array $args {
 *     @type array $bookings Dossiers per-booking de la commande courante.
 * }
 */

use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$bookings = is_array($args['bookings'] ?? null) ? $args['bookings'] : [];

if ($bookings === []) {
    return;
}

usort($bookings, static function (array $a, array $b): int {
    $left  = $a['event_date'] ?? null;
    $right = $b['event_date'] ?? null;
    $left  = $left instanceof DateTimeImmutable ? $left->getTimestamp() : PHP_INT_MAX;
    $right = $right instanceof DateTimeImmutable ? $right->getTimestamp() : PHP_INT_MAX;

    return $left <=> $right;
});
?>

<section class="account-perso">

    <?php Personalisation::renderNotice(); ?>

    <h2 class="account-perso-title"><?php echo esc_html(Personalisation::text('title', 'Personnalise ton Lumiboo')); ?></h2>

    <p class="account-perso-intro">
        <?php echo esc_html(Personalisation::text('intro', 'Une carte par Lumiboo commandé. Tout ce qui est valide est enregistré, même partiellement.')); ?>
    </p>

    <?php foreach ($bookings as $dossier) : ?>
        <?php
        get_template_part('partials/account/perso-booking', null, [
            'dossier'   => $dossier,
            'templates' => Personalisation::templates(),
        ]);
        ?>
    <?php endforeach; ?>

</section>
