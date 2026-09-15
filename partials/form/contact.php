<?php

/**
 * Formulaire « Une question avant de réserver ? » (§13.2, R24).
 *
 * Type d'événement, choix particulier/entreprise, nom ou nom de l'entreprise,
 * email, téléphone, message. Nom, email et message sont obligatoires ;
 * téléphone et qualification restent facultatifs.
 *
 * Le choix particulier/entreprise est une information de contact : il ne
 * change ni le catalogue, ni les options, ni le checkout. Il arrive
 * présélectionné sur « Entreprise » quand la page fait partie des pages
 * d'atterrissage entreprise désignées dans les options du thème.
 *
 * Les libellés ne sont pas affichés — le wireframe n'utilise que des textes
 * indicatifs — mais restent rattachés à chaque champ pour la lecture vocale.
 */

use Theme_base\Forms;

defined('ABSPATH') || exit;

$form   = 'contact';
$fields = Forms::fields($form);
$notice = Forms::notice($form);

$values = is_array($notice) ? $notice['values'] : [];
$errors = is_array($notice) ? $notice['errors'] : [];

$value    = static fn(string $name): string => (string) ($values[$name] ?? '');
$invalid  = static fn(string $name): string => isset($errors[$name]) ? 'true' : 'false';
$audience = $value('audience') !== '' ? $value('audience') : Forms::defaultAudience();
?>

<form
    id="<?= esc_attr(Forms::anchor($form)); ?>"
    class="lumiboo-form lumiboo-form--contact"
    method="post"
    action="<?= esc_url(admin_url('admin-post.php')); ?>"
    novalidate>

    <?php get_template_part('partials/form/notice', null, ['notice' => $notice]); ?>
    <?php get_template_part('partials/form/security', null, ['form' => $form]); ?>

    <div class="form-group form-group--select">
        <label class="visually-hidden" for="contact_event_type"><?= esc_html($fields['event_type']['label']); ?></label>
        <select name="event_type" id="contact_event_type">
            <option value=""><?= esc_html($fields['event_type']['label']); ?></option>
            <?php foreach (Forms::choices('event_types') as $key => $label) : ?>
                <option value="<?= esc_attr($key); ?>" <?php selected($value('event_type'), $key); ?>><?= esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <fieldset class="form-group form-group--audience">
        <legend class="visually-hidden"><?= esc_html($fields['audience']['label']); ?></legend>
        <?php foreach (Forms::choices('audiences') as $key => $label) : ?>
            <span class="form-choice">
                <input
                    type="radio"
                    name="audience"
                    id="contact_audience_<?= esc_attr($key); ?>"
                    value="<?= esc_attr($key); ?>"
                    <?php checked($audience, $key); ?>>
                <label for="contact_audience_<?= esc_attr($key); ?>"><?= esc_html($label); ?></label>
            </span>
        <?php endforeach; ?>
    </fieldset>

    <div class="form-group">
        <label class="visually-hidden" for="contact_name"><?= esc_html($fields['name']['label']); ?></label>
        <input
            type="text"
            name="name"
            id="contact_name"
            value="<?= esc_attr($value('name')); ?>"
            placeholder="<?= esc_attr($fields['name']['label']); ?>"
            maxlength="<?= esc_attr((string) $fields['name']['max']); ?>"
            autocomplete="name"
            aria-invalid="<?= esc_attr($invalid('name')); ?>"
            required>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="visually-hidden" for="contact_email"><?= esc_html($fields['email']['label']); ?></label>
            <input
                type="email"
                name="email"
                id="contact_email"
                value="<?= esc_attr($value('email')); ?>"
                placeholder="<?= esc_attr__('Mail', 'theme_base'); ?>"
                maxlength="<?= esc_attr((string) $fields['email']['max']); ?>"
                autocomplete="email"
                aria-invalid="<?= esc_attr($invalid('email')); ?>"
                required>
        </div>

        <div class="form-group">
            <label class="visually-hidden" for="contact_phone"><?= esc_html($fields['phone']['label']); ?></label>
            <input
                type="tel"
                name="phone"
                id="contact_phone"
                value="<?= esc_attr($value('phone')); ?>"
                placeholder="<?= esc_attr($fields['phone']['label']); ?>"
                maxlength="<?= esc_attr((string) $fields['phone']['max']); ?>"
                autocomplete="tel"
                aria-invalid="<?= esc_attr($invalid('phone')); ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="visually-hidden" for="contact_message"><?= esc_html($fields['message']['label']); ?></label>
        <textarea
            name="message"
            id="contact_message"
            rows="5"
            placeholder="<?= esc_attr($fields['message']['label']); ?>"
            maxlength="<?= esc_attr((string) $fields['message']['max']); ?>"
            aria-invalid="<?= esc_attr($invalid('message')); ?>"
            required><?= esc_textarea($value('message')); ?></textarea>
    </div>

    <div class="button-background-primary button-background-animation form-submit">
        <button type="submit" class="btn"><?= esc_html(Forms::text('contact_submit', __('Envoyer', 'theme_base'))); ?></button>
        <span class="hover-bg"></span>
    </div>
</form>
