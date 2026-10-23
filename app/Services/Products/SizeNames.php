<?php

declare(strict_types=1);

namespace App\Services\Products;

/**
 * How a size is named in text (human decision 2026-10-22, S-70): a letter size by its label ("M"), a size whose label is digits only
 * as "size 36" ("Size 36" at the start of a sentence). The size fields and table rows show the bare label; only sentences use this.
 */
final class SizeNames
{
    /** A size inside a sentence: "M", "size 36"; `$first` capitalises it for the start of a sentence ("Size 36"). */
    public static function inText(string $label, bool $first = false): string
    {
        $name = ctype_digit($label) ? "size {$label}" : $label;

        return $first ? ucfirst($name) : $name;
    }

    /** A per-size message with its size in front: "M: Enter a whole number of units.", "Size 36: Enter a whole number of units.". */
    public static function prefixed(string $label, string $message): string
    {
        return self::inText($label, true).': '.$message;
    }
}
