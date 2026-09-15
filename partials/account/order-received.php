<?php

/**
 * Accès à l'espace compte depuis la confirmation de commande.
 *
 * Le compte est obligatoire et créé automatiquement dans le parcours : ce
 * bloc mène le client à son espace, ou lui explique comment y accéder quand
 * sa session n'est pas ouverte.
 *
 * @var array $args {
 *     @type WC_Order $order
 *     @type bool     $is_owner         Client connecté et propriétaire de la commande.
 *     @type bool     $has_account      Commande rattachée à un compte.
 *     @type bool     $password_pending Mot de passe pas encore défini par le client.
 * }
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$order       = $args['order'] ?? null;
$is_owner    = ! empty($args['is_owner']);
$has_account = ! empty($args['has_account']);
$pending     = ! empty($args['password_pending']);

if (! $order instanceof WC_Order) {
    return;
}

if ($pending) {
    // Le compte vient d'être créé : le client doit passer par l'e-mail reçu
    // pour définir son mot de passe, même si sa session est déjà ouverte.
    $text = Account::text('thankyou_text_new', __('Pour créer votre espace, rendez-vous sur votre boîte mail : un lien pour définir votre mot de passe vous a été envoyé.', 'theme_base'));
    $cta  = $is_owner
        ? Account::text('thankyou_cta_logged', __('Accéder à mon espace Lumiboo', 'theme_base'))
        : Account::text('thankyou_cta_new', __('Me connecter', 'theme_base'));
    $url  = $is_owner ? wc_get_account_endpoint_url('dashboard') : wc_get_page_permalink('myaccount');
} elseif ($is_owner) {
    $text = Account::text('thankyou_text_logged', __('Retrouvez votre réservation dans votre espace et personnalisez-la jusqu’à sept jours avant votre événement.', 'theme_base'));
    $cta  = Account::text('thankyou_cta_logged', __('Accéder à mon espace Lumiboo', 'theme_base'));
    $url  = wc_get_account_endpoint_url('dashboard');
} elseif ($has_account) {
    $text = Account::text('thankyou_text_new', __('Pour créer votre espace, rendez-vous sur votre boîte mail : un lien pour définir votre mot de passe vous a été envoyé.', 'theme_base'));
    $cta  = Account::text('thankyou_cta_new', __('Me connecter', 'theme_base'));
    $url  = wc_get_page_permalink('myaccount');
} else {
    $text = Account::text('thankyou_text_guest', __('Créez votre espace avec l’adresse e-mail utilisée pour cette commande afin de suivre votre réservation et de la personnaliser.', 'theme_base'));
    $cta  = Account::text('thankyou_cta_guest', __('Créer mon espace', 'theme_base'));
    $url  = wc_get_page_permalink('myaccount');
}
?>

<section class="account-thankyou">
    <h2 class="account-thankyou-title">
        <?= esc_html(Account::text('thankyou_title', __('Votre espace Lumiboo', 'theme_base'))); ?>
    </h2>

    <p class="account-thankyou-text"><?= esc_html($text); ?></p>

    <?php if ($url) : ?>
        <div class="button-container button-background-tertiary">
            <a class="btn" href="<?= esc_url($url); ?>"><?= esc_html($cta); ?></a>
        </div>
    <?php endif; ?>
</section>
