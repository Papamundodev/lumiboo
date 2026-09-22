<?php

/**
 * Carte Lumiboo sur la page de remerciement (chunk 4).
 *
 * Rendue par `Theme_base\Account::renderOrderReceived()` via
 * `get_template_part()` au hook `woocommerce_thankyou` priorité 5 :
 * entre l'aperçu de commande (imprimé par
 * `woocommerce/checkout/thankyou.php`) et le tableau de détails
 * (`woocommerce_order_details_table` à la priorité 10).
 *
 * Trois textes sont pilotés depuis ACF (page d'options « Lumiboo »
 * enregistrée par `Base::registerOptionsPages()`, slug
 * `lumiboo-settings`) — cf. `acf-export-thankyou.json` à importer via
 * *Custom Fields → Tools → Import*. Les valeurs par défaut ci-dessous
 * sont les mêmes que celles du groupe ACF ; elles s'affichent tant que
 * le champ n'est pas rempli. TranslatePress traduit les textes au
 * rendu, ce qui couvre le besoin multilingue.
 *
 * @var array $args {
 *     @type \WC_Order $order
 *     @type bool      $is_owner         Session ouverte et propriétaire de la commande.
 *     @type bool      $has_account      Commande rattachée à un compte.
 *     @type bool      $password_pending Compte créé, mot de passe non défini.
 * }
 */

defined('ABSPATH') || exit;

$order = $args['order'] ?? null;

if (! $order instanceof WC_Order) {
    return;
}

// Textes ACF (page d'options `lumiboo-settings`) avec fallback FR.
$acf = static function (string $name, string $fallback): string {
    if (! function_exists('get_field')) {
        return $fallback;
    }
    $value = get_field($name, 'option');
    $value = is_string($value) ? trim($value) : '';
    return $value !== '' ? $value : $fallback;
};

$heading   = $acf(
    'lumiboo_thankyou_heading',
    'Bonjour'
);
$body      = $acf(
    'lumiboo_thankyou_body',
    'Votre compte a été créé, consultez votre e-mail pour définir le mot de passe de votre compte.'
);
$cta_label = $acf(
    'lumiboo_thankyou_cta_label',
    'Accéder à mon compte'
);

$my_account_url = (string) wc_get_page_permalink('myaccount');
?>

<section class="lumiboo-account-card">

    <h2 class="lumiboo-account-card__title"><?php echo esc_html($heading); ?></h2>

    <div class="lumiboo-account-card__body">
        <?php echo wp_kses_post(wpautop($body)); ?>
    </div>

    <?php if ($my_account_url !== '') : ?>
        <p class="lumiboo-account-card__actions">
            <a class="btn" href="<?php echo esc_url($my_account_url); ?>">
                <?php echo esc_html($cta_label); ?>
            </a>
        </p>
    <?php endif; ?>

</section>
