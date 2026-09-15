<?php

/**
 * Formulaire « Demander un devis » au-delà de 24 heures (§8.5, R06).
 *
 * Nom, prénom, nom de l'entreprise, téléphone, email, description libre,
 * produit intéressé, nombre d'heures et type d'événement.
 *
 * Le nombre d'heures appartient à la demande de devis : il ne réintroduit
 * aucun choix horaire dans la réservation standard de 24 heures. L'envoi
 * transmet la demande à l'équipe et affiche un accusé de réception — il ne
 * crée ni commande, ni réservation ferme, ni occupation de stock.
 *
 * Le produit est présélectionné quand le formulaire est ouvert depuis une
 * fiche, conformément à R06 : la demande reprend le contexte déjà saisi.
 *
 * L'obligation champ par champ reste à confirmer par Lumiboo. L'état actuel
 * — nom, prénom, email et description obligatoires — est modifiable par le
 * filtre `lumiboo_form_fields` sans toucher au gabarit.
 */

use Theme_base\Forms;

defined('ABSPATH') || exit;

$form   = 'devis';
$fields = Forms::fields($form);
$notice = Forms::notice($form);

$values   = is_array($notice) ? $notice['values'] : [];
$errors   = is_array($notice) ? $notice['errors'] : [];
$products = Forms::choices('products');

$value   = static fn(string $name): string => (string) ($values[$name] ?? '');
$invalid = static fn(string $name): string => isset($errors[$name]) ? 'true' : 'false';
$product = $value('product') !== '' ? $value('product') : Forms::contextProduct();
?>

<form
    id="<?= esc_attr(Forms::anchor($form)); ?>"
    class="lumiboo-form lumiboo-form--devis"
    method="post"
    action="<?= esc_url(admin_url('admin-post.php')); ?>"
    novalidate>

    <?php get_template_part('partials/form/notice', null, ['notice' => $notice]); ?>
    <?php get_template_part('partials/form/security', null, ['form' => $form]); ?>

    <div class="form-row">
        <div class="form-group">
            <label class="visually-hidden" for="devis_last_name"><?= esc_html($fields['last_name']['label']); ?></label>
            <input
                type="text"
                name="last_name"
                id="devis_last_name"
                value="<?= esc_attr($value('last_name')); ?>"
                placeholder="<?= esc_attr($fields['last_name']['label']); ?>"
                maxlength="<?= esc_attr((string) $fields['last_name']['max']); ?>"
                autocomplete="family-name"
                aria-invalid="<?= esc_attr($invalid('last_name')); ?>"
                required>
        </div>

        <div class="form-group">
            <label class="visually-hidden" for="devis_first_name"><?= esc_html($fields['first_name']['label']); ?></label>
            <input
                type="text"
                name="first_name"
                id="devis_first_name"
                value="<?= esc_attr($value('first_name')); ?>"
                placeholder="<?= esc_attr($fields['first_name']['label']); ?>"
                maxlength="<?= esc_attr((string) $fields['first_name']['max']); ?>"
                autocomplete="given-name"
                aria-invalid="<?= esc_attr($invalid('first_name')); ?>"
                required>
        </div>
    </div>

    <div class="form-group">
        <label class="visually-hidden" for="devis_company"><?= esc_html($fields['company']['label']); ?></label>
        <input
            type="text"
            name="company"
            id="devis_company"
            value="<?= esc_attr($value('company')); ?>"
            placeholder="<?= esc_attr($fields['company']['label']); ?>"
            maxlength="<?= esc_attr((string) $fields['company']['max']); ?>"
            autocomplete="organization"
            aria-invalid="<?= esc_attr($invalid('company')); ?>">
    </div>

    <div class="form-row">
        <div class="form-group">
            <label class="visually-hidden" for="devis_email"><?= esc_html($fields['email']['label']); ?></label>
            <input
                type="email"
                name="email"
                id="devis_email"
                value="<?= esc_attr($value('email')); ?>"
                placeholder="<?= esc_attr__('Mail', 'theme_base'); ?>"
                maxlength="<?= esc_attr((string) $fields['email']['max']); ?>"
                autocomplete="email"
                aria-invalid="<?= esc_attr($invalid('email')); ?>"
                required>
        </div>

        <div class="form-group">
            <label class="visually-hidden" for="devis_phone"><?= esc_html($fields['phone']['label']); ?></label>
            <input
                type="tel"
                name="phone"
                id="devis_phone"
                value="<?= esc_attr($value('phone')); ?>"
                placeholder="<?= esc_attr($fields['phone']['label']); ?>"
                maxlength="<?= esc_attr((string) $fields['phone']['max']); ?>"
                autocomplete="tel"
                aria-invalid="<?= esc_attr($invalid('phone')); ?>">
        </div>
    </div>

    <div class="form-row">
        <?php if ($products !== []) : ?>
            <div class="form-group form-group--select">
                <label class="visually-hidden" for="devis_product"><?= esc_html($fields['product']['label']); ?></label>
                <select name="product" id="devis_product">
                    <option value=""><?= esc_html($fields['product']['label']); ?></option>
                    <?php foreach ($products as $key => $label) : ?>
                        <option value="<?= esc_attr($key); ?>" <?php selected($product, $key); ?>><?= esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group form-group--select">
            <label class="visually-hidden" for="devis_event_type"><?= esc_html($fields['event_type']['label']); ?></label>
            <select name="event_type" id="devis_event_type">
                <option value=""><?= esc_html($fields['event_type']['label']); ?></option>
                <?php foreach (Forms::choices('event_types') as $key => $label) : ?>
                    <option value="<?= esc_attr($key); ?>" <?php selected($value('event_type'), $key); ?>><?= esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="visually-hidden" for="devis_hours"><?= esc_html($fields['hours']['label']); ?></label>
        <input
            type="number"
            name="hours"
            id="devis_hours"
            value="<?= esc_attr($value('hours')); ?>"
            placeholder="<?= esc_attr($fields['hours']['label']); ?>"
            min="<?= esc_attr((string) $fields['hours']['min']); ?>"
            max="<?= esc_attr((string) $fields['hours']['max']); ?>"
            step="1"
            inputmode="numeric"
            aria-invalid="<?= esc_attr($invalid('hours')); ?>">
    </div>

    <div class="form-group">
        <label class="visually-hidden" for="devis_message"><?= esc_html($fields['message']['label']); ?></label>
        <textarea
            name="message"
            id="devis_message"
            rows="5"
            placeholder="<?= esc_attr($fields['message']['label']); ?>"
            maxlength="<?= esc_attr((string) $fields['message']['max']); ?>"
            aria-invalid="<?= esc_attr($invalid('message')); ?>"
            required><?= esc_textarea($value('message')); ?></textarea>
    </div>

    <div class="button-background-primary button-background-animation form-submit">
        <button type="submit" class="btn"><?= esc_html(Forms::text('devis_submit', __('Demander un devis', 'theme_base'))); ?></button>
        <span class="hover-bg"></span>
    </div>
</form>
