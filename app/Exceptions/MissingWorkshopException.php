<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Workshops\CurrentWorkshop;
use RuntimeException;

/**
 * A record that belongs to a workshop was saved while no workshop was known: nobody is signed in, and no code set one with
 * {@see CurrentWorkshop::run()}. A bug in the caller, never shown to a user.
 */
final class MissingWorkshopException extends RuntimeException
{
    /** @param class-string $model */
    public static function for(string $model): self
    {
        return new self(class_basename($model)." can't be saved without a workshop: set one with CurrentWorkshop::run() or sign in.");
    }
}
