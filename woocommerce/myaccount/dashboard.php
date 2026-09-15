<?php

/**
 * Tableau de bord du compte — « Mon espace Lumiboo ».
 *
 * Surcharge du template WooCommerce natif. Le contenu est délégué au partial
 * du thème pour que la logique reste dans Theme_base\Account.
 *
 * @see woocommerce/templates/myaccount/dashboard.php
 */

defined('ABSPATH') || exit;

get_template_part('partials/account/dashboard');
