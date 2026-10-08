<?php

/** MOCK — UI-first preview data (ADR-0006). Removed by S-17. */

declare(strict_types=1);

namespace App\Mock;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Serialises fixture decimals the way the real Resources will: strings with scale 4. No float is ever involved.
 */
final class PreviewDecimal
{
    /** "3000" -> "3000.0000"; null stays null. */
    public static function scale4(?string $value): ?string
    {
        return $value === null ? null : BigDecimal::of($value)->toScale(4, RoundingMode::HalfUp)->toString();
    }

    /** Same as scale4() for a value that is never null. */
    public static function required(string $value): string
    {
        return BigDecimal::of($value)->toScale(4, RoundingMode::HalfUp)->toString();
    }
}
