<?php

/**
 * Bloc « Informations personnelles ».
 *
 * Les champs sont d'abord en lecture seule ; « Modifier mes informations »
 * ouvre l'édition, une action distincte enregistre (réponse Q068). La
 * sauvegarde ne réécrit jamais les commandes existantes.
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$user = wp_get_current_user();

if (! $user || ! $user->ID) {
    return;
}

$customer = new WC_Customer($user->ID);

$identity = [
    'first_name'    => [__('Nom', 'theme_base'), $customer->get_billing_first_name(), true],
    'last_name'     => [__('Prénom', 'theme_base'), $customer->get_billing_last_name(), true],
    'billing_phone' => [__('Téléphone', 'theme_base'), $customer->get_billing_phone(), true],
    'billing_email' => [__('E-mail', 'theme_base'), $customer->get_billing_email(), true],
    'billing_company' => [__('Nom de l’entreprise', 'theme_base'), $customer->get_billing_company(), false],
];

$address = [
    'billing_country'   => [__('Pays', 'theme_base'), $customer->get_billing_country(), true],
    'billing_address_1' => [__('Numéro et nom de rue', 'theme_base'), $customer->get_billing_address_1(), true],
    'billing_city'      => [__('Ville', 'theme_base'), $customer->get_billing_city(), true],
    'billing_postcode'  => [__('Code postal', 'theme_base'), $customer->get_billing_postcode(), true],
];
?>

<section class="account-profile" data-account-profile>
    <h3 class="account-section-title"><?= esc_html(Account::text('profile_title', __('Informations personnelles', 'theme_base'))); ?></h3>

    <form method="post" action="<?= esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="lumiboo_save_profile">
        <?php wp_nonce_field('lumiboo_profile'); ?>

        <div class="account-profile-grid">
            <?php foreach (['identity' => $identity, 'address' => $address] as $group => $fields) : ?>
                <div class="account-profile-group">
                    <h4 class="account-profile-legend">
                        <?= esc_html($group === 'identity'
                            ? Account::text('profile_identity', __('Identité', 'theme_base'))
                            : Account::text('profile_address', __('Adresse', 'theme_base'))); ?>
                    </h4>

                    <?php foreach ($fields as $name => $field) : ?>
                        <?php [$label, $value, $required] = $field; ?>
                        <p class="account-profile-field">
                            <label for="lumiboo-<?= esc_attr($name); ?>">
                                <?= esc_html($label); ?><?= $required ? '*' : ''; ?>
                            </label>
                            <input
                                id="lumiboo-<?= esc_attr($name); ?>"
                                type="<?= $name === 'billing_email' ? 'email' : 'text'; ?>"
                                name="<?= esc_attr($name); ?>"
                                value="<?= esc_attr($value); ?>"
                                <?= $required ? 'required' : ''; ?>
                                <?= $name === 'billing_email' ? 'readonly' : ''; ?>
                                readonly
                                data-account-profile-input>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="account-profile-actions">
            <div class="button-container button-background-tertiary" data-account-profile-open>
                <button type="button" class="btn">
                    <?= esc_html(Account::text('profile_edit', __('Modifier mes informations', 'theme_base'))); ?>
                </button>
            </div>

            <div class="button-container button-background-tertiary" data-account-profile-submit hidden>
                <button type="submit" class="btn">
                    <?= esc_html(Account::text('profile_save', __('Enregistrer mes informations', 'theme_base'))); ?>
                </button>
            </div>

            <div class="button-container button-background-secondary" data-account-profile-cancel hidden>
                <button type="button" class="btn">
                    <?= esc_html(Account::text('profile_cancel', __('Annuler', 'theme_base'))); ?>
                </button>
            </div>
        </div>
    </form>

    <p class="account-profile-links">
        <a href="<?= esc_url(wc_get_account_endpoint_url('edit-account')); ?>">
            <?= esc_html(Account::text('profile_password', __('Modifier mon mot de passe', 'theme_base'))); ?>
        </a>
        <a href="<?= esc_url(wc_logout_url(wc_get_page_permalink('myaccount'))); ?>">
            <?= esc_html(Account::text('profile_logout', __('Se déconnecter', 'theme_base'))); ?>
        </a>
    </p>
</section>
