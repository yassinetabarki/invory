<?php

declare(strict_types=1);

namespace App\Domain\Clients;

/**
 * A client's phone number (INV-4, ADR-0021): what the screens show, and the key that finds her. Pure PHP.
 *
 * Spaces, dots, dashes and brackets are dropped. Eight digits, or +216 / 00216 and eight digits, is a Tunisian number: shown "22 345
 * 678" (2-3-3), keyed "21622345678", so "22345678", "22.345.678" and "+216 22 345 678" are one number. A number that starts with + and
 * another country code, 8 to 15 digits in all, is a number abroad: shown as typed with single spaces, keyed by its digits. Anything
 * else is not a phone Invory knows (`parse()` returns null).
 */
final readonly class PhoneNumber
{
    private const TUNISIA = '216';

    private function __construct(
        /** What the screens show: "22 345 678", or a number abroad as typed with single spaces ("+33 6 12 34 56 78"). */
        public string $display,
        /** Digits only, the country code first: "21622345678", "33612345678". Unique per workshop. */
        public string $key,
    ) {}

    public static function parse(string $typed): ?self
    {
        $typed = trim($typed);

        // A + is only allowed first, or right after an opening bracket: "(+216) 22 345 678".
        if ($typed === '' || preg_match('/^\(?\+?[0-9 .\-()]+$/', $typed) !== 1) {
            return null;
        }

        $digits = (string) preg_replace('/[^0-9]/', '', $typed);
        $plus = str_starts_with(ltrim($typed, '('), '+');

        // A Tunisian number: 8 digits, or 216 / 00216 in front of them.
        $local = match (true) {
            ! $plus && strlen($digits) === 8 => $digits,
            $plus && strlen($digits) === 11 && str_starts_with($digits, self::TUNISIA) => substr($digits, 3),
            ! $plus && strlen($digits) === 13 && str_starts_with($digits, '00'.self::TUNISIA) => substr($digits, 5),
            default => null,
        };

        if ($local !== null) {
            return new self(substr($local, 0, 2).' '.substr($local, 2, 3).' '.substr($local, 5), self::TUNISIA.$local);
        }

        // A number abroad starts with + and its country code; +216 with the wrong length is a Tunisian number typed wrong.
        if (! $plus || strlen($digits) < 8 || strlen($digits) > 15 || str_starts_with($digits, self::TUNISIA)) {
            return null;
        }

        return new self((string) preg_replace('/\s+/', ' ', $typed), $digits);
    }

    /**
     * The digits of a search term, as they would sit inside a key: "22 345" → "22345". Null when the term has fewer than two digits,
     * so a search for a name never matches phone numbers by accident.
     */
    public static function searchDigits(string $term): ?string
    {
        $digits = (string) preg_replace('/[^0-9]/', '', $term);

        return strlen($digits) >= 2 ? $digits : null;
    }
}
