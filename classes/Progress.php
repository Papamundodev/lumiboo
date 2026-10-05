<?php

namespace Theme_base;

class Progress
{

    public const MODE_NOT_ANSWERED = 'not_answered';

    public const MODE_WITH = 'with';

    public const MODE_WITHOUT = 'without';

    /**
     * Completeness of one booking dossier.
     *
     * Premium: template, logo, text.
     * Studio: the same three, plus welcome and rear only when the Studio option was bought.
     * MODE_NOT_ANSWERED never counts as done. requests is excluded.
     *
     * @return array{steps: array<string, bool>, complete: bool}
     */
    public static function ofBooking(array $dossier): array
    {
        $steps = [
            'template' => (string) ($dossier['template'] ?? '') !== '',
            'logo'     => self::modeDone(
                (string) ($dossier['logo_mode'] ?? ''),
                (int) ($dossier['logo'] ?? 0) > 0
            ),
            'text'     => self::modeDone(
                (string) ($dossier['text_mode'] ?? ''),
                (string) ($dossier['text'] ?? '') !== ''
            ),
        ];

        if ((string) ($dossier['gamme'] ?? '') === Gamme::STUDIO && ! empty($dossier['has_studio_option'])) {
            $steps['welcome'] = self::modeDone(
                (string) ($dossier['welcome_mode'] ?? ''),
                (int) ($dossier['welcome'] ?? 0) > 0
            );
            $steps['rear'] = self::modeDone(
                (string) ($dossier['rear_mode'] ?? ''),
                (int) ($dossier['rear'] ?? 0) > 0
            );
        }

        $done = count(array_filter($steps));

        return [
            'steps'    => $steps,
            'complete' => $done === count($steps),
        ];
    }

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
