<?php

declare(strict_types=1);

namespace App\Domain\Barcodes;

/**
 * The one rule behind every sentence that lists variants or SKUs (INV-8, ADR-0020; costing skill "Variant lists" and "SKU lists"): the
 * toasts, the rename refusal and the Generate window's text are all written here, on the server, so no screen rebuilds them.
 * Pure PHP over plain arrays.
 *
 * - Variants are grouped by colour in colour order, each colour followed by its sizes in size order, the colours joined with ", " and
 *   a last ", and" ("Black M and L, and Light wash S, M, L and XL"). A size made of digits only reads "Light wash sizes 36, 37 and 38"
 *   ("Light wash size 36"). A "One size" product names its colours ("Camel and Rust").
 * - With a colour limit, the colours beyond it become ", and 2 more colours" (", and 1 more colour").
 * - SKUs: one to three in full, four or more the first two and "and N more".
 */
final class VariantNames
{
    /**
     * @param  list<array{colour: string, sizes: list<string>}>  $colours  in colour order, each with its sizes in size order
     * @param  bool  $oneSize  a "One size" product: only the colours are named
     * @param  int|null  $colourLimit  name at most this many colours, then "and N more colours"
     */
    public static function list(array $colours, bool $oneSize = false, ?int $colourLimit = null): string
    {
        $more = 0;

        if ($colourLimit !== null && count($colours) > $colourLimit) {
            $more = count($colours) - $colourLimit;
            $colours = array_slice($colours, 0, $colourLimit);
        }

        $groups = array_map(
            fn (array $colour): string => $oneSize ? $colour['colour'] : self::group($colour['colour'], $colour['sizes']),
            $colours,
        );

        if ($more > 0) {
            return implode(', ', $groups).', and '.$more.($more === 1 ? ' more colour' : ' more colours');
        }

        if ($oneSize) {
            return self::join($groups);
        }

        // Sized groups hold "and" themselves ("M and L"), so the last colour gets the Oxford ", and" to keep them apart.
        $last = (string) array_pop($groups);

        return $groups === [] ? $last : implode(', ', $groups).', and '.$last;
    }

    /**
     * One variant: "Light wash S", "Light wash size 36", "Camel".
     *
     * @param  string|null  $sizeLabel  null for a "One size" product
     */
    public static function one(string $colour, ?string $sizeLabel): string
    {
        return match (true) {
            $sizeLabel === null => $colour,
            ctype_digit($sizeLabel) => "{$colour} size {$sizeLabel}",
            default => "{$colour} {$sizeLabel}",
        };
    }

    /**
     * SKUs of products: "PRD-JKT-002", "PRD-JKT-002 and PRD-JKT-007", "PRD-JKT-002, PRD-JKT-007 and PRD-JKT-009", and from four
     * "PRD-JKT-002, PRD-JKT-007 and 3 more".
     *
     * @param  list<string>  $skus  already in SKU order
     */
    public static function skus(array $skus): string
    {
        if (count($skus) > 3) {
            return "{$skus[0]}, {$skus[1]} and ".(count($skus) - 2).' more';
        }

        return self::join($skus);
    }

    /** @param  list<string>  $sizes */
    private static function group(string $colour, array $sizes): string
    {
        $digits = $sizes !== [] && count(array_filter($sizes, 'ctype_digit')) === count($sizes);
        $word = match (true) {
            ! $digits => '',
            count($sizes) === 1 => 'size ',
            default => 'sizes ',
        };

        return trim("{$colour} {$word}".self::join($sizes));
    }

    /**
     * "A", "A and B", "A, B and C".
     *
     * @param  list<string>  $items
     */
    private static function join(array $items): string
    {
        $last = (string) array_pop($items);

        return $items === [] ? $last : implode(', ', $items).' and '.$last;
    }
}
