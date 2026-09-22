<?php

/**
 * Une unité de personnalisation (un Lumiboo).
 *
 * Q048 : empty ≠ Sans — `not_answered` est le défaut, radio masqué.
 * Fichiers : aperçu lecture seule + placeholder chunk 8.
 *
 * @var array $args {
 *     @type int   $index
 *     @type array $unit
 *     @type bool  $locked
 *     @type array $templates
 * }
 */

use Theme_base\Personalisation;
use Theme_base\Progress;

defined('ABSPATH') || exit;

$index     = (int) ($args['index'] ?? 0);
$unit      = is_array($args['unit'] ?? null) ? $args['unit'] : [];
$locked    = ! empty($args['locked']);
$templates = is_array($args['templates'] ?? null) ? $args['templates'] : [];
$prefix    = 'units[' . $index . ']';
$template  = (string) ($unit['template'] ?? '');

$choice_with    = Personalisation::text('choice_with', 'Oui');
$choice_without = Personalisation::text('choice_without', 'Non');
$choice_later   = Personalisation::text('choice_not_answered', 'Je choisirai plus tard');
$file_pending   = Personalisation::text('file_pending', 'Téléversement disponible bientôt');

$groups = [
    [
        'name'    => 'logo_mode',
        'label'   => Personalisation::text('field_logo', 'Logo'),
        'mode'    => (string) ($unit['logo_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'    => 'file',
        'file_id' => (int) ($unit['logo'] ?? 0),
    ],
    [
        'name'  => 'text_mode',
        'label' => Personalisation::text('field_text', 'Texte'),
        'mode'  => (string) ($unit['text_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'  => 'text',
        'text'  => (string) ($unit['text'] ?? ''),
    ],
];
?>

<fieldset class="account-unit" <?php disabled($locked, true, true); ?>>
    <legend class="account-unit-title"><?php echo esc_html(Personalisation::unitTitle($index)); ?></legend>

    <fieldset class="account-field">
        <legend><?php echo esc_html(Personalisation::text('field_template', 'Modèle')); ?></legend>
        <div class="account-templates">
            <?php foreach ($templates as $item) : ?>
                <?php
                $key   = (string) ($item['key'] ?? '');
                $label = (string) ($item['label'] ?? $key);
                $image = (int) ($item['image'] ?? 0);

                if ($key === '') {
                    continue;
                }
                ?>
                <label class="account-template">
                    <input
                        type="radio"
                        name="<?php echo esc_attr($prefix . '[template]'); ?>"
                        value="<?php echo esc_attr($key); ?>"
                        <?php checked($template, $key); ?>>
                    <?php if ($image > 0) : ?>
                        <?php echo wp_get_attachment_image($image, 'medium', false, ['alt' => $label]); ?>
                    <?php endif; ?>
                    <span><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?php foreach ($groups as $group) : ?>
        <?php
        $mode     = $group['mode'];
        $open     = $mode === Progress::MODE_WITH;
        $field_id = 'account-' . $group['name'] . '-' . $index;
        ?>
        <fieldset class="account-field" data-account-toggle>
            <legend><?php echo esc_html($group['label']); ?></legend>

            <label class="account-choice-fallback">
                <input
                    type="radio"
                    name="<?php echo esc_attr($prefix . '[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_NOT_ANSWERED); ?>"
                    <?php checked($mode, Progress::MODE_NOT_ANSWERED); ?>>
                <?php echo esc_html($choice_later); ?>
            </label>

            <label>
                <input
                    type="radio"
                    name="<?php echo esc_attr($prefix . '[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_WITH); ?>"
                    data-account-toggle-on
                    <?php checked($mode, Progress::MODE_WITH); ?>>
                <?php echo esc_html($choice_with); ?>
            </label>

            <label>
                <input
                    type="radio"
                    name="<?php echo esc_attr($prefix . '[' . $group['name'] . ']'); ?>"
                    value="<?php echo esc_attr(Progress::MODE_WITHOUT); ?>"
                    <?php checked($mode, Progress::MODE_WITHOUT); ?>>
                <?php echo esc_html($choice_without); ?>
            </label>

            <div
                class="account-field-target"
                data-account-toggle-target
                <?php echo $open ? '' : 'hidden'; ?>>

                <?php if ($group['kind'] === 'text') : ?>
                    <label class="account-text" for="<?php echo esc_attr($field_id); ?>">
                        <span class="screen-reader-text"><?php echo esc_html($group['label']); ?></span>
                        <input
                            id="<?php echo esc_attr($field_id); ?>"
                            type="text"
                            name="<?php echo esc_attr($prefix . '[text]'); ?>"
                            value="<?php echo esc_attr((string) ($group['text'] ?? '')); ?>"
                            maxlength="<?php echo (int) Personalisation::TEXT_MAX; ?>">
                    </label>
                <?php else : ?>
                    <?php
                    $file_id = (int) ($group['file_id'] ?? 0);
                    if ($file_id > 0) {
                        echo wp_get_attachment_image($file_id, 'medium');
                    }
                    ?>
                    <p class="account-file-pending"><?php echo esc_html($file_pending); ?></p>
                <?php endif; ?>

            </div>
        </fieldset>
    <?php endforeach; ?>

    <div class="account-field">
        <label for="<?php echo esc_attr('account-requests-' . $index); ?>">
            <?php echo esc_html(Personalisation::text('field_requests', 'Demandes spécifiques')); ?>
        </label>
        <textarea
            id="<?php echo esc_attr('account-requests-' . $index); ?>"
            name="<?php echo esc_attr($prefix . '[requests]'); ?>"
            rows="4"
            maxlength="<?php echo (int) Personalisation::REQUESTS_MAX; ?>"><?php echo esc_textarea((string) ($unit['requests'] ?? '')); ?></textarea>
    </div>

</fieldset>
