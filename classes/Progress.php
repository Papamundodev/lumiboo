<?php

namespace Theme_base;

/**
 * Moteur de complétude de la personnalisation d'un dossier.
 *
 * La progression est CALCULÉE à chaque appel à partir des valeurs ACF
 * du repeater `lumiboo_units`. Aucun pourcentage n'est stocké.
 *
 * Un dossier Lumiboo contient N unités (une par Lumiboo commandé).
 * Chaque unité a sa propre progression et le dossier n'est complet que
 * si TOUTES les unités le sont.
 *
 * Étapes par unité : `template`, `logo`, `text`.
 * `welcome` et `rear` sont une fois par dossier, seulement si l'option
 * Studio est achetée. Ils vivent hors du repeater.
 *
 * Un choix « avec/sans » n'est validé que par `MODE_WITHOUT` ou par
 * `MODE_WITH` accompagné d'une valeur ; `MODE_NOT_ANSWERED` (défaut) ne
 * vaut jamais un choix.
 *
 * Le champ « Demandes spécifiques » n'entre pas dans le calcul.
 */
class Progress
{
    /** Valeur radio ACF : le client n'a pas encore répondu (défaut). */
    public const MODE_NOT_ANSWERED = 'not_answered';

    /** Valeur radio ACF : le client a fourni un fichier/texte. */
    public const MODE_WITH = 'with';

    /** Valeur radio ACF : le client a choisi explicitement « sans ». */
    public const MODE_WITHOUT = 'without';

    /**
     * Progression agrégée du dossier, avec le détail par unité.
     *
     * @param array<string, mixed> $dossier
     * @return array{
     *     units: array<int, array{steps: array<int, array{key: string, done: bool}>, done: int, total: int, complete: bool}>,
     *     studio: array<int, array{key: string, done: bool}>,
     *     done: int,
     *     total: int,
     *     complete: bool
     * }
     */
    public static function of(array $dossier): array
    {
        $units = (array) ($dossier['units'] ?? []);

        $per_unit = [];
        $done     = 0;
        $total    = 0;

        foreach ($units as $unit) {
            $up          = self::ofUnit((array) $unit);
            $per_unit[]  = $up;
            $done       += $up['done'];
            $total      += $up['total'];
        }

        $studio = [];

        if (! empty($dossier['has_studio_option'])) {
            $studio = [
                [
                    'key'  => 'welcome',
                    'done' => self::modeDone(
                        (string) ($dossier['welcome_mode'] ?? ''),
                        (int) ($dossier['welcome'] ?? 0) > 0
                    ),
                ],
                [
                    'key'  => 'rear',
                    'done' => self::modeDone(
                        (string) ($dossier['rear_mode'] ?? ''),
                        (int) ($dossier['rear'] ?? 0) > 0
                    ),
                ],
            ];

            foreach ($studio as $step) {
                $total++;
                if ($step['done']) {
                    $done++;
                }
            }
        }

        return [
            'units'    => $per_unit,
            'studio'   => $studio,
            'done'     => $done,
            'total'    => $total,
            'complete' => $total > 0 && $done === $total,
        ];
    }

    /**
     * Progression d'une unité isolée.
     *
     * @param array<string, mixed> $unit
     * @return array{steps: array<int, array{key: string, done: bool}>, done: int, total: int, complete: bool}
     */
    public static function ofUnit(array $unit): array
    {
        $steps = [
            [
                'key'  => 'template',
                'done' => (string) ($unit['template'] ?? '') !== '',
            ],
            [
                'key'  => 'logo',
                'done' => self::modeDone(
                    (string) ($unit['logo_mode'] ?? ''),
                    (int) ($unit['logo'] ?? 0) > 0
                ),
            ],
            [
                'key'  => 'text',
                'done' => self::modeDone(
                    (string) ($unit['text_mode'] ?? ''),
                    (string) ($unit['text'] ?? '') !== ''
                ),
            ],
        ];

        $done  = count(array_filter($steps, static fn (array $s): bool => $s['done']));
        $total = count($steps);

        return [
            'steps'    => $steps,
            'done'     => $done,
            'total'    => $total,
            'complete' => $total > 0 && $done === $total,
        ];
    }

    /**
     * @param array<string, mixed> $dossier
     */
    public static function isComplete(array $dossier): bool
    {
        return self::of($dossier)['complete'];
    }

    /**
     * Une étape à choix « avec/sans » est validée soit par un choix
     * explicite « sans », soit par « avec » accompagné d'une valeur.
     */
    private static function modeDone(string $mode, bool $has_value): bool
    {
        if ($mode === self::MODE_WITHOUT) {
            return true;
        }

        if ($mode === self::MODE_WITH && $has_value) {
            return true;
        }

        return false;
    }
}
