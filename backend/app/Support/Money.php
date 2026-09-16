<?php

namespace App\Support;

/**
 * Backend stores money as integer kuruş. TL conversion happens only at the API/UI boundary.
 */
final class Money
{
    /** 9500 → 95, 9550 → 95.5 */
    public static function toTl(int $cents): int|float
    {
        return $cents % 100 === 0 ? intdiv($cents, 100) : $cents / 100;
    }

    /** "95.5" / 95.5 / junk → 9550 / 9550 / 0. Never negative. */
    public static function fromTl(mixed $tl): int
    {
        if (! is_numeric($tl)) {
            return 0;
        }

        return max(0, (int) round(((float) $tl) * 100));
    }
}
