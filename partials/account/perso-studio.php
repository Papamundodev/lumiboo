<?php

/**
 * Écran d'accueil et plaque arrière, une fois par commande Studio.
 * Hors du repeater `lumiboo_units`. Fichiers : placeholder chunk 8.
 *
 * @var array $args {
 *     @type array $dossier
 *     @type bool  $locked
 * }
 */

use Theme_base\Personalisation;
use Theme_base\Progress;

defined('ABSPATH') || exit;

$dossier = is_array($args['dossier'] ?? null) ? $args['dossier'] : [];
$locked  = ! empty($args['locked']);

$choice_with    = Personalisation::text('choice_with', 'Oui');
$choice_without = Personalisation::text('choice_without', 'Non');
$choice_later   = Personalisation::text('choice_not_answered', 'Je choisirai plus tard');
$file_pending   = Personalisation::text('file_pending', 'Téléversement disponible bientôt');

$groups = [
    [
        'name'    => 'welcome_mode',
        'label'   => Personalisation::text('field_welcome', 'Écran d’accueil'),
        'mode'    => (string) ($dossier['welcome_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'file_id' => (int) ($dossier['welcome'] ?? 0),
    ],
    [
        'name'    => 'rear_mode',
        'label'   => Personalisation::text('field_rear', 'Écran arrière'),
        'mode'    => (string) ($dossier['rear_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'file_id' => (int) ($dossier['rear'] ?? 0),
    ],
];
?>

<fieldset class="account-studio" <?php disabled($locked, true, true); ?>>
    <legend class="account-unit-title"><?php echo esc_html(Personalisation::text('studio_title', 'Option Studio')); ?></legend>

    <?php foreach ($groups as $group) : ?>
        <?php
        $mode = $group['mode'];
        $open = $mode === Progress::MODE_WITH;
        ?>
        <fieldset class="account-field" data-account-toggle>
            <legend><?php echo esc_html($group['label']); ?></legend>

            <label class="account-choice-fallback">
                <input
                    type="radio"
                    name="<?php echo esc_attr('studio[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_NOT_ANSWERED); ?>"
                    <?php checked($mode, Progress::MODE_NOT_ANSWERED); ?>>
                <?php echo esc_html($choice_later); ?>
            </label>

            <label>
                <input
                    type="radio"
                    name="<?php echo esc_attr('studio[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_WITH); ?>"
                    data-account-toggle-on
                    <?php checked($mode, Progress::MODE_WITH); ?>>
                <?php echo esc_html($choice_with); ?>
            </label>

            <label>
                <input
                    type="radio"
                    name="<?php echo esc_attr('studio[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_WITHOUT); ?>"
                    <?php checked($mode, Progress::MODE_WITHOUT); ?>>
                <?php echo esc_html($choice_without); ?>
            </label>

            <div
                class="account-field-target"
                data-account-toggle-target
                <?php echo $open ? '' : 'hidden'; ?>>
                <?php
                $file_id = (int) ($group['file_id'] ?? 0);
                if ($file_id > 0) {
                    echo wp_get_attachment_image($file_id, 'medium');
                }
                ?>
                <p class="account-file-pending"><?php echo esc_html($file_pending); ?></p>
            </div>
        </fieldset>
    <?php endforeach; ?>
</fieldset>
