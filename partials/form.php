<?php

// Values coming back from a redirect are already sanitized before being added to the URL.
$preserved_name = '';
if (isset($_GET['cf_name'])) {
    $preserved_name = sanitize_text_field(wp_unslash(urldecode($_GET['cf_name'])));
}

$preserved_email = '';
if (isset($_GET['cf_email'])) {
    $preserved_email = sanitize_email(wp_unslash(urldecode($_GET['cf_email'])));
}

$preserved_phone = '';
if (isset($_GET['cf_phone'])) {
    $preserved_phone = sanitize_text_field(wp_unslash(urldecode($_GET['cf_phone'])));
}

$preserved_message = '';
if (isset($_GET['cf_message'])) {
    $preserved_message = sanitize_textarea_field(wp_unslash(urldecode($_GET['cf_message'])));
}
?>

<form id="custom-contact-form" method="POST" action="<?= esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="custom_contact_form">
    <?php wp_nonce_field('custom_contact_form', 'cf_nonce'); ?>

    <div class="form-group name">
        <label for="cf_name">
            <span class="visually-hidden"><?= esc_html__('Nom', 'theme_base'); ?></span>
            <input type="text" name="cf_name" id="cf_name" placeholder="<?= esc_attr__('Votre nom*', 'theme_base'); ?>" value="<?= esc_attr($preserved_name); ?>" required>
        </label>
    </div>

    <div class="form-group email">
        <label for="cf_email">
            <span class="visually-hidden"><?= esc_html__('Email', 'theme_base'); ?></span>
            <input type="email" name="cf_email" id="cf_email" placeholder="<?= esc_attr__('Email*', 'theme_base'); ?>" value="<?= esc_attr($preserved_email); ?>" required>
        </label>
    </div>

    <div class="form-group phone">
        <label for="cf_phone">
            <span class="visually-hidden"><?= esc_html__('Téléphone', 'theme_base'); ?></span>
            <input type="tel" name="cf_phone" id="cf_phone" placeholder="<?= esc_attr__('Téléphone*', 'theme_base'); ?>" value="<?= esc_attr($preserved_phone); ?>" required>
        </label>
    </div>

    <div class="form-group message">
        <label for="cf_message">
            <span class="visually-hidden"><?= esc_html__('Message', 'theme_base'); ?></span>
            <textarea name="cf_message" id="cf_message" placeholder="<?= esc_attr__('Message*', 'theme_base'); ?>" required><?= esc_textarea($preserved_message); ?></textarea>
        </label>
    </div>

    <!-- Honeypot anti-spam (hidden from assistive technologies). Must stay empty. -->
    <div style="position: absolute; left: -9999px;" aria-hidden="true">
        <input type="text" name="cf_honeypot" id="cf_honeypot" tabindex="-1" autocomplete="off">
    </div>

    <!-- reCAPTCHA v3 token (populated by JS before submit). Separate from honeypot. -->
    <input type="hidden" name="cf_recaptcha_token" id="cf_recaptcha_token" value="">

    <div class="button-background-primary button-background-animation form-submit">
        <input type="submit" name="cf_submit" value="<?= esc_attr__('Envoyer', 'theme_base'); ?>" class="btn">
        <span class="hover-bg"></span>
    </div>
</form>
