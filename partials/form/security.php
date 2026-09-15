<?php

/**
 * Champs communs aux deux formulaires publics.
 *
 * Le jeton lie la soumission à la session du visiteur et à sa provenance ; le
 * champ piège reste vide chez un visiteur réel et trahit un robot qui remplit
 * tout ce qu'il trouve. Les deux sont revérifiés côté serveur par
 * `Theme_base\Forms::handle()` : le rendu seul ne protège rien.
 *
 * Attention en cas de cache de page : un jeton servi depuis le cache peut
 * avoir expiré. Le formulaire ne perd alors pas la saisie, il demande un
 * nouvel envoi.
 *
 * @var array{form: string} $args
 */

use Theme_base\Forms;

defined('ABSPATH') || exit;

$form = (string) ($args['form'] ?? '');

// Vide tant que les clés ne sont pas saisies : la protection se met en route
// depuis l'administration, sans intervention sur le thème.
$recaptcha = Forms::enqueueRecaptcha();
?>

<input type="hidden" name="action" value="<?= esc_attr(Forms::action($form)); ?>">
<input type="hidden" name="lum_form" value="<?= esc_attr($form); ?>">
<?php wp_nonce_field(Forms::action($form), 'lum_nonce'); ?>

<div class="form-trap" aria-hidden="true">
    <label for="lum_hp_<?= esc_attr($form); ?>"><?= esc_html__('Laissez ce champ vide', 'theme_base'); ?></label>
    <input type="text" name="lum_hp" id="lum_hp_<?= esc_attr($form); ?>" value="" tabindex="-1" autocomplete="off">
</div>

<?php if ($recaptcha !== '') : ?>
    <input type="hidden" name="lum_recaptcha" value="" data-action="<?= esc_attr(Forms::action($form)); ?>">
<?php endif; ?>
