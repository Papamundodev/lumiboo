<?php

/**
 * Tableau de bord « Mon espace Lumiboo ».
 *
 * Deux buckets (réservations en cours / passées), triés par date de début
 * de la réservation. Dans chaque bucket, les réservations sont groupées
 * par commande : le numéro de commande est affiché à la première
 * occurrence de cette commande, puis une carte par réservation.
 */

use Theme_base\Account;
use Theme_base\Dossier;
use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$start_timestamp = static function (array $dossier): ?int {
    $event = $dossier['event_date'] ?? null;

    return $event instanceof DateTimeImmutable ? $event->getTimestamp() : null;
};

$sort_by_start = static function (array $bookings, bool $descending) use ($start_timestamp): array {
    usort($bookings, static function (array $a, array $b) use ($start_timestamp, $descending): int {
        $left  = $start_timestamp($a);
        $right = $start_timestamp($b);

        if ($left === null && $right === null) {
            return 0;
        }

        if ($left === null) {
            return 1;
        }

        if ($right === null) {
            return -1;
        }

        return $descending ? $right <=> $left : $left <=> $right;
    });

    return $bookings;
};

$group_by_order = static function (array $bookings): array {
    $groups = [];

    foreach ($bookings as $dossier) {
        $order_id = (string) ($dossier['order_id'] ?? '');

        if (! isset($groups[$order_id])) {
            $number = (string) ($dossier['reference'] ?? '');

            $groups[$order_id] = [
                'number'   => $number !== '' ? $number : $order_id,
                'bookings' => [],
            ];
        }

        $groups[$order_id]['bookings'][] = $dossier;
    }

    return array_values($groups);
};

$current = [];
$past    = [];

foreach (Dossier::bookingsForCurrentUser() as $dossier) {
    if (! empty($dossier['is_past'])) {
        $past[] = $dossier;
        continue;
    }

    $current[] = $dossier;
}

$buckets = [
    'current' => $group_by_order($sort_by_start($current, false)),
    'past'    => $group_by_order($sort_by_start($past, true)),
];

$has_any = $buckets['current'] !== [] || $buckets['past'] !== [];
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
            <h4 class="account-section-title"><?php echo esc_html(Account::text('section_current', 'Réservations en cours')); ?></h4>

            <?php if ($buckets['current'] === []) : ?>
                <p class="account-empty"><?php echo esc_html(Account::text('empty_current', 'Aucune réservation à venir.')); ?></p>
            <?php else : ?>
                <?php foreach ($buckets['current'] as $group) : ?>
                    <h2 class="account-order-title"><?php echo esc_html($group['number']); ?></h2>
                    <ul class="account-cards">
                        <?php foreach ($group['bookings'] as $dossier) : ?>
                            <?php get_template_part('partials/account/card', null, ['dossier' => $dossier, 'context' => 'current']); ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>

            <h4 class="account-section-title"><?php echo esc_html(Account::text('section_past', 'Réservations passées')); ?></h4>

            <?php if ($buckets['past'] === []) : ?>
                <p class="account-empty"><?php echo esc_html(Account::text('empty_past', 'Vos événements passés apparaîtront ici.')); ?></p>
            <?php else : ?>
                <?php foreach ($buckets['past'] as $group) : ?>
                    <h2 class="account-order-title"><?php echo esc_html($group['number']); ?></h2>
                    <ul class="account-cards">
                        <?php foreach ($group['bookings'] as $dossier) : ?>
                            <?php get_template_part('partials/account/card', null, ['dossier' => $dossier, 'context' => 'past']); ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>
