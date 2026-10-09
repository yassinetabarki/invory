<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * A unit or category can't be deleted: something still points at it (or it is a seeded system unit). The message is
 * written for the user and shown as an error toast; the item can be archived instead.
 */
final class ReferenceInUseException extends RuntimeException
{
    /** @param  string  $noun  singular name of what points at the item (for example "material") */
    public static function inUse(string $item, int $count, string $noun): self
    {
        return new self(__(':item is used by :count :noun, so it can\'t be deleted. Archive it instead.', [
            'item' => $item,
            'count' => $count,
            'noun' => Str::plural($noun, $count),
        ]));
    }

    public static function systemUnit(string $item): self
    {
        return new self(__(':item is a built-in unit, so it can\'t be deleted. Archive it instead.', ['item' => $item]));
    }
}
