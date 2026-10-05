<?php

/**
 * Une section de personnalisation = une réservation.
 *
 * Formulaire, nonce et verrou sont propres à ce booking.
 * Welcome / rear uniquement pour Studio avec option.
 *
 * @var array $args {
 *     @type array $dossier   Normalisé par Theme_base\Dossier::fromBooking().
 *     @type array $templates Modèles Lumiboo (clé, label, image).
 * }
 */

use Theme_base\Gamme;
use Theme_base\Impressions;
use Theme_base\Personalisation;
use Theme_base\Progress;

defined('ABSPATH') || exit;

$dossier   = is_array($args['dossier'] ?? null) ? $args['dossier'] : [];
$templates = is_array($args['templates'] ?? null) ? $args['templates'] : [];

$booking_id = (string) ($dossier['id'] ?? '');

if ($booking_id === '' || ! ctype_digit($booking_id)) {
    return;
}

$gamme  = (string) ($dossier['gamme'] ?? '');
$locked = ! empty($dossier['is_locked']);
$studio = $gamme === Gamme::STUDIO && ! empty($dossier['has_studio_option']);

$title = 'LUMIBOO';

if ($gamme !== '') {
    $title .= ' ' . ucfirst($gamme);
}

if ($gamme === Gamme::PREMIUM) {
    $prints    = $dossier['prints'] ?? null;
    $marketing = Impressions::marketingLabel(is_string($prints) && $prints !== '' ? $prints : null);

    if ($marketing !== null) {
        $title .= ' – ' . $marketing;
    }
}

$event = $dossier['event_date'] ?? null;
$event_date = $event instanceof DateTimeImmutable
    ? wp_date((string) get_option('date_format'), $event->getTimestamp())
    : '';

$choice_with    = Personalisation::text('choice_with', 'Oui');
$choice_without = Personalisation::text('choice_without', 'Non');
$choice_later   = Personalisation::text('choice_not_answered', 'Je choisirai plus tard');
$template       = (string) ($dossier['template'] ?? '');

$groups = [
    [
        'name'    => 'logo_mode',
        'label'   => Personalisation::text('field_logo', 'Logo'),
        'mode'    => (string) ($dossier['logo_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'    => 'file',
        'file'    => 'logo',
        'accept'  => 'image/png',
        'file_id' => (int) ($dossier['logo'] ?? 0),
    ],
    [
        'name'  => 'text_mode',
        'label' => Personalisation::text('field_text', 'Texte'),
        'mode'  => (string) ($dossier['text_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'  => 'text',
        'text'  => (string) ($dossier['text'] ?? ''),
    ],
];

if ($studio) {
    $groups[] = [
        'name'    => 'welcome_mode',
        'label'   => Personalisation::text('field_welcome', 'Écran d’accueil'),
        'mode'    => (string) ($dossier['welcome_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'    => 'file',
        'file'    => 'welcome',
        'accept'  => 'image/png',
        'file_id' => (int) ($dossier['welcome'] ?? 0),
    ];
    $groups[] = [
        'name'    => 'rear_mode',
        'label'   => Personalisation::text('field_rear', 'Écran arrière'),
        'mode'    => (string) ($dossier['rear_mode'] ?? Progress::MODE_NOT_ANSWERED),
        'kind'    => 'file',
        'file'    => 'rear',
        'accept'  => 'image/png,video/mp4',
        'file_id' => (int) ($dossier['rear'] ?? 0),
    ];
}

$heading_id = 'lumiboo-booking-title-' . $booking_id;
?>

<section
    class="account-booking<?php echo $locked ? ' is-locked' : ''; ?>"
    id="<?php echo esc_attr('lumiboo-booking-' . $booking_id); ?>"
    aria-labelledby="<?php echo esc_attr($heading_id); ?>">

    <h3 class="account-unit-title" id="<?php echo esc_attr($heading_id); ?>"><?php echo esc_html($title); ?></h3>

    <?php if ($event_date !== '') : ?>
        <p class="account-booking-date"><?php echo esc_html($event_date); ?></p>
    <?php endif; ?>

    <?php if ($locked) : ?>
        <p class="account-perso-intro"><?php echo esc_html(Personalisation::text('locked_intro', 'Modifications fermées à J-7. Consultez votre personnalisation.')); ?></p>
    <?php endif; ?>

    <form
        class="account-perso-form"
        action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
        method="post"
        enctype="multipart/form-data"
        novalidate>

        <input type="hidden" name="action" value="lumiboo_save_personalisation">
        <input type="hidden" name="booking_id" value="<?php echo esc_attr($booking_id); ?>">
        <?php wp_nonce_field('lumiboo_perso_booking_' . $booking_id); ?>

        <fieldset class="account-unit" <?php disabled($locked); ?>>

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
                                name="template"
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
                $field_id = 'account-' . $group['name'] . '-' . $booking_id;
                ?>
                <fieldset class="account-field" data-account-toggle>
                    <legend><?php echo esc_html($group['label']); ?></legend>

                    <label class="account-choice-fallback">
                        <input
                            type="radio"
                            name="<?php echo esc_attr($group['name']); ?>"
                            value="<?php echo esc_attr(Progress::MODE_NOT_ANSWERED); ?>"
                            <?php checked($mode, Progress::MODE_NOT_ANSWERED); ?>>
                        <?php echo esc_html($choice_later); ?>
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="<?php echo esc_attr($group['name']); ?>"
                            value="<?php echo esc_attr(Progress::MODE_WITH); ?>"
                            data-account-toggle-on
                            <?php checked($mode, Progress::MODE_WITH); ?>>
                        <?php echo esc_html($choice_with); ?>
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="<?php echo esc_attr($group['name']); ?>"
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
                                    name="text"
                                    value="<?php echo esc_attr((string) ($group['text'] ?? '')); ?>"
                                    maxlength="<?php echo (int) Personalisation::TEXT_MAX; ?>">
                            </label>
                        <?php else : ?>
                            <?php
                            $file_id = (int) ($group['file_id'] ?? 0);
                            if ($file_id > 0 && str_starts_with((string) get_post_mime_type($file_id), 'video/')) {
                                printf('<video controls src="%s"></video>', esc_url((string) wp_get_attachment_url($file_id)));
                            } elseif ($file_id > 0) {
                                echo wp_get_attachment_image($file_id, 'medium');
                            }
                            if (! $locked) {
                                printf(
                                    '<input type="file" name="%s" accept="%s">',
                                    esc_attr((string) ($group['file'] ?? '')),
                                    esc_attr((string) ($group['accept'] ?? ''))
                                );
                            }
                            ?>
                        <?php endif; ?>

                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div class="account-field">
                <label for="<?php echo esc_attr('account-requests-' . $booking_id); ?>">
                    <?php echo esc_html(Personalisation::text('field_requests', 'Demandes spécifiques')); ?>
                </label>
                <textarea
                    id="<?php echo esc_attr('account-requests-' . $booking_id); ?>"
                    name="requests"
                    rows="4"
                    maxlength="<?php echo (int) Personalisation::REQUESTS_MAX; ?>"><?php echo esc_textarea((string) ($dossier['requests'] ?? '')); ?></textarea>
            </div>

        </fieldset>

        <?php if (! $locked) : ?>
            <div class="button-container button-background-tertiary account-perso-submit">
                <button type="submit" class="btn" name="lumiboo_save" value="1">
                    <?php echo esc_html(Personalisation::text('save', 'Enregistrer')); ?>
                </button>
            </div>
        <?php endif; ?>

    </form>

</section>
