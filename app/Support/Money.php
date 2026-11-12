<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toMinorUnits(string $amount): int
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Invalid monetary amount.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    public static function format(int $amount): string
    {
        $whole = intdiv($amount, 100);
        $fraction = $amount % 100;

        return $whole.'.'.str_pad(
            (string) $fraction,
            2,
            '0',
            STR_PAD_LEFT
        );
    }
}
