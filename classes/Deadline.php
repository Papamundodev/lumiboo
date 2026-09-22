<?php

namespace Theme_base;

/**
 * Moteur de deadline et de partitionnement temporel des dossiers.
 *
 * Règle de verrou (§11.8, décision explicite du 19/09) :
 *
 * > Événement le 20 octobre → modification client possible jusqu'au
 * > 13 octobre 23:59 ; verrouillage à 14 octobre 00:00.
 *
 * Autrement dit : `deadline = event_date − 7 jours` à 00:00, dans le
 * fuseau WordPress (`wp_timezone()`), sans constante hardcodée.
 *
 * Une commande créée après la deadline est verrouillée d'entrée de jeu
 * pour le client. L'admin reste maître dans le back-office (arbitrage
 * B06).
 *
 * Une commande sans date d'événement (aucune réservation YITH rattachée)
 * n'a pas de deadline : `isLocked()` renvoie `false` et `isPast()` aussi.
 */
class Deadline
{
    /** Jours de fermeture avant la date d'événement. */
    private const DAYS_BEFORE_EVENT = 7;

    /**
     * Moment exact où la modification client se ferme.
     *
     * @param array<string, mixed> $dossier
     */
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

    /**
     * Le dossier est-il verrouillé côté client ?
     *
     * @param array<string, mixed> $dossier
     */
    public static function isLocked(array $dossier): bool
    {
        $deadline = self::of($dossier);

        if ($deadline === null) {
            return false;
        }

        return self::now() >= $deadline;
    }

    /**
     * L'événement est-il terminé (dossier à ranger dans « passées ») ?
     *
     * H01 : la commande reste « en cours » jusqu'à sa date de retour
     * INCLUSE, puis bascule en « passée » le lendemain. On compare donc
     * les dates seules, pas les timestamps.
     *
     * @param array<string, mixed> $dossier
     */
    public static function isPast(array $dossier): bool
    {
        $return = $dossier['return_date'] ?? null;

        if (! $return instanceof \DateTimeImmutable) {
            return false;
        }

        return self::now()->format('Y-m-d') > $return->format('Y-m-d');
    }

    /**
     * Un seul endroit qui « connaît » l'heure : facilite les tests
     * futurs si l'on souhaite injecter une horloge simulée.
     */
    private static function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', wp_timezone());
    }
}
