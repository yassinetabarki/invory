<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A material can't be deleted: the composition of at least one non-deleted product uses it. The message is written for the
 * user and shown as an error toast, so it names the products (the first few, with a count of the rest).
 */
final class MaterialInUseException extends RuntimeException
{
    /** How many product names the message lists before it says "and N more". */
    private const LISTED_NAMES = 5;

    /**
     * @param  list<string>  $productNames  every non-deleted product that uses the material, sorted
     */
    private function __construct(string $message, public readonly array $productNames)
    {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $productNames  non-deleted products that use the material, in any order
     */
    public static function usedBy(string $material, array $productNames): self
    {
        // Case-insensitive first, bytes second: the same order on every database engine.
        usort($productNames, static fn (string $a, string $b): int => [strtolower($a), $a] <=> [strtolower($b), $b]);

        $listed = array_slice($productNames, 0, self::LISTED_NAMES);
        $more = count($productNames) - count($listed);
        $names = implode(', ', $listed).($more > 0 ? ' '.__('and :count more', ['count' => $more]) : '');

        return new self(
            __(':material is used in the composition of :products, so it can\'t be deleted. Remove it from there first.', [
                'material' => $material,
                'products' => $names,
            ]),
            $productNames,
        );
    }
}
