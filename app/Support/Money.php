<?php

namespace App\Support;

/**
 * Integer-cent helpers so profit is never computed with binary floats.
 * (bcmath is not guaranteed to be installed.)
 */
final class Money
{
    public static function toCents(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /** (selling - cost) * qty, as a 2-decimal string. */
    public static function profit(string|int|float $selling, string|int|float $cost, int $qty): string
    {
        return self::fromCents((self::toCents($selling) - self::toCents($cost)) * $qty);
    }
}
