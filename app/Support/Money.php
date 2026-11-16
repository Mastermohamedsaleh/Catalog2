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
        // تنظيف الرقم العشري أولاً لمنع أي بقايا لـ floating-point inaccuracies (مثل .99999 أو .00001)
        $cleanAmount = number_format((float) $amount, 2, '.', '');
        return (int) round(((float) $cleanAmount) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /** (selling - cost) * qty, as a 2-decimal string. */
    public static function profit(string|int|float $selling, string|int|float $cost, int $qty): string
    {
        $sellingCents = self::toCents($selling);
        $costCents = self::toCents($cost);

        return self::fromCents(($sellingCents - $costCents) * $qty);
    }
}