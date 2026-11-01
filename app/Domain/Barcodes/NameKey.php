<?php

declare(strict_types=1);

namespace App\Domain\Barcodes;

/**
 * "The same name" for barcodes (INV-8, ADR-0020): the name trimmed, every run of spaces as one space, in small letters. Accents are
 * kept ("Écru" is not "Ecru"). Pure PHP on purpose: PostgreSQL's and SQLite's `lower()` disagree on accents, so the key is never worked
 * out in SQL; it is stored as `products.name_key` (the migration holds a frozen copy of this rule).
 */
final class NameKey
{
    /** The key: "denim jacket" for "  Denim   Jacket ". */
    public static function of(string $name): string
    {
        return mb_strtolower(self::tidy($name));
    }

    /** The name as typed with the extra spaces taken out and its capitals kept: "Trucker Jacket" for "Trucker  Jacket ". */
    public static function tidy(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }
}
