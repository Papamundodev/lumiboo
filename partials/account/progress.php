<?php

/**
 * Barre de progression agrégée d'un dossier (chunk 6).
 *
 * Une barre par carte : toutes les unités concaténées, plus un dernier
 * segment « Personnalisation complétée » dérivé de `Progress::of()['complete']`.
 * Les libellés viennent de `Personalisation::progressLabels()`, pas du moteur.
 *
 * @var array $args {
 *     @type array $dossier Normalisé par Theme_base\Dossier.
 * }
 */

use Theme_base\Personalisation;
use Theme_base\Progress;

defined('ABSPATH') || exit;

$dossier = $args['dossier'] ?? null;

if (! is_array($dossier)) {
    return;
}

$progress = Progress::of($dossier);
$labels   = Personalisation::progressLabels();
$steps    = [];

foreach ((array) ($progress['units'] ?? []) as $unit) {
    foreach ((array) ($unit['steps'] ?? []) as $step) {
        $key = (string) ($step['key'] ?? '');
        if ($key === '') {
            continue;
        }
        $steps[] = [
            'key'   => $key,
            'done'  => ! empty($step['done']),
            'label' => $labels[$key] ?? $key,
        ];
    }
}

foreach ((array) ($progress['studio'] ?? []) as $step) {
    $key = (string) ($step['key'] ?? '');
    if ($key === '') {
        continue;
    }
    $steps[] = [
        'key'   => $key,
        'done'  => ! empty($step['done']),
        'label' => $labels[$key] ?? $key,
    ];
}

$steps[] = [
    'key'   => 'complete',
    'done'  => ! empty($progress['complete']),
    'label' => $labels['complete'] ?? 'Personnalisation complétée',
];
?>

<div
    class="account-progress"
    role="group"
    aria-label="<?php echo esc_attr($labels['complete'] ?? ''); ?>">
    <ol class="account-progress-steps">
        <?php foreach ($steps as $step) : ?>
            <li class="account-progress-step<?php echo $step['done'] ? ' is-done' : ''; ?>">
                <span class="account-progress-bar" aria-hidden="true"></span>
                <span class="account-progress-label"><?php echo esc_html($step['label']); ?></span>
                <span class="screen-reader-text">
                    <?php echo $step['done'] ? esc_html('Complété') : esc_html('À compléter'); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
