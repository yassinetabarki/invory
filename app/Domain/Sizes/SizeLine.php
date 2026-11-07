<?php

declare(strict_types=1);

namespace App\Domain\Sizes;

use Closure;

/**
 * The one rule behind the sizes of a product in words (INV-18, ADR-0028; costing skill "Sizes"): the badge on the product page
 * ("Sizes S, M, L, XL") and the Suggests line of a product category ("S, M, L, XL"). The server writes both, so no screen works out a range.
 * Pure PHP over plain labels.
 *
 * - The workshop's One size reads as its own name ("One size", "Taille unique" when renamed); a single other size as "Size M".
 * - Two to six sizes are listed in full; seven or more in an unbroken run of the workshop's list read "XS to XXXL", and with gaps
 *   "XS, S, M and 4 more".
 */
final class SizeLine
{
    /**
     * The badge: "One size", "Size M", "Sizes S, M, L, XL", "Sizes XS to XXXL", "Sizes XS, S, M and 4 more".
     *
     * @param  list<string>  $labels  the product's sizes, in list order
     * @param  list<string>|Closure(): list<string>  $workshopLabels  the workshop's sizes, in list order (asked only for seven sizes or more)
     * @param  bool  $oneSize  the product's only size is the workshop's flagged One size
     */
    public static function badge(array $labels, array|Closure $workshopLabels, bool $oneSize): string
    {
        $line = self::suggests($labels, $workshopLabels, $oneSize);

        return match (true) {
            $oneSize || $labels === [] => $line,
            count($labels) === 1 => 'Size '.$line,
            default => 'Sizes '.$line,
        };
    }

    /**
     * The same without "Size" / "Sizes", for the Suggests line of a category: "One size", "M", "S, M, L, XL", "XS to XXXL".
     *
     * @param  list<string>  $labels  the sizes, in list order
     * @param  list<string>|Closure(): list<string>  $workshopLabels  the workshop's sizes, in list order (asked only for seven sizes or more)
     * @param  bool  $oneSize  the only size is the workshop's flagged One size
     */
    public static function suggests(array $labels, array|Closure $workshopLabels, bool $oneSize): string
    {
        $count = count($labels);

        if ($count < 7) {
            return implode(', ', $labels);
        }

        if (! $oneSize && self::isRun($labels, $workshopLabels instanceof Closure ? $workshopLabels() : $workshopLabels)) {
            return $labels[0].' to '.$labels[$count - 1];
        }

        return implode(', ', array_slice($labels, 0, 3)).' and '.($count - 3).' more';
    }

    /**
     * @param  list<string>  $labels
     * @param  list<string>  $workshopLabels
     */
    private static function isRun(array $labels, array $workshopLabels): bool
    {
        $start = array_search($labels[0], $workshopLabels, true);

        return $start !== false && array_slice($workshopLabels, $start, count($labels)) === $labels;
    }
}
