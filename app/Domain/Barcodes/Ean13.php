<?php

declare(strict_types=1);

namespace App\Domain\Barcodes;

use InvalidArgumentException;

/**
 * EAN-13 numbers (INV-8, ADR-0020). Pure PHP: the check digit and the workshop's in-store numbers, "2" + the serial on 11 digits + the
 * check digit (serial 1 is 2000000000015). Numbers starting with 2 are for use inside the shop only.
 */
final class Ean13
{
    /** The largest serial that fits the 11 digits between the "2" and the check digit. */
    public const MAX_SERIAL = 99_999_999_999;

    /**
     * The check digit of the first 12 digits: weights 1, 3, 1, 3 … from the left, then what makes the sum a multiple of 10.
     *
     * @throws InvalidArgumentException If $digits is not exactly 12 digits.
     */
    public static function checkDigit(string $digits): int
    {
        if (preg_match('/^\d{12}$/', $digits) !== 1) {
            throw new InvalidArgumentException('An EAN-13 check digit is worked out from exactly 12 digits.');
        }

        $sum = 0;

        foreach (str_split($digits) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }

    /**
     * The in-store number of a serial: 1 → "2000000000015", 2 → "2000000000022".
     *
     * @throws InvalidArgumentException If $serial is below 1 or above 99,999,999,999.
     */
    public static function inStore(int $serial): string
    {
        if ($serial < 1 || $serial > self::MAX_SERIAL) {
            throw new InvalidArgumentException('An in-store serial runs from 1 to '.number_format(self::MAX_SERIAL).'.');
        }

        $digits = '2'.str_pad((string) $serial, 11, '0', STR_PAD_LEFT);

        return $digits.self::checkDigit($digits);
    }
}
