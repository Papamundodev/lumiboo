<?php

/**
 * Page de personnalisation d'une commande.
 *
 * Les deux boutons « Enregistrer » appartiennent au même formulaire : chacun
 * soumet donc la totalité des champs de la page (réponse Q054). Tout ce qui
 * est valide est conservé à chaque envoi ; le dossier ne devient « complété »
 * que lorsque tous les choix requis sont faits (réponse Q051). Après J-7,
 * toute écriture est refusée (réponse Q055).
 *
 * @var array $args { @type array $dossier }
 */

use Theme_base\Account;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;

if (! is_array($dossier)) {
    return;
}

$perso        = $dossier['perso'];
$locked       = (bool) $dossier['locked'];
$disabled     = $locked ? ' disabled' : '';
$templates    = Account::templates();
$illustration = Account::image('perso_illustration');
$sav_url      = Account::text('sav_url');
$date_format  = get_option('date_format');
$event_date   = wp_date($date_format, strtotime($dossier['event_date']));
$text_max     = (int) apply_filters('lumiboo_account_text_max_length', 100);
?>

<div class="account account-perso<?= $locked ? ' is-locked' : ''; ?>">

    <?php get_template_part('partials/account/notice'); ?>

    <header class="account-perso-header">
        <h2 class="account-title"><?= esc_html(Account::text('perso_title', __('Personnalise ton Lumiboo !', 'theme_base'))); ?></h2>

        <p class="account-perso-reference">
            <?= esc_html(sprintf(
                /* translators: 1: order reference, 2: product, 3: event date */
                __('Commande %1$s — %2$s — %3$s', 'theme_base'),
                $dossier['reference'],
                $dossier['title'],
                $event_date
            )); ?>
        </p>

        <a class="account-perso-back" href="<?= esc_url(wc_get_account_endpoint_url('dashboard')); ?>">
            <?= esc_html(Account::text('perso_back', __('Retour à mon espace', 'theme_base'))); ?>
        </a>
    </header>

    <?php if ($locked) : ?>
        <p class="account-locked-notice">
            <?= esc_html(Account::text('msg_locked', __('Les modifications en ligne sont fermées. Contactez l’équipe Lumiboo sur WhatsApp pour une correction.', 'theme_base'))); ?>
            <?php if ($sav_url !== '') : ?>
                <a href="<?= esc_url($sav_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?= esc_html(Account::text('sav_label', __('Une question ? Écrivez-nous sur WhatsApp', 'theme_base'))); ?>
                </a>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <form
        class="account-perso-form"
        method="post"
        enctype="multipart/form-data"
        action="<?= esc_url(admin_url('admin-post.php')); ?>">

        <input type="hidden" name="action" value="lumiboo_save_personalisation">
        <input type="hidden" name="dossier" value="<?= esc_attr($dossier['id']); ?>">
        <?php wp_nonce_field('lumiboo_perso_' . $dossier['id']); ?>

        <div class="account-perso-main">

            <div class="account-perso-fields">

                <fieldset class="account-field">
                    <legend class="account-field-title"><?= esc_html(Account::text('perso_templates_title', __('Choisi ton template', 'theme_base'))); ?></legend>

                    <div class="account-templates">
                        <?php foreach ($templates as $template) : ?>
                            <label class="account-template">
                                <input
                                    type="radio"
                                    name="template"
                                    value="<?= esc_attr($template['key']); ?>"
                                    <?php checked($perso['template'], $template['key']); ?>
                                    <?= $disabled; ?>>
                                <span class="account-template-visual">
                                    <?php if ($template['image']) : ?>
                                        <?= wp_get_attachment_image($template['image'], 'medium'); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="account-template-label"><?= esc_html($template['label']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="account-field" data-account-toggle="logo">
                    <legend class="account-field-title"><?= esc_html(Account::text('perso_logo_title', __('Insère ton logo', 'theme_base'))); ?></legend>
                    <p class="account-field-hint"><?= esc_html(Account::text('perso_logo_hint', __('Dimensions et formats acceptés', 'theme_base'))); ?></p>

                    <label class="account-choice">
                        <input type="radio" name="logo_mode" value="with" <?php checked($perso['logo_mode'], 'with'); ?> <?= $disabled; ?> data-account-toggle-on>
                        <span><?= esc_html(Account::text('perso_upload', __('Télécharger', 'theme_base'))); ?></span>
                    </label>

                    <div class="account-upload" data-account-toggle-target>
                        <input type="file" name="logo" accept="image/png,image/jpeg" <?= $disabled; ?>>

                        <?php if ($perso['logo_file'] !== []) : ?>
                            <p class="account-upload-current">
                                <img src="<?= esc_url(Account::fileUrl($dossier, 'logo')); ?>"
                                    alt="<?= esc_attr(Account::fileLabel($dossier, 'logo')); ?>"
                                    loading="lazy">
                                <span><?= esc_html(Account::fileLabel($dossier, 'logo')); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <label class="account-choice">
                        <input type="radio" name="logo_mode" value="without" <?php checked($perso['logo_mode'], 'without'); ?> <?= $disabled; ?> data-account-toggle-off>
                        <span><?= esc_html(Account::text('perso_logo_none', __('Sans logo', 'theme_base'))); ?></span>
                    </label>
                </fieldset>

                <fieldset class="account-field" data-account-toggle="text">
                    <legend class="account-field-title"><?= esc_html(Account::text('perso_text_title', __('Ajoute ton texte', 'theme_base'))); ?></legend>

                    <label class="account-choice">
                        <input type="radio" name="text_mode" value="with" <?php checked($perso['text_mode'], 'with'); ?> <?= $disabled; ?> data-account-toggle-on>
                        <span><?= esc_html(Account::text('perso_text_title', __('Ajoute ton texte', 'theme_base'))); ?></span>
                    </label>

                    <div class="account-upload" data-account-toggle-target>
                        <input
                            type="text"
                            name="perso_text"
                            maxlength="<?= esc_attr((string) $text_max); ?>"
                            value="<?= esc_attr($perso['text']); ?>"
                            placeholder="<?= esc_attr(Account::text('perso_text_placeholder', __('Texte personnalisé', 'theme_base'))); ?>"
                            <?= $disabled; ?>>
                    </div>

                    <label class="account-choice">
                        <input type="radio" name="text_mode" value="without" <?php checked($perso['text_mode'], 'without'); ?> <?= $disabled; ?> data-account-toggle-off>
                        <span><?= esc_html(Account::text('perso_text_none', __('Sans texte', 'theme_base'))); ?></span>
                    </label>
                </fieldset>

                <?php if (! $locked) : ?>
                    <div class="button-container button-background-tertiary">
                        <button type="submit" class="btn"><?= esc_html(Account::text('perso_save', __('Enregistrer', 'theme_base'))); ?></button>
                    </div>
                <?php endif; ?>

            </div>

            <?php if ($illustration) : ?>
                <figure class="account-perso-illustration">
                    <img
                        src="<?= esc_url($illustration['url']); ?>"
                        alt="<?= esc_attr($illustration['alt'] ?? ''); ?>"
                        loading="lazy">
                </figure>
            <?php else : ?>
                <div class="account-perso-illustration is-placeholder" aria-hidden="true"></div>
            <?php endif; ?>

        </div>

        <?php
        /**
         * Bloc réservé à une commande Studio ayant acheté l'option. Le droit
         * dépend de la commande, jamais du nom d'entreprise (décision Q052),
         * et il est revérifié à l'enregistrement.
         *
         * Blocage B02 : « écran arrière » et « plaque arrière » sont déclarés
         * distincts par Lumiboo. Seuls les deux champs du wireframe sont
         * rendus ici.
         */
        ?>
        <?php if (! empty($dossier['has_studio_option'])) : ?>
            <section class="account-perso-studio">
                <h3 class="account-field-title"><?= esc_html(Account::text('perso_studio_title', __('Option Studio', 'theme_base'))); ?></h3>

                <?php
                $studio_fields = [
                    'welcome' => [
                        'title' => Account::text('perso_welcome_title', __('Personnalise l’écran d’accueil', 'theme_base')),
                        'url'   => Account::fileUrl($dossier, 'welcome'),
                        'label' => Account::fileLabel($dossier, 'welcome'),
                    ],
                    'rear'    => [
                        'title' => Account::text('perso_rear_title', __('Personnalise ton écran arrière', 'theme_base')),
                        'url'   => Account::fileUrl($dossier, 'rear'),
                        'label' => Account::fileLabel($dossier, 'rear'),
                    ],
                ];
                ?>

                <?php foreach ($studio_fields as $name => $field) : ?>
                    <div class="account-perso-studio-row">
                        <div class="account-perso-studio-preview">
                            <?php if ($field['url'] !== '') : ?>
                                <img src="<?= esc_url($field['url']); ?>"
                                    alt="<?= esc_attr($field['label']); ?>"
                                    loading="lazy">
                            <?php endif; ?>
                        </div>

                        <fieldset class="account-field">
                            <legend class="account-field-title"><?= esc_html($field['title']); ?></legend>
                            <p class="account-field-hint"><?= esc_html(Account::text('perso_logo_hint', __('Dimensions et formats acceptés', 'theme_base'))); ?></p>
                            <input type="file" name="<?= esc_attr($name); ?>" accept="image/png,image/jpeg" <?= $disabled; ?>>

                            <?php if ($field['label'] !== '') : ?>
                                <p class="account-upload-current"><span><?= esc_html($field['label']); ?></span></p>
                            <?php endif; ?>
                        </fieldset>
                    </div>
                <?php endforeach; ?>

                <?php if (! $locked) : ?>
                    <div class="button-container button-background-tertiary">
                        <button type="submit" class="btn"><?= esc_html(Account::text('perso_save', __('Enregistrer', 'theme_base'))); ?></button>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <fieldset class="account-field account-perso-requests">
            <legend class="account-field-title"><?= esc_html(Account::text('perso_requests_title', __('Demandes spécifiques', 'theme_base'))); ?></legend>
            <p class="account-field-hint"><?= esc_html(Account::text('perso_requests_hint', __('Facultatif. Ce champ n’entre pas dans le calcul de complétude.', 'theme_base'))); ?></p>
            <textarea name="requests" rows="4" <?= $disabled; ?>><?= esc_textarea($perso['requests']); ?></textarea>
        </fieldset>

    </form>
</div>
