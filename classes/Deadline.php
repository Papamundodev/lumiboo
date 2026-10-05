<?php

namespace Theme_base;

class Deadline
{

    private const DAYS_BEFORE_EVENT = 7;

    public static function of(array $dossier): ?\DateTimeImmutable
    {
        $event = $dossier['event_date'] ?? null;

        if (! $event instanceof \DateTimeImmutable) {
            return null;
        }

        return $event
            ->setTimezone(wp_timezone())
            ->modify('-' . self::DAYS_BEFORE_EVENT . ' days')
            ->setTime(0, 0);
    }

    public static function isLocked(array $dossier): bool
    {
        $deadline = self::of($dossier);

        if ($deadline === null) {
            return false;
        }

        return self::now() >= $deadline;
    }

    public static function isPast(array $dossier): bool
    {
        $return = $dossier['return_date'] ?? null;

        if (! $return instanceof \DateTimeImmutable) {
            return false;
        }

        return self::now()->format('Y-m-d') > $return->format('Y-m-d');
    }

    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', wp_timezone());
    }
}
