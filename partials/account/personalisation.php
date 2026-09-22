<?php

/**
 * Formulaire de personnalisation sous le détail de commande natif.
 *
 * Un formulaire, deux boutons submit (Q054). Studio iff has_studio_option (Q052).
 * Uploads : placeholder chunk 8. Carte non cliquable n'applique pas ici.
 *
 * @var array $args {
 *     @type array $dossier Normalisé par Theme_base\Dossier.
 * }
 */

use Theme_base\Deadline;
use Theme_base\Personalisation;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;

if (! is_array($dossier)) {
    return;
}

$order_id  = (string) ($dossier['id'] ?? '');
$paid      = ! empty($dossier['paid']);
$locked    = Deadline::isLocked($dossier);
$editable  = $paid && ! $locked;
$units     = (array) ($dossier['units'] ?? []);
$templates = Personalisation::templates();
$action    = admin_url('admin-post.php');
?>

<section class="account-perso">

    <?php Personalisation::renderNotice(); ?>

    <h2 class="account-perso-title"><?php echo esc_html(Personalisation::text('title', 'Personnalise ton Lumiboo')); ?></h2>

    <?php if (! $paid) : ?>

        <p class="account-perso-intro"><?php echo esc_html(Personalisation::text('unpaid_intro', 'En attente de paiement. La personnalisation s’ouvrira dès validation.')); ?></p>

    <?php else : ?>

        <p class="account-perso-intro">
            <?php echo esc_html($locked
                ? Personalisation::text('locked_intro', 'Modifications fermées à J-7. Consultez votre personnalisation.')
                : Personalisation::text('intro', 'Une carte par Lumiboo commandé. Tout ce qui est valide est enregistré, même partiellement.')
            ); ?>
        </p>

        <form
            class="account-perso-form"
            action="<?php echo esc_url($action); ?>"
            method="post"
            novalidate>

            <input type="hidden" name="action" value="lumiboo_save_personalisation">
            <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
            <?php wp_nonce_field('lumiboo_perso_' . $order_id); ?>

            <?php if ($editable) : ?>
                <?php get_template_part('partials/account/perso-submit'); ?>
            <?php endif; ?>

            <?php foreach ($units as $index => $unit) : ?>
                <?php
                get_template_part('partials/account/perso-unit', null, [
                    'index'     => (int) $index,
                    'unit'      => is_array($unit) ? $unit : [],
                    'locked'    => $locked,
                    'templates' => $templates,
                ]);
                ?>
            <?php endforeach; ?>

            <?php if (! empty($dossier['has_studio_option'])) : ?>
                <?php
                get_template_part('partials/account/perso-studio', null, [
                    'dossier' => $dossier,
                    'locked'  => $locked,
                ]);
                ?>
            <?php endif; ?>

            <?php if ($editable) : ?>
                <?php get_template_part('partials/account/perso-submit'); ?>
            <?php endif; ?>

        </form>

    <?php endif; ?>

</section>
