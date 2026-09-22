<?php

/**
 * Bouton d'enregistrement du formulaire de personnalisation.
 * Deux instances dans le même <form> (Q054) — même action save.
 */

use Theme_base\Personalisation;

defined('ABSPATH') || exit;
?>

<div class="button-container button-background-tertiary account-perso-submit">
    <button type="submit" class="btn" name="lumiboo_save" value="1">
        <?php echo esc_html(Personalisation::text('save', 'Enregistrer')); ?>
    </button>
</div>
