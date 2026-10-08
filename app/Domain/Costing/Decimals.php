<?php

declare(strict_types=1);

namespace App\Domain\Costing;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

/**
 * Strict parsing of the decimal strings that enter the costing domain.
 *
 * @internal
 */
final class Decimals
{
    /** Optional minus sign, digits, optional fractional digits. No exponent, fraction, sign "+", padding or lone point. */
    private const PLAIN_DECIMAL = '/\A-?[0-9]+(?:\.[0-9]+)?\z/';

    /**
     * @throws InvalidArgumentException If the value is not a plain decimal number.
     */
    public static function parse(string $value, string $field): BigDecimal
    {
        if (preg_match(self::PLAIN_DECIMAL, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('%s must be a plain decimal number, got "%s".', $field, $value));
        }

        return BigDecimal::of($value);
    }

    /**
     * @throws InvalidArgumentException If the value is not a plain decimal number, or is negative.
     */
    public static function parseNonNegative(string $value, string $field): BigDecimal
    {
        $decimal = self::parse($value, $field);

        if ($decimal->isNegative()) {
            throw new InvalidArgumentException(sprintf('%s must not be negative, got "%s".', $field, $value));
        }

        return $decimal;
    }
}
