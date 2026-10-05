<?php

use Theme_base\Base;
use Theme_base\Form;

$preserved_name = isset($_GET['cf_name'])
    ? sanitize_text_field(wp_unslash($_GET['cf_name']))
    : '';

$preserved_email = isset($_GET['cf_email'])
    ? sanitize_email(wp_unslash($_GET['cf_email']))
    : '';

$preserved_phone = isset($_GET['cf_phone'])
    ? sanitize_text_field(wp_unslash($_GET['cf_phone']))
    : '';

$preserved_message = isset($_GET['cf_message'])
    ? sanitize_textarea_field(wp_unslash($_GET['cf_message']))
    : '';

$preserved_event_type = isset($_GET['cf_event_type'])
    ? (int) $_GET['cf_event_type']
    : 0;

$preserved_client_type = isset($_GET['cf_client_type'])
    ? sanitize_text_field(wp_unslash($_GET['cf_client_type']))
    : '';

$lp_pages = get_field('form_lp_pages', 'option') ?: [];
$client_type_labels = get_field('cf_client_type', 'option') ?: [];
$particulier_label = $client_type_labels['particulier'] ?? 'Particulier';
$entreprise_label  = $client_type_labels['entreprise']  ?? 'Entreprise';

$current_page_id = is_page() ? (int) get_queried_object_id() : 0;
$selected_event_type = $preserved_event_type ?: $current_page_id;

$site_key = Form::getRecaptchaSiteKey();
$redirect_url = home_url(add_query_arg(null, null));

$form_errors = [];
if (isset($_GET['form_errors'])) {
    $form_errors = array_filter(array_map('sanitize_key', explode(',', (string) $_GET['form_errors'])));
}
$form_error = isset($_GET['form_error']) ? sanitize_key(wp_unslash($_GET['form_error'])) : '';
$form_success = isset($_GET['form_success']);
?>

<form
    id="custom-contact-form"
    class="contact-form"
    method="POST"
    action="<?= esc_url(admin_url('admin-post.php')); ?>"
    data-site-key="<?= esc_attr($site_key); ?>">

    <input type="hidden" name="action" value="custom_contact_form">
    <?php wp_nonce_field('custom_contact_form', 'cf_nonce'); ?>

    <?php if (!empty($lp_pages)): ?>
        <div class="form-group dropdown">
            <label for="cf_event_type">
                <span class="visually-hidden"><?= esc_html__('Type d\'évènement', 'theme_base'); ?></span>
                <select name="cf_event_type" id="cf_event_type">
                    <option value="" <?= $selected_event_type ? '' : 'selected'; ?>>
                        <?= esc_html__('Type d\'évènement', 'theme_base'); ?>
                    </option>
                    <?php foreach ($lp_pages as $page):
                        $page_id = $page instanceof WP_Post ? (int) $page->ID : (int) ($page['ID'] ?? 0);
                        if ($page_id <= 0) {
                            continue;
                        }
                        $label = get_field('lp_form_title', $page_id);
                        if (!is_string($label) || $label === '') {
                            $label = get_the_title($page_id);
                        }
                        $selected = ($selected_event_type === $page_id) ? 'selected' : '';
                    ?>
                        <option value="<?= esc_attr($page_id); ?>" <?= $selected; ?>>
                            <?= esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="svg-container chevron">
                <?= Base::inline_svg('simple-chevron-right.svg'); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-group client-type" role="radiogroup" aria-label="<?= esc_attr__('Type de client', 'theme_base'); ?>">
        <label>
            <input type="radio" name="cf_client_type" value="particulier" <?= checked($preserved_client_type, 'particulier', false); ?>>
            <span><?= esc_html($particulier_label); ?></span>
        </label>
        <label>
            <input type="radio" name="cf_client_type" value="entreprise" <?= checked($preserved_client_type, 'entreprise', false); ?>>
            <span><?= esc_html($entreprise_label); ?></span>
        </label>
    </div>

    <div class="form-group name">
        <label for="cf_name">
            <span class="visually-hidden"><?= esc_html__('Nom ou nom de l\'entreprise', 'theme_base'); ?></span>
            <input type="text" name="cf_name" id="cf_name" placeholder="<?= esc_attr__('Nom ou nom de l\'entreprise', 'theme_base'); ?>" value="<?= esc_attr($preserved_name); ?>" required>
        </label>
    </div>

    <div class="form-row mail-phone">
        <div class="form-group email">
            <label for="cf_email">
                <span class="visually-hidden"><?= esc_html__('Mail', 'theme_base'); ?></span>
                <input type="email" name="cf_email" id="cf_email" placeholder="<?= esc_attr__('Mail', 'theme_base'); ?>" value="<?= esc_attr($preserved_email); ?>" required>
            </label>
        </div>
        <div class="form-group phone">
            <label for="cf_phone">
                <span class="visually-hidden"><?= esc_html__('Téléphone', 'theme_base'); ?></span>
                <input type="tel" name="cf_phone" id="cf_phone" placeholder="<?= esc_attr__('Téléphone', 'theme_base'); ?>" value="<?= esc_attr($preserved_phone); ?>" required>
            </label>
        </div>
    </div>

    <div class="form-group message">
        <label for="cf_message">
            <span class="visually-hidden"><?= esc_html__('Message', 'theme_base'); ?></span>
            <textarea name="cf_message" id="cf_message" placeholder="<?= esc_attr__('Message', 'theme_base'); ?>" required><?= esc_textarea($preserved_message); ?></textarea>
        </label>
    </div>

    <div style="position: absolute; left: -9999px;" aria-hidden="true">
        <input type="text" name="cf_honeypot" id="cf_honeypot" tabindex="-1" autocomplete="off">
    </div>

    <input type="hidden" name="cf_recaptcha_token" id="cf_recaptcha_token" value="">
    <input type="hidden" name="cf_redirect" value="<?= esc_url($redirect_url); ?>">

    <?php if (!empty($form_errors) || $form_error || $form_success): ?>
        <div class="form-messages">
            <?php if (!empty($form_errors)): ?>
                <div class="form-error">
                    <?php if (in_array('name', $form_errors, true)): ?>
                        <p><?= esc_html__('Veuillez entrer un nom valide.', 'theme_base'); ?></p>
                    <?php endif; ?>
                    <?php if (in_array('email', $form_errors, true)): ?>
                        <p><?= esc_html__('Veuillez entrer un email valide.', 'theme_base'); ?></p>
                    <?php endif; ?>
                    <?php if (in_array('phone', $form_errors, true)): ?>
                        <p><?= esc_html__('Veuillez entrer un numéro de téléphone valide.', 'theme_base'); ?></p>
                    <?php endif; ?>
                    <?php if (in_array('message', $form_errors, true)): ?>
                        <p><?= esc_html__('Le message ne peut pas être vide.', 'theme_base'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($form_error === 'security'): ?>
                <div class="form-error"><p><?= esc_html__('Vérification de sécurité échouée. Veuillez réessayer.', 'theme_base'); ?></p></div>
            <?php elseif ($form_error === 'spam'): ?>
                <div class="form-error"><p><?= esc_html__('Spam détecté.', 'theme_base'); ?></p></div>
            <?php elseif ($form_error === 'email_failed'): ?>
                <div class="form-error"><p><?= esc_html__('Erreur lors de l\'envoi. Veuillez réessayer plus tard.', 'theme_base'); ?></p></div>
            <?php endif; ?>

            <?php if ($form_success): ?>
                <div class="form-success">
                    <p><?= esc_html__('Votre message a été envoyé avec succès. Nous vous répondrons bientôt.', 'theme_base'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="button-background-primary button-background-animation form-submit">
        <input type="submit" name="cf_submit" value="<?= esc_attr__('Envoyer', 'theme_base'); ?>" class="btn">
        <span class="hover-bg"></span>
    </div>
</form>
